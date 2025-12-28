<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api\Account;
use App\Models\Api\Customer;
use App\Models\Api\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    use ApiResponse;

    public function statistics()
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $totalBalance = Account::sum('balance');
        $totalBalanceYesterday = $totalBalance;

        $totalCustomers = Customer::count();
        $totalCustomersYesterday = Customer::where('created_at', '<', $today)->count();

        $totalAccounts = Account::count();
        $totalAccountsYesterday = Account::where('created_at', '<', $today)->count();

        $transactionsToday = Transaction::whereDate('created_at', $today->toDateString())->count();
        $transactionsYesterday = Transaction::whereDate('created_at', $yesterday->toDateString())->count();

        $transactionsThisMonth = Transaction::where('created_at', '>=', $thisMonth)->count();
        $transactionsLastMonth = Transaction::whereBetween('created_at', [$lastMonth, $thisMonth])->count();

        $depositsToday = Transaction::where('type', 'deposit')
            ->whereDate('created_at', $today->toDateString())
            ->sum('amount');
        $depositsYesterday = Transaction::where('type', 'deposit')
            ->whereDate('created_at', $yesterday->toDateString())
            ->sum('amount');

        $withdrawalsToday = Transaction::where('type', 'withdrawal')
            ->whereDate('created_at', $today->toDateString())
            ->sum('amount');
        $withdrawalsYesterday = Transaction::where('type', 'withdrawal')
            ->whereDate('created_at', $yesterday->toDateString())
            ->sum('amount');

        $transfersToday = Transaction::where('type', 'transfer')
            ->where('direction', 'out')
            ->whereDate('created_at', $today->toDateString())
            ->sum('amount');
        $transfersYesterday = Transaction::where('type', 'transfer')
            ->where('direction', 'out')
            ->whereDate('created_at', $yesterday->toDateString())
            ->sum('amount');

        $recentTransactions = Transaction::with(['account.customer', 'relatedAccount.customer'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $dailyStats = Transaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN type = "deposit" THEN amount ELSE 0 END) as deposits'),
            DB::raw('SUM(CASE WHEN type = "withdrawal" THEN amount ELSE 0 END) as withdrawals'),
            DB::raw('SUM(CASE WHEN type = "transfer" AND direction = "out" THEN amount ELSE 0 END) as transfers')
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $balanceChange = $transactionsYesterday > 0 
            ? (($transactionsToday - $transactionsYesterday) / $transactionsYesterday) * 100 
            : 0;

        $customersChange = $totalCustomersYesterday > 0
            ? (($totalCustomers - $totalCustomersYesterday) / $totalCustomersYesterday) * 100
            : 0;

        $accountsChange = $totalAccountsYesterday > 0
            ? (($totalAccounts - $totalAccountsYesterday) / $totalAccountsYesterday) * 100
            : 0;

        $transactionsChange = $transactionsYesterday > 0
            ? (($transactionsToday - $transactionsYesterday) / $transactionsYesterday) * 100
            : 0;

        return $this->successResponse(
            'success',
            [
                'summary' => [
                    'total_balance' => (float) $totalBalance,
                    'total_customers' => $totalCustomers,
                    'total_accounts' => $totalAccounts,
                    'transactions_today' => $transactionsToday,
                    'transactions_this_month' => $transactionsThisMonth,
                ],
                'today' => [
                    'transactions' => $transactionsToday,
                    'deposits' => (float) $depositsToday,
                    'withdrawals' => (float) $withdrawalsToday,
                    'transfers' => (float) $transfersToday,
                ],
                'yesterday' => [
                    'transactions' => $transactionsYesterday,
                    'deposits' => (float) $depositsYesterday,
                    'withdrawals' => (float) $withdrawalsYesterday,
                    'transfers' => (float) $transfersYesterday,
                ],
                'changes' => [
                    'transactions' => round($transactionsChange, 2),
                    'customers' => round($customersChange, 2),
                    'accounts' => round($accountsChange, 2),
                ],
                'recent_transactions' => $recentTransactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'transaction_code' => $transaction->transaction_code,
                        'type' => $transaction->type,
                        'type_label' => match($transaction->type) {
                            'deposit' => 'Setoran',
                            'withdrawal' => 'Penarikan',
                            'transfer' => 'Transfer',
                            default => $transaction->type,
                        },
                        'direction' => $transaction->direction,
                        'amount' => (float) $transaction->amount,
                        'account_number' => $transaction->account->account_number ?? null,
                        'customer_name' => $transaction->account->customer->name ?? $transaction->account->customer->full_name ?? null,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'daily_stats' => $dailyStats->map(function ($stat) {
                    return [
                        'date' => $stat->date,
                        'total' => $stat->total,
                        'deposits' => (float) $stat->deposits,
                        'withdrawals' => (float) $stat->withdrawals,
                        'transfers' => (float) $stat->transfers,
                    ];
                }),
            ]
        );
    }
}

