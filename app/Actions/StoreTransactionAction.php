<?php

namespace App\Actions;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StoreTransactionAction
{
    /**
     * Simpan transaksi + item secara atomic.
     *
     * @param  User  $cashier
     * @param  array{payment_method: string, notes: ?string, items: array<array{service_id: int, service_name: string, qty: int, unit_price: int}>}  $data
     */
    public function execute(User $cashier, array $data): Transaction
    {
        return DB::transaction(function () use ($cashier, $data) {
            $subtotal = collect($data['items'])->sum(fn ($item) => $item['qty'] * $item['unit_price']);

            $transaction = Transaction::create([
                'transaction_code' => $this->generateCode(),
                'transaction_date' => Carbon::today(),
                'cashier_id' => $cashier->id,
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'service_id' => $item['service_id'],
                    'service_name' => $item['service_name'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['qty'] * $item['unit_price'],
                ]);
            }

            return $transaction;
        });
    }

    private function generateCode(): string
    {
        $date = Carbon::today()->format('Ymd');

        // Kunci baris terakhir hari ini agar tidak ada nomor duplikat
        // pada request bersamaan (race condition)
        $last = Transaction::whereDate('transaction_date', Carbon::today())
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('transaction_code');

        $lastSeq = $last ? (int) substr($last, -4) : 0;

        return sprintf('TRX-%s-%04d', $date, $lastSeq + 1);
    }
}
