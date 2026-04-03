# LoketKu

Repository: [github.com/isnrdraa/LoketKu](https://github.com/isnrdraa/LoketKu)

Sistem kasir berbasis web untuk pengelolaan loket wisata. Dibangun dengan Laravel 13, Inertia.js, dan Vue 3. Dapat dijalankan sebagai aplikasi Android menggunakan Capacitor.

---

## Fitur

**Kasir**
- Transaksi baru dengan pemilihan layanan dan jumlah
- Pencatatan metode pembayaran: tunai atau QRIS (bukan integrasi payment gateway)
- Cetak struk thermal via browser atau Bluetooth (Android)
- Riwayat transaksi dengan filter tanggal dan metode pembayaran

**Admin**
- Manajemen pengguna (admin dan kasir)
- Manajemen kategori layanan dan layanan
- Pencatatan pengeluaran
- Laporan keuangan harian dengan cetak A4
- Pengaturan toko: nama, alamat, telepon, slogan, dan timezone

**Umum**
- Autentikasi berbasis username
- Sidebar navigasi dinamis sesuai role
- Notifikasi flash untuk setiap aksi

---

## Instalasi

### Prasyarat

- PHP 8.2 atau lebih baru
- Composer
- Node.js 18 atau lebih baru
- MySQL

### Langkah

**1. Clone dan install dependensi**

```bash
git clone <repository-url> LoketKu
cd LoketKu

composer install
npm install
```

**2. Konfigurasi environment**

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuai kebutuhan:

```env
APP_NAME=LoketKu
APP_URL=https://domain-kamu.com

DB_DATABASE=loketku
DB_USERNAME=root
DB_PASSWORD=password_kamu

SESSION_DRIVER=database
SESSION_DOMAIN=domain-kamu.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none

ASSET_URL=https://domain-kamu.com
```

**3. Migrasi dan seeder**

```bash
php artisan migrate
php artisan db:seed --class=AdminSeeder
```

Akun default setelah seeder:

| Username | Password | Role  |
|----------|----------|-------|
| admin    | admin123 | Admin |
| kasir    | kasir123 | Kasir |

**4. Build aset**

```bash
npm run build
```

**5. Jalankan server**

```bash
php artisan serve
```

---

## Build Aplikasi Android

### Konfigurasi Server

Sebelum build, sesuaikan `server.url` di `capacitor.config.ts`:

```typescript
server: {
    url: 'https://domain-kamu.com', // URL Laravel yang dapat diakses perangkat
    androidScheme: 'http',
},
```

| Lingkungan          | Nilai `server.url`              |
|---------------------|---------------------------------|
| Emulator Android    | `http://10.0.2.2:8000`          |
| Perangkat fisik LAN | `http://192.168.1.X:8000`       |
| Production          | `https://domain-kamu.com`       |

### Sinkronisasi

```bash
npm run build
npx cap sync android
```

### Build dengan Android Studio (Windows)

1. Buka Android Studio
2. Pilih **Open** dan arahkan ke folder `android/`
3. Tunggu Gradle sync selesai
4. Pilih **Build > Build Bundle(s) / APK(s) > Build APK(s)**
5. APK tersedia di `android/app/build/outputs/apk/debug/`

### Build dengan Gradlew (Linux/VPS)

**Prasyarat:** JDK 21, Android SDK dengan `platforms;android-36` dan `build-tools;36.0.0`.

```bash
# Set variabel environment
export JAVA_HOME=/usr/lib/jvm/java-21-openjdk-amd64
export ANDROID_HOME=$HOME/Android/Sdk

# Buat file local.properties
echo "sdk.dir=$ANDROID_HOME" > android/local.properties

# Build APK debug
cd android
./gradlew assembleDebug --no-daemon
```

APK tersedia di `android/app/build/outputs/apk/debug/app-debug.apk`.

> Untuk build release (distribusi), diperlukan keystore signing. Build debug sudah cukup untuk penggunaan internal.

---

## Catatan Tambahan

- Setiap perubahan variabel `VITE_*` di `.env` mengharuskan `npm run build` ulang karena nilai dikompilasi secara statis oleh Vite.
- Jika Laravel berada di belakang reverse proxy (nginx), pastikan `trustProxies` dikonfigurasi agar URL aset menggunakan HTTPS. Konfigurasi ini sudah diterapkan di `bootstrap/app.php`.
- Cetak struk via Bluetooth hanya tersedia di aplikasi Android. Di browser, cetak menggunakan `window.print()`.
