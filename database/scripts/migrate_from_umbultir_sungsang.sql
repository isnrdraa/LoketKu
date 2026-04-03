-- ============================================================
-- MIGRASI DATA: umbultir_sungsang → loketku (LoketKu Laravel)
-- ============================================================
--
-- Prasyarat:
--   1. php artisan migrate sudah dijalankan di database baru (loketku)
--   2. Database lama (umbultir_sungsang) tersedia di server MySQL yang sama
--   3. User MySQL memiliki akses ke kedua database
--
-- Cara pakai:
--   mysql -u root -p loketku < migrate_from_umbultir_sungsang.sql
--
-- Atau dari dalam MySQL client:
--   USE loketku;
--   SOURCE /path/to/migrate_from_umbultir_sungsang.sql;
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- 1. Bersihkan data default dari seeder Laravel
--    (settings dibiarkan karena sudah dikonfigurasi)
-- ------------------------------------------------------------
TRUNCATE TABLE transaction_items;
TRUNCATE TABLE transactions;
TRUNCATE TABLE expenses;
TRUNCATE TABLE services;
TRUNCATE TABLE service_categories;
TRUNCATE TABLE users;

-- ------------------------------------------------------------
-- 2. Migrasi users
--    Role lama: admin → admin, loket → cashier
--    Password sudah bcrypt, langsung kompatibel dengan Laravel
-- ------------------------------------------------------------
INSERT INTO users (id, username, name, password, role, is_active, remember_token, created_at, updated_at)
SELECT
    id,
    username,
    nama,
    password,
    CASE role
        WHEN 'admin' THEN 'admin'
        WHEN 'loket' THEN 'cashier'
        ELSE 'cashier'
    END,
    1,
    NULL,
    created_at,
    updated_at
FROM umbultir_sungsang.users;

-- ------------------------------------------------------------
-- 3. Migrasi kategori layanan
-- ------------------------------------------------------------
INSERT INTO service_categories (id, name, description, is_active, created_at, updated_at)
SELECT
    id,
    nama_kategori,
    NULL,
    1,
    created_at,
    updated_at
FROM umbultir_sungsang.kategori_layanan;

-- ------------------------------------------------------------
-- 4. Migrasi layanan
-- ------------------------------------------------------------
INSERT INTO services (id, service_category_id, name, price, is_active, created_at, updated_at)
SELECT
    id,
    id_kategori,
    nama_layanan,
    CAST(harga AS UNSIGNED),
    CASE status WHEN 'active' THEN 1 ELSE 0 END,
    created_at,
    updated_at
FROM umbultir_sungsang.layanan;

-- ------------------------------------------------------------
-- 5. Migrasi pengeluaran
--    deskripsi bisa NULL di lama, pakai string kosong jika NULL
-- ------------------------------------------------------------
INSERT INTO expenses (id, expense_date, category, description, amount, created_by, created_at, updated_at)
SELECT
    id,
    tanggal,
    kategori,
    COALESCE(deskripsi, '-'),
    CAST(nominal AS UNSIGNED),
    id_user,
    created_at,
    updated_at
FROM umbultir_sungsang.pengeluaran;

-- ------------------------------------------------------------
-- 6. Migrasi transaksi
--    - transaction_code dibuat dari tanggal + nomor urut harian
--    - payment_method default 'cash' (data lama tidak menyimpan ini)
--    - subtotal = grand_total = total (tidak ada diskon di transaksi harian)
-- ------------------------------------------------------------
INSERT INTO transactions (id, transaction_code, transaction_date, cashier_id, payment_method, subtotal, grand_total, notes, created_at, updated_at)
SELECT
    t.id,
    CONCAT(
        'TRX-',
        DATE_FORMAT(t.tanggal, '%Y%m%d'),
        '-',
        LPAD(ROW_NUMBER() OVER (PARTITION BY t.tanggal ORDER BY t.id), 4, '0')
    ),
    t.tanggal,
    t.id_user,
    'cash',
    CAST(t.total AS UNSIGNED),
    CAST(t.total AS UNSIGNED),
    NULL,
    t.created_at,
    t.updated_at
FROM umbultir_sungsang.transaksi t;

-- ------------------------------------------------------------
-- 7. Migrasi transaksi_detail
-- ------------------------------------------------------------
INSERT INTO transaction_items (id, transaction_id, service_id, service_name, qty, unit_price, subtotal, created_at, updated_at)
SELECT
    id,
    id_transaksi,
    id_layanan,
    nama_layanan,
    quantity,
    CAST(harga AS UNSIGNED),
    CAST(subtotal AS UNSIGNED),
    created_at,
    created_at
FROM umbultir_sungsang.transaksi_detail;

-- ------------------------------------------------------------
-- 8. Reset AUTO_INCREMENT agar insert baru tidak bentrok
-- ------------------------------------------------------------
ALTER TABLE users              AUTO_INCREMENT = 100;
ALTER TABLE service_categories AUTO_INCREMENT = 100;
ALTER TABLE services           AUTO_INCREMENT = 100;
ALTER TABLE expenses           AUTO_INCREMENT = 1000;
ALTER TABLE transactions       AUTO_INCREMENT = 10000;
ALTER TABLE transaction_items  AUTO_INCREMENT = 10000;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Selesai. Verifikasi dengan query berikut:
-- ============================================================
SELECT 'users'              AS tabel, COUNT(*) AS total FROM users
UNION ALL
SELECT 'service_categories',           COUNT(*) FROM service_categories
UNION ALL
SELECT 'services',                     COUNT(*) FROM services
UNION ALL
SELECT 'expenses',                     COUNT(*) FROM expenses
UNION ALL
SELECT 'transactions',                 COUNT(*) FROM transactions
UNION ALL
SELECT 'transaction_items',            COUNT(*) FROM transaction_items;
