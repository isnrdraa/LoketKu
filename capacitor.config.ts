import type { CapacitorConfig } from '@capacitor/cli';

/**
 * Konfigurasi Capacitor untuk LoketKu Android App.
 *
 * PENTING:
 * - Saat development/testing di emulator  → server.url = 'http://10.0.2.2:8000'
 * - Saat testing di perangkat fisik       → server.url = 'http://192.168.x.x:8000'
 *   (ganti dengan IP lokal mesin kamu, cek via `ipconfig`)
 * - Saat build APK production             → ubah server.url ke URL server yang di-deploy
 *   lalu hapus baris `cleartext: true`
 */

const isDev = process.env.NODE_ENV !== 'production';

const config: CapacitorConfig = {
    appId: 'com.loketku.app',
    appName: 'LoketKu',

    /**
     * webDir dipakai sebagai fallback jika server.url tidak tersedia.
     * Karena LoketKu adalah Inertia app (server-rendered), kita selalu
     * pakai server.url — webDir hanya berisi halaman loading/fallback.
     */
    webDir: 'capacitor-www',

    server: {
        /**
         * URL Laravel kamu.
         * Ganti sesuai environment:
         *   Emulator Android → http://10.0.2.2:8000
         *   Perangkat fisik  → http://192.168.1.X:8000
         *   Production       → https://loketku.yourdomain.com
         */
        url: 'https://laravel.isnrdra.dev',

        /**
         * androidScheme: 'https' karena sudah pakai domain HTTPS.
         */
        androidScheme: 'https',
    },

    android: {
        /**
         * Izinkan mixed content (HTTP dalam HTTPS context)
         * untuk kemudahan development.
         */
        allowMixedContent: true,

        /**
         * Konfigurasi WebView untuk memastikan
         * session/cookie Laravel berfungsi di Android.
         */
        webContentsDebuggingEnabled: isDev,
    },

    plugins: {
        /**
         * SplashScreen — splash screen saat app pertama dibuka.
         * Akan otomatis hilang setelah halaman pertama Inertia selesai load.
         */
        SplashScreen: {
            launchShowDuration: 2000,
            backgroundColor: '#ffffff',
            showSpinner: true,
            spinnerColor: '#6366f1', // warna primary app (indigo)
            androidSpinnerStyle: 'large',
        },
    },
};

export default config;
