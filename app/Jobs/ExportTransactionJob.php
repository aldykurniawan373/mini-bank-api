<?php

namespace App\Jobs;

use App\Models\Api\Account;
use App\Models\Api\Transaction;
use App\Exports\TransactionsExport;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ExportTransactionJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $accountId,
        public int $userId,
        public ?string $startDate = null,
        public ?string $endDate = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $account = Account::with('customer')->findOrFail($this->accountId);
            $user = User::findOrFail($this->userId);

            // Generate nama file
            $filename = 'transaksi_' . $account->account_number . '_' . now()->format('YmdHis') . '.xlsx';
            $filepath = 'exports/' . $filename;

            // Query transaksi dengan filter tanggal jika ada
            $query = Transaction::where('account_id', $this->accountId)
                ->with(['account.customer', 'relatedAccount.customer'])
                ->orderBy('created_at', 'desc');

            if ($this->startDate) {
                $query->whereDate('created_at', '>=', $this->startDate);
            }

            if ($this->endDate) {
                $query->whereDate('created_at', '<=', $this->endDate);
            }

            // Export menggunakan Laravel Excel
            Excel::store(
                new TransactionsExport($query, $account),
                $filepath,
                'public'
            );

            // Log untuk notifikasi
            $fileUrl = asset('storage/' . $filepath);
            
            Log::info("Export transaksi berhasil", [
                'user_id' => $this->userId,
                'account_id' => $this->accountId,
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => $fileUrl,
            ]);

        } catch (\Exception $e) {
            Log::error("Export transaksi gagal", [
                'user_id' => $this->userId,
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

}
