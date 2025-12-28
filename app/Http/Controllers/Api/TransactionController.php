<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Transaction\DepositRequest;
use App\Http\Requests\Api\Transaction\WithdrawRequest;
use App\Http\Requests\Api\Transaction\TransferRequest;
use App\Http\Requests\Api\Transaction\ExportTransactionRequest;
use App\Http\Requests\Api\Transaction\IndexTransactionRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\TransactionResource;
use App\Models\Api\Account;
use App\Models\Api\Transaction;
use App\Jobs\ExportTransactionJob;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(IndexTransactionRequest $request)
    {
        if (!Auth::user()->hasPermissionTo('transaction.view')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melihat transaksi');
        }

        $query = Transaction::with(['account.customer', 'relatedAccount.customer']);

        if ($request->getSearch()) {
            $search = $request->getSearch();
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhereHas('account', function ($accountQuery) use ($search) {
                      $accountQuery->where('account_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('account.customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('full_name', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        if ($request->has('account_id')) {
            $query->where('account_id', $request->input('account_id'));
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $perPage = $request->getPerPage();
        $sortBy = $request->getSortBy();
        $sortDir = $request->getSortDir();

        $transactions = $query->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        return $this->paginatedResponse(
            'Daftar transaksi berhasil diambil',
            TransactionResource::collection($transactions)
        );
    }

    public function deposit(DepositRequest $request, $accountId)
    {
        if (!Auth::user()->hasPermissionTo('transaction.deposit')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melakukan deposit');
        }

        $transaction = DB::transaction(function () use ($request, $accountId) {
            $account = Account::lockForUpdate()->findOrFail($accountId);

            $after = $account->balance + $request->validated()['amount'];

            $transaction = Transaction::create([
                'transaction_code' => 'DEP' . now()->format('Ymd') . Str::random(8),
                'account_id'       => $account->id,
                'type'             => 'deposit',
                'direction'        => 'in',
                'amount'           => $request->validated()['amount'],
                'created_by'       => Auth::id(),
            ]);

            $account->update([
                'balance'    => $after,
                'updated_by' => Auth::id(),
            ]);

            return $transaction->load(['account.customer']);
        });

        return $this->createdResponse(
            'success',
            new TransactionResource($transaction)
        );
    }

    public function withdraw(WithdrawRequest $request, $accountId)
    {
        if (!Auth::user()->hasPermissionTo('transaction.withdraw')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melakukan penarikan');
        }

        $transaction = DB::transaction(function () use ($request, $accountId) {
            $account = Account::lockForUpdate()->findOrFail($accountId);
            $amount = $request->validated()['amount'];

            if ($account->balance < $amount) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Saldo tidak mencukupi');
            }

            $after = $account->balance - $amount;

            $transaction = Transaction::create([
                'transaction_code' => 'WD' . now()->format('Ymd') . Str::random(8),
                'account_id'       => $account->id,
                'type'             => 'withdrawal',
                'direction'        => 'out',
                'amount'           => $request->validated()['amount'],
                'created_by'       => Auth::id(),
            ]);

            $account->update([
                'balance'    => $after,
                'updated_by' => Auth::id(),
            ]);

            return $transaction->load(['account.customer']);
        });

        return $this->createdResponse(
            'Penarikan berhasil',
            new TransactionResource($transaction)
        );
    }

    public function transfer(TransferRequest $request, $accountId)
    {
        if (!Auth::user()->hasPermissionTo('transaction.transfer')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melakukan transfer');
        }

        $validated = $request->validated();
        $transactions = DB::transaction(function () use ($validated, $accountId) {
            $fromAccount = Account::lockForUpdate()->findOrFail($accountId);
            $toAccount   = Account::lockForUpdate()->findOrFail($validated['to_account_id']);

            if ($fromAccount->balance < $validated['amount'] || $fromAccount->balance == 0) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Saldo tidak mencukupi untuk melakukan transfer');
            }

            $baseCode = 'TRF' . now()->format('Ymd') . Str::random(6);
            $fromTransactionCode = $baseCode . '-OUT';
            $toTransactionCode = $baseCode . '-IN';

            $fromAfter = $fromAccount->balance - $validated['amount'];

            $fromTransaction = Transaction::create([
                'transaction_code'    => $fromTransactionCode,
                'account_id'          => $fromAccount->id,
                'related_account_id'  => $toAccount->id,
                'type'                => 'transfer',
                'direction'           => 'out',
                'amount'              => $validated['amount'],
                'created_by'          => Auth::id(),
            ]);

            $fromAccount->update([
                'balance'    => $fromAfter,
                'updated_by' => Auth::id(),
            ]);

            $toAfter = $toAccount->balance + $validated['amount'];

            $toTransaction = Transaction::create([
                'transaction_code'    => $toTransactionCode,
                'account_id'          => $toAccount->id,
                'related_account_id'  => $fromAccount->id,
                'type'                => 'transfer',
                'direction'           => 'in',
                'amount'              => $validated['amount'],
                'created_by'          => Auth::id(),
            ]);

            $toAccount->update([
                'balance'    => $toAfter,
                'updated_by' => Auth::id(),
            ]);

            return [
                'from_transaction' => $fromTransaction->load(['account.customer', 'relatedAccount.customer']),
                'to_transaction'   => $toTransaction->load(['account.customer', 'relatedAccount.customer']),
            ];
        });

        return $this->createdResponse(
            'Transfer berhasil',
            [
                'from_transaction' => new TransactionResource($transactions['from_transaction']),
                'to_transaction'   => new TransactionResource($transactions['to_transaction']),
            ]
        );
    }

    public function balance($accountId)
    {
        if (!Auth::user()->hasPermissionTo('transaction.view')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melihat saldo');
        }

        $account = Account::with('customer')->findOrFail($accountId);

        return $this->successResponse(
            'Data saldo berhasil diambil',
            new AccountResource($account)
        );
    }

    public function history(IndexTransactionRequest $request, $accountId)
    {
        if (!Auth::user()->hasPermissionTo('transaction.view')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melihat riwayat transaksi');
        }

        $query = Transaction::where('account_id', $accountId)
            ->with(['account.customer', 'relatedAccount.customer']);

        if ($request->getSearch()) {
            $search = $request->getSearch();
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $query->orderBy($request->getSortBy(), $request->getSortDir());

        $transactions = $query->paginate($request->getPerPage());

        return $this->paginatedResponse(
            'Riwayat transaksi berhasil diambil',
            TransactionResource::collection($transactions)
        );
    }

    public function export(Request $request, $accountId)
    {
        if (!Auth::user()->hasPermissionTo('transaction.export')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melakukan ekspor transaksi');
        }

        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $account = Account::with('customer')->findOrFail($accountId);
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $filename = 'transaksi_' . $account->account_number . '_' . now()->format('YmdHis') . '.xlsx';

        ExportTransactionJob::dispatch(
            $accountId,
            Auth::id(),
            $startDate,
            $endDate
        );

        return $this->successResponse(
            'Proses ekspor transaksi telah dimulai. File akan tersedia di storage/exports/' . $filename,
            [
                'filename' => $filename,
                'filepath' => 'exports/' . $filename,
            ]
        );
    }

    public function downloadExport(Request $request, $filename)
    {
        if (!Auth::user()->hasPermissionTo('transaction.export')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk mengunduh file ekspor');
        }

        $filepath = 'exports/' . $filename;

        if (!Storage::disk('public')->exists($filepath)) {
            abort(Response::HTTP_NOT_FOUND, 'File tidak ditemukan');
        }

        return Storage::disk('public')->download($filepath);
    }

    public function checkExportStatus(Request $request, $filename)
    {
        if (!Auth::user()->hasPermissionTo('transaction.export')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melihat status ekspor');
        }

        $filepath = 'exports/' . $filename;
        $exists = Storage::disk('public')->exists($filepath);

        return $this->successResponse(
            $exists ? 'File sudah tersedia' : 'File masih dalam proses',
            [
                'filename' => $filename,
                'exists' => $exists,
                'filepath' => $filepath,
            ]
        );
    }

    public function listExports(Request $request, $accountId = null)
    {
        if (!Auth::user()->hasPermissionTo('transaction.export')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melihat daftar ekspor');
        }

        $files = Storage::disk('public')->files('exports');
        
        $account = null;
        if ($accountId) {
            $account = Account::findOrFail($accountId);
        }
        
        $exports = collect($files)->map(function ($file) use ($account) {
            $filename = basename($file);
            $filepath = $file;
            $fullPath = Storage::disk('public')->path($file);
            
            $fileAccountNumber = null;
            if (preg_match('/transaksi_([A-Z0-9-]+)_/', $filename, $matches)) {
                $fileAccountNumber = $matches[1] ?? null;
            }
            
            return [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => Storage::disk('public')->size($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
                'download_url' => asset('storage/' . $filepath),
                'account_number' => $fileAccountNumber,
            ];
        })->filter(function ($export) use ($account) {
            if (!$account) {
                return true;
            }
            return $export['account_number'] === $account->account_number;
        })->sortByDesc('created_at')->values()->toArray();

        return $this->successResponse(
            'Daftar file ekspor berhasil diambil',
            $exports
        );
    }
}
