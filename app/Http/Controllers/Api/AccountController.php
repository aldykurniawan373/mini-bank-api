<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Account\StoreAccountRequest;
use App\Http\Requests\Api\Account\SearchAccountRequest;
use App\Http\Requests\Api\IndexRequest;
use App\Http\Resources\AccountResource;
use App\Models\Api\Account;
use App\Models\Api\Customer;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    use ApiResponse;

    public function store(StoreAccountRequest $request, Customer $customer)
    {
        if (!Auth::user()->hasPermissionTo('customer.create')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk membuat rekening nasabah');
        }

        $account = DB::transaction(function () use ($customer) {
            $lastAccount = Account::orderBy('id', 'desc')->first();
            $nextNumber = $lastAccount ? (int) str_replace('ACC-', '', $lastAccount->account_number) + 1 : 1;
            $accountNumber = 'ACC-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            while (Account::where('account_number', $accountNumber)->exists()) {
                $nextNumber++;
                $accountNumber = 'ACC-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }

            $account = Account::create([
                'customer_id'    => $customer->id,
                'account_number' => $accountNumber,
                'balance'        => 0,
                'created_by'     => Auth::id(),
            ]);

            return $account->load('customer');
        });

        return $this->createdResponse(
            'success',
            new AccountResource($account)
        );
    }

    public function index(IndexRequest $request)
    {
        if (!Auth::user()->hasPermissionTo('customer.view') && !Auth::user()->hasPermissionTo('transaction.view')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melihat daftar rekening');
        }

        $query = Account::with('customer');

        if ($request->getSearch()) {
            $search = $request->getSearch();
            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('full_name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->getPerPage();
        $sortBy = $request->getSortBy();
        $sortDir = $request->getSortDir();

        $accounts = $query->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        return $this->paginatedResponse(
            'Daftar rekening berhasil diambil',
            AccountResource::collection($accounts)
        );
    }

    public function show(Account $account)
    {
        if (!Auth::user()->hasPermissionTo('customer.view') && !Auth::user()->hasPermissionTo('transaction.view')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melihat data rekening');
        }

        $account->load('customer');

        return $this->successResponse(
            'success',
            new AccountResource($account)
        );
    }

    public function search(SearchAccountRequest $request)
    {
        if (!Auth::user()->hasPermissionTo('customer.view') && !Auth::user()->hasPermissionTo('transaction.view')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk mencari rekening');
        }

        $query = Account::with('customer');

        if ($request->has('exclude_id')) {
            $query->where('id', '!=', $request->input('exclude_id'));
        }

        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('account_number', 'like', "%{$search}%");
        }

        $limit = $request->input('limit', 20);
        $accounts = $query->limit($limit)->get();

        return $this->successResponse(
            'Daftar rekening berhasil diambil',
            AccountResource::collection($accounts)
        );
    }
}
