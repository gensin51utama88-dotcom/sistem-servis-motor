<?php
/**
 * ==========================================================
 * SERVISKU - HELPER WHATSAPP FONNTE
 * ==========================================================
 *
 * Digunakan oleh:
 * - notifikasi_wa.php
 * - data_servis.php
 *
 * Konfigurasi:
 * - config/fonnte.php
 *
 * Database:
 * - servis
 * - notifikasi_wa
 * ==========================================================
 */

require_once __DIR__ . '/../config/fonnte.php';

/* ==========================================================
   AMBIL TOKEN FONNTE
========================================================== */

if (!function_exists('skAmbilTokenFonnte')) {

    function skAmbilTokenFonnte()
    {
        /*
         * Prioritas:
         * 1. Environment variable
         * 2. Konstanta config/fonnte.php
         */

        $tokenEnvironment = getenv('FONNTE_TOKEN');

        if (
            is_string($tokenEnvironment) &&
            trim($tokenEnvironment) !== ''
        ) {
            return trim($tokenEnvironment);
        }

        if (defined('SERVISKU_FONNTE_TOKEN')) {

            $token = trim(
                (string) SERVISKU_FONNTE_TOKEN
            );

            if (
                $token !== '' &&
                $token !== 'MASUKKAN_TOKEN_FONNTE_BARU_DI_SINI'
            ) {
                return $token;
            }
        }

        return '';
    }
}

/* ==========================================================
   FORMAT NOMOR WHATSAPP INDONESIA
========================================================== */

if (!function_exists('skFormatNomorWa')) {

    function skFormatNomorWa($nomor)
    {
        $nomor = preg_replace(
            '/[^0-9]/',
            '',
            (string) $nomor
        );

        if ($nomor === '') {
            throw new RuntimeException(
                'Nomor WhatsApp pelanggan belum diisi.'
            );
        }

        /*
         * 081234567890 -> 6281234567890
         * 81234567890  -> 6281234567890
         * 6281234567890 -> tetap
         */

        if (substr($nomor, 0, 1) === '0') {

            $nomor = '62' . substr($nomor, 1);

        } elseif (substr($nomor, 0, 1) === '8') {

            $nomor = '62' . $nomor;
        }

        if (!preg_match('/^628[0-9]{7,12}$/', $nomor)) {

            throw new RuntimeException(
                'Nomor WhatsApp tidak valid. ' .
                'Gunakan format 08xxxxxxxxxx.'
            );
        }

        return $nomor;
    }
}

/* ==========================================================
   TEMPLATE PESAN MOTOR SELESAI
========================================================== */

if (!function_exists('skPesanServisSelesai')) {

    function skPesanServisSelesai(array $servis)
    {
        $nama = trim(
            (string) ($servis['nama_pelanggan'] ?? '')
        );

        $nomor = trim(
            (string) ($servis['nomor_servis'] ?? '')
        );

        $motor = trim(
            (string) ($servis['jenis_motor'] ?? '')
        );

        $plat = trim(
            (string) ($servis['nomor_polisi'] ?? '')
        );

        if ($nama === '') {
            $nama = 'Pelanggan';
        }

        if ($nomor === '') {
            $nomor = '-';
        }

        if ($motor === '') {
            $motor = '-';
        }

        if ($plat === '') {
            $plat = '-';
        }

        return
            "🏍️ *SERVISKU — BENGKEL MOTOR*\n\n" .
            "Halo, Bapak/Ibu *{$nama}*! 👋\n\n" .
            "🎉 *KABAR BAIK!*\n\n" .
            "Servis motor Anda telah *SELESAI*.\n\n" .
            "📋 *Nomor Servis:* {$nomor}\n" .
            "🏍️ *Motor:* {$motor}\n" .
            "🔢 *Nomor Polisi:* {$plat}\n" .
            "✅ *Status:* SELESAI\n\n" .
            "Motor Anda sudah siap diambil di bengkel kami.\n\n" .
            "Terima kasih telah mempercayakan motor Anda kepada *ServisKu*. 🙏";
    }
}

/* ==========================================================
   FUNGSI UTAMA KIRIM WHATSAPP
========================================================== */

if (!function_exists('skKirimWhatsApp')) {

    function skKirimWhatsApp(
        PDO $pdo,
        int $idServis,
        string $jenis,
        string $pesan
    ) {

        /* ------------------------------------------
           VALIDASI JENIS
        ------------------------------------------ */

        $jenisDiizinkan = [
            'Status Servis',
            'Estimasi Berubah',
            'Motor Siap Diambil'
        ];

        if (!in_array($jenis, $jenisDiizinkan, true)) {

            throw new RuntimeException(
                'Jenis notifikasi WhatsApp tidak valid.'
            );
        }

        /* ------------------------------------------
           VALIDASI PESAN
        ------------------------------------------ */

        $pesan = trim($pesan);

        if ($pesan === '') {

            throw new RuntimeException(
                'Pesan WhatsApp tidak boleh kosong.'
            );
        }

        $panjang = function_exists('mb_strlen')
            ? mb_strlen($pesan, 'UTF-8')
            : strlen($pesan);

        if ($panjang > 3000) {

            throw new RuntimeException(
                'Pesan WhatsApp maksimal 3000 karakter.'
            );
        }

        /* ------------------------------------------
           AMBIL DATA SERVIS
        ------------------------------------------ */

        $stmt = $pdo->prepare(
            'SELECT
                id,
                nomor_servis,
                nama_pelanggan,
                telepon,
                jenis_motor,
                nomor_polisi,
                status
             FROM servis
             WHERE id = ?
             LIMIT 1'
        );

        $stmt->execute([
            $idServis
        ]);

        $servis = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$servis) {

            throw new RuntimeException(
                'Data servis tidak ditemukan.'
            );
        }

        /* ------------------------------------------
           FORMAT NOMOR
        ------------------------------------------ */

        $nomor = skFormatNomorWa(
            $servis['telepon'] ?? ''
        );

        /* ------------------------------------------
           SIMPAN RIWAYAT AWAL
        ------------------------------------------ */

        $stmt = $pdo->prepare(
            'INSERT INTO notifikasi_wa
                (
                    id_servis,
                    telepon_tujuan,
                    jenis_notifikasi,
                    pesan,
                    status_pengiriman,
                    jumlah_percobaan
                )
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $idServis,
            $nomor,
            $jenis,
            $pesan,
            'menunggu',
            0
        ]);

        $idNotifikasi = (int) $pdo->lastInsertId('notifikasi_wa_id_seq');

        /* ------------------------------------------
           KONFIGURASI AWAL
        ------------------------------------------ */

        $sukses = false;
        $respons = '';
        $pesanHasil = '';

        $token = skAmbilTokenFonnte();

        /* ------------------------------------------
           VALIDASI TOKEN
        ------------------------------------------ */

        if ($token === '') {

            $respons =
                'Token Fonnte belum dikonfigurasi.';

            $pesanHasil =
                'Token Fonnte belum diisi. ' .
                'Periksa config/fonnte.php.';

        }

        /* ------------------------------------------
           VALIDASI CURL
        ------------------------------------------ */

        elseif (!function_exists('curl_init')) {

            $respons =
                'Ekstensi cURL PHP belum aktif.';

            $pesanHasil =
                'Ekstensi cURL belum aktif. ' .
                'Aktifkan extension=curl pada php.ini.';

        }

        /* ------------------------------------------
           KIRIM KE FONNTE
        ------------------------------------------ */

        else {

            $ch = curl_init(
                'https://api.fonnte.com/send'
            );

            if ($ch === false) {

                $respons = 'Gagal menginisialisasi cURL.';

                $pesanHasil =
                    'Gagal memulai koneksi ke Fonnte.';

            } else {

                curl_setopt_array($ch, [

                    CURLOPT_POST => true,

                    CURLOPT_POSTFIELDS => http_build_query([
                        'target' => $nomor,
                        'message' => $pesan
                    ]),

                    CURLOPT_HTTPHEADER => [
                        'Authorization: ' . $token
                    ],

                    CURLOPT_RETURNTRANSFER => true,

                    CURLOPT_CONNECTTIMEOUT => 10,

                    CURLOPT_TIMEOUT => 30,

                    CURLOPT_SSL_VERIFYPEER => true,

                    CURLOPT_SSL_VERIFYHOST => 2

                ]);

                $hasilCurl = curl_exec($ch);

                $httpCode = (int) curl_getinfo(
                    $ch,
                    CURLINFO_HTTP_CODE
                );

                $curlError = curl_error($ch);

                curl_close($ch);

                /* ----------------------------------
                   RESPONS CURL
                ---------------------------------- */

                if ($hasilCurl === false) {

                    $respons =
                        'Kesalahan cURL: ' . $curlError;

                    $pesanHasil =
                        'Gagal terhubung ke Fonnte: ' .
                        $curlError;

                } else {

                    $respons = (string) $hasilCurl;

                    $json = json_decode(
                        $respons,
                        true
                    );

                    $statusApi = is_array($json)
                        ? ($json['status'] ?? false)
                        : false;

                    $sukses =
                        $httpCode >= 200 &&
                        $httpCode < 300 &&
                        in_array(
                            $statusApi,
                            [true, 1, 'true'],
                            true
                        );

                    if ($sukses) {

                        $pesanHasil =
                            'Permintaan WhatsApp berhasil ' .
                            'diterima oleh Fonnte.';

                    } else {

                        $keteranganApi = '';

                        if (is_array($json)) {

                            $keteranganApi = (string) (
                                $json['reason']
                                ?? $json['message']
                                ?? ''
                            );
                        }

                        $pesanHasil =
                            'Pengiriman WhatsApp gagal.';

                        if ($keteranganApi !== '') {

                            $pesanHasil .=
                                ' Keterangan Fonnte: ' .
                                $keteranganApi;
                        }
                    }
                }
            }
        }

        /* ------------------------------------------
           UPDATE RIWAYAT PENGIRIMAN
        ------------------------------------------ */

        $stmt = $pdo->prepare(
            'UPDATE notifikasi_wa
             SET
                status_pengiriman = ?,
                respons_api = ?,
                jumlah_percobaan = 1,
                dikirim_pada = ?
             WHERE id = ?'
        );

        $stmt->execute([

            $sukses
                ? 'terkirim'
                : 'gagal',

            substr(
                $respons,
                0,
                5000
            ),

            $sukses
                ? date('Y-m-d H:i:s')
                : null,

            $idNotifikasi

        ]);

        /* ------------------------------------------
           HASIL
        ------------------------------------------ */

        return [

            'sukses' => $sukses,

            'pesan' => $pesanHasil,

            'id_notifikasi' => $idNotifikasi

        ];
    }
}