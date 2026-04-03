<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyData extends Command
{
    protected $signature = 'import:legacy
                            {file : Path to the legacy SQL dump file}
                            {--created-by=1 : User ID to assign as creator for imported expenses}
                            {--cashier-id= : User ID to assign as cashier for imported transactions (default: first cashier user)}
                            {--skip-transactions : Skip importing transaksi and transaksi_detail}
                            {--fresh : Truncate relevant tables before importing}';

    protected $description = 'Import kategori layanan, layanan, pengeluaran, dan transaksi dari file SQL dump lama';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Membaca file: {$filePath}");
        $sql = file_get_contents($filePath);

        if (! $sql) {
            $this->error('File kosong atau tidak dapat dibaca.');
            return self::FAILURE;
        }

        // Tentukan cashier ID untuk transaksi
        $cashierId = $this->option('cashier-id')
            ? (int) $this->option('cashier-id')
            : $this->resolveDefaultCashierId();

        $this->line("  Cashier ID untuk transaksi: <comment>{$cashierId}</comment>");

        if ($this->option('fresh')) {
            $this->warn('Opsi --fresh aktif, menghapus data lama...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('transaction_items')->truncate();
            DB::table('transactions')->truncate();
            DB::table('expenses')->truncate();
            DB::table('services')->truncate();
            DB::table('service_categories')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $createdBy = (int) $this->option('created-by');

        DB::beginTransaction();
        try {
            $katCount     = $this->importKategoriLayanan($sql);
            $layananCount = $this->importLayanan($sql);
            $expenseCount = $this->importPengeluaran($sql, $createdBy);

            $trxCount  = 0;
            $itemCount = 0;
            if (! $this->option('skip-transactions')) {
                [$trxCount, $itemCount] = $this->importTransaksi($sql, $cashierId);
            }

            DB::commit();

            $this->newLine();
            $this->table(
                ['Tabel', 'Record diimpor'],
                [
                    ['service_categories', $katCount],
                    ['services',           $layananCount],
                    ['expenses',           $expenseCount],
                    ['transactions',       $trxCount],
                    ['transaction_items',  $itemCount],
                ]
            );
            $this->info('✔ Impor selesai.');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Impor gagal: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    // ----------------------------------------------------------------
    // Helper: Tentukan cashier ID default
    // ----------------------------------------------------------------

    private function resolveDefaultCashierId(): int
    {
        // Cari user dengan role cashier
        $cashier = User::where('role', 'cashier')->orderBy('id')->first();
        if ($cashier) {
            return $cashier->id;
        }

        // Fallback ke admin
        $admin = User::where('role', 'admin')->orderBy('id')->first();
        if ($admin) {
            return $admin->id;
        }

        return 1;
    }

    // ----------------------------------------------------------------
    // Parser helpers
    // ----------------------------------------------------------------

    /**
     * Ekstrak baris-baris data dari blok INSERT INTO `<table>` di SQL dump.
     */
    private function parseInserts(string $sql, string $tableName): array
    {
        $pattern = '/INSERT INTO\s+[`"]?' . preg_quote($tableName, '/') . '[`"]?\s*\([^)]+\)\s*VALUES\s*(.*?);/si';

        if (! preg_match_all($pattern, $sql, $matches)) {
            return [];
        }

        $rows = [];
        foreach ($matches[1] as $valuesBlock) {
            preg_match_all('/\(([^()]*(?:\(.*?\)[^()]*)*)\)/s', $valuesBlock, $rowMatches);

            foreach ($rowMatches[1] as $rowRaw) {
                $rows[] = $this->parseRow($rowRaw);
            }
        }

        return $rows;
    }

    /**
     * Parse satu baris nilai SQL menjadi array PHP.
     */
    private function parseRow(string $rowRaw): array
    {
        $values = [];
        $rowRaw = trim($rowRaw);
        $len    = strlen($rowRaw);
        $i      = 0;

        while ($i < $len) {
            while ($i < $len && $rowRaw[$i] === ' ') {
                $i++;
            }
            if ($i >= $len) {
                break;
            }

            if ($rowRaw[$i] === "'") {
                $i++;
                $val = '';
                while ($i < $len) {
                    if ($rowRaw[$i] === '\\' && isset($rowRaw[$i + 1])) {
                        $next = $rowRaw[$i + 1];
                        $val .= match ($next) {
                            'n'  => "\n",
                            'r'  => "\r",
                            't'  => "\t",
                            '\'' => "'",
                            '\\' => '\\',
                            default => $rowRaw[$i + 1],
                        };
                        $i += 2;
                    } elseif ($rowRaw[$i] === "'") {
                        $i++;
                        break;
                    } else {
                        $val .= $rowRaw[$i];
                        $i++;
                    }
                }
                $values[] = $val;
            } else {
                $start = $i;
                while ($i < $len && $rowRaw[$i] !== ',') {
                    $i++;
                }
                $token    = trim(substr($rowRaw, $start, $i - $start));
                $values[] = strtoupper($token) === 'NULL' ? null : $token;
            }

            if ($i < $len && $rowRaw[$i] === ',') {
                $i++;
            }
        }

        return $values;
    }

    // ----------------------------------------------------------------
    // Import per tabel
    // ----------------------------------------------------------------

    private function importKategoriLayanan(string $sql): int
    {
        $this->line('Mengimpor <comment>kategori_layanan</comment> → service_categories...');

        // Kolom: id, nama_kategori, created_at, updated_at
        $rows  = $this->parseInserts($sql, 'kategori_layanan');
        $count = 0;

        foreach ($rows as $row) {
            [$id, $namaKategori, $createdAt, $updatedAt] = $row;

            ServiceCategory::upsert(
                [
                    'id'          => (int) $id,
                    'name'        => $namaKategori,
                    'description' => null,
                    'is_active'   => true,
                    'created_at'  => $createdAt,
                    'updated_at'  => $updatedAt,
                ],
                uniqueBy: ['id'],
                update:   ['name', 'updated_at'],
            );
            $count++;
        }

        $this->line("  → {$count} kategori");
        return $count;
    }

    private function importLayanan(string $sql): int
    {
        $this->line('Mengimpor <comment>layanan</comment> → services...');

        // Kolom: id, id_kategori, nama_layanan, harga, status, created_at, updated_at
        $rows  = $this->parseInserts($sql, 'layanan');
        $count = 0;

        foreach ($rows as $row) {
            [$id, $idKategori, $namaLayanan, $harga, $status, $createdAt, $updatedAt] = $row;

            Service::upsert(
                [
                    'id'                  => (int) $id,
                    'service_category_id' => (int) $idKategori,
                    'name'                => $namaLayanan,
                    'price'               => (int) round((float) $harga),
                    'is_active'           => $status === 'active' ? 1 : 0,
                    'created_at'          => $createdAt,
                    'updated_at'          => $updatedAt,
                ],
                uniqueBy: ['id'],
                update:   ['name', 'price', 'is_active', 'updated_at'],
            );
            $count++;
        }

        $this->line("  → {$count} layanan");
        return $count;
    }

    private function importPengeluaran(string $sql, int $createdBy): int
    {
        $this->line('Mengimpor <comment>pengeluaran</comment> → expenses...');

        // Kolom: id, tanggal, kategori, deskripsi, nominal, id_user, created_at, updated_at
        $rows  = $this->parseInserts($sql, 'pengeluaran');
        $count = 0;

        foreach ($rows as $row) {
            [$id, $tanggal, $kategori, $deskripsi, $nominal, , $createdAt, $updatedAt] = $row;

            Expense::upsert(
                [
                    'id'           => (int) $id,
                    'expense_date' => $tanggal,
                    'category'     => $kategori,
                    'description'  => $deskripsi ?? '-',
                    'amount'       => (int) round((float) $nominal),
                    'created_by'   => $createdBy,
                    'created_at'   => $createdAt,
                    'updated_at'   => $updatedAt,
                ],
                uniqueBy: ['id'],
                update:   ['expense_date', 'category', 'description', 'amount', 'updated_at'],
            );
            $count++;
        }

        $this->line("  → {$count} pengeluaran");
        return $count;
    }

    /**
     * Import transaksi & transaksi_detail.
     * Semua transaksi default ke payment_method = 'cash'.
     *
     * @return array{int, int} [jumlah transaksi, jumlah items]
     */
    private function importTransaksi(string $sql, int $cashierId): array
    {
        $this->line('Mengimpor <comment>transaksi</comment> → transactions...');

        // Kolom: id, tanggal, total, id_user, created_at, updated_at
        $rows = $this->parseInserts($sql, 'transaksi');

        // Hitung nomor urut per hari untuk transaction_code
        $dailyCounter = [];
        $trxData      = [];

        foreach ($rows as $row) {
            [$id, $tanggal, $total, , $createdAt, $updatedAt] = $row;

            $dateKey = $tanggal; // YYYY-MM-DD
            $dailyCounter[$dateKey] = ($dailyCounter[$dateKey] ?? 0) + 1;
            $seq  = $dailyCounter[$dateKey];
            $code = 'TRX-' . str_replace('-', '', $dateKey) . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            $trxData[] = [
                'id'               => (int) $id,
                'transaction_code' => $code,
                'transaction_date' => $dateKey,
                'cashier_id'       => $cashierId,
                'payment_method'   => 'cash',
                'subtotal'         => (int) round((float) $total),
                'grand_total'      => (int) round((float) $total),
                'notes'            => null,
                'created_at'       => $createdAt,
                'updated_at'       => $updatedAt,
            ];
        }

        // Insert dalam batch agar lebih cepat
        $trxChunks = array_chunk($trxData, 500);
        foreach ($trxChunks as $chunk) {
            DB::table('transactions')->upsert(
                $chunk,
                uniqueBy: ['id'],
                update:   ['transaction_date', 'cashier_id', 'subtotal', 'grand_total', 'updated_at'],
            );
        }

        $trxCount = count($trxData);
        $this->line("  → {$trxCount} transaksi");

        // ---- Import transaksi_detail → transaction_items ----
        $this->line('Mengimpor <comment>transaksi_detail</comment> → transaction_items...');

        // Kolom: id, id_transaksi, id_layanan, nama_layanan, quantity, harga, subtotal, created_at
        $detailRows = $this->parseInserts($sql, 'transaksi_detail');
        $itemData   = [];

        foreach ($detailRows as $row) {
            [$id, $idTrx, $idLayanan, $namaLayanan, $quantity, $harga, $subtotal, $createdAt] = $row;

            $itemData[] = [
                'id'             => (int) $id,
                'transaction_id' => (int) $idTrx,
                'service_id'     => (int) $idLayanan,
                'service_name'   => $namaLayanan,
                'qty'            => (int) $quantity,
                'unit_price'     => (int) round((float) $harga),
                'subtotal'       => (int) round((float) $subtotal),
                'created_at'     => $createdAt,
                'updated_at'     => $createdAt,
            ];
        }

        $itemChunks = array_chunk($itemData, 500);
        foreach ($itemChunks as $chunk) {
            DB::table('transaction_items')->upsert(
                $chunk,
                uniqueBy: ['id'],
                update:   ['qty', 'unit_price', 'subtotal'],
            );
        }

        $itemCount = count($itemData);
        $this->line("  → {$itemCount} item transaksi");

        return [$trxCount, $itemCount];
    }
}
