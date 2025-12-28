<?php

namespace App\Exports;

use App\Models\Api\Account;
use App\Models\Api\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected Builder $query;
    protected Account $account;

    public function __construct(Builder $query, Account $account)
    {
        $this->query = $query;
        $this->account = $account;
    }

    /**
     * Query untuk mengambil data transaksi
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Headings untuk kolom Excel
     */
    public function headings(): array
    {
        return [
            'Tanggal',
            'No. Transaksi',
            'Tipe',
            'Arah',
            'Jumlah',
            'Rekening Terkait',
        ];
    }

    /**
     * Mapping data untuk setiap row
     */
    public function map($transaction): array
    {
        $typeLabel = match ($transaction->type) {
            'deposit' => 'Setoran',
            'withdrawal' => 'Penarikan',
            'transfer' => 'Transfer',
            default => $transaction->type,
        };

        $directionLabel = $transaction->direction == 'in' ? 'Masuk' : 'Keluar';
        $relatedAccount = $transaction->relatedAccount 
            ? $transaction->relatedAccount->account_number 
            : '-';

        return [
            $transaction->created_at->format('d/m/Y H:i:s'),
            $transaction->transaction_code,
            $typeLabel,
            $directionLabel,
            number_format($transaction->amount, 0, ',', '.'),
            $relatedAccount,
        ];
    }

    /**
     * Title untuk worksheet
     */
    public function title(): string
    {
        return 'Transaksi ' . $this->account->account_number;
    }

    /**
     * Styling untuk worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Style untuk header
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Auto width untuk kolom
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $sheet;
    }
}
