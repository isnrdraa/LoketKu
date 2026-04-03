<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImportLegacyData extends Command
{
    protected $signature = 'import:legacy
                            {file : Path to the legacy SQL dump file}
                            {--created-by=1 : User ID to assign as creator for imported expenses}
                            {--fresh : Truncate service_categories, services, expenses before importing}';

    protected $description = 'Import kategori layanan, layanan, dan pengeluaran dari file SQL dump lama';

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

        if ($this->option('fresh')) {
            $this->warn('Opsi --fresh aktif, menghapus data lama...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('expenses')->truncate();
            DB::table('services')->truncate();
            DB::table('service_categories')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $createdBy = (int) $this->option('created-by');

        DB::beginTransaction();
        try {
            $katCount      = $this->importKategoriLayanan($sql);
            $layananCount  = $this->importLayanan($sql);
            $expenseCount  = $this->importPengeluaran($sql, $createdBy);

            DB::commit();

            $this->newLine();
            $this->table(
                ['Tabel', 'Record diimpor'],
                [
                    ['service_categories', $katCount],
                    ['services',           $layananCount],
                    ['expenses',           $expenseCount],
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
    // Parser helpers
    // ----------------------------------------------------------------

    /**
     * Ekstrak baris-baris data dari blok INSERT INTO `<table>` di SQL dump.
     * Mengembalikan array of array (setiap baris = satu record).
     */
    private function parseInserts(string $sql, string $tableName): array
    {
        // Cari semua blok INSERT INTO `nama_tabel` (...) VALUES ...;
        $pattern = '/INSERT INTO\s+[`"]?' . preg_quote($tableName, '/') . '[`"]?\s*\([^)]+\)\s*VALUES\s*(.*?);/si';

        if (! preg_match_all($pattern, $sql, $matches)) {
            return [];
        }

        $rows = [];
        foreach ($matches[1] as $valuesBlock) {
            // Pisahkan antar baris: (...), (...)
            // Pakai regex yang menangani string dengan koma/kurung di dalamnya
            preg_match_all('/\(([^()]*(?:\(.*?\)[^()]*)*)\)/s', $valuesBlock, $rowMatches);

            foreach ($rowMatches[1] as $rowRaw) {
                $rows[] = $this->parseRow($rowRaw);
            }
        }

        return $rows;
    }

    /**
     * Parse satu baris nilai SQL menjadi array PHP.
     * Contoh input: `1, 'Kendaraan', '2026-01-11 02:42:58', '2026-01-11 02:42:58'`
     */
    private function parseRow(string $rowRaw): array
    {
        $values = [];
        $rowRaw = trim($rowRaw);
        $len    = strlen($rowRaw);
        $i      = 0;

        while ($i < $len) {
            // Lewati spasi
            while ($i < $len && $rowRaw[$i] === ' ') {
                $i++;
            }
            if ($i >= $len) {
                break;
            }

            if ($rowRaw[$i] === "'") {
                // String value — tangani escaped quote \'
                $i++;
                $val = '';
                while ($i < $len) {
                    if ($rowRaw[$i] === '\\' && isset($rowRaw[$i + 1])) {
                        // Escape sequence
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
                // Numeric / NULL / keyword
                $start = $i;
                while ($i < $len && $rowRaw[$i] !== ',') {
                    $i++;
                }
                $token = trim(substr($rowRaw, $start, $i - $start));
                $values[] = strtoupper($token) === 'NULL' ? null : $token;
            }

            // Lewati koma pemisah
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
                    'id'                   => (int) $id,
                    'service_category_id'  => (int) $idKategori,
                    'name'                 => $namaLayanan,
                    'price'                => (int) round((float) $harga),
                    'is_active'            => $status === 'active' ? 1 : 0,
                    'created_at'           => $createdAt,
                    'updated_at'           => $updatedAt,
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
}
