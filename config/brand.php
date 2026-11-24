<?php
/**
 * Helper identitas brand/toko.
 * Aman dipanggil dari halaman admin maupun user.
 */

if (!function_exists('get_brand_settings')) {
    function get_brand_settings($koneksi) {
        $result = mysqli_query($koneksi, "SELECT * FROM settings LIMIT 1");
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }

        return [
            'nama_toko' => 'Budiarti Risol',
            'slogan' => 'Risol Lezat, Setiap Saat',
            'no_hp' => '',
            'alamat' => '',
            'logo' => '',
            'favicon' => ''
        ];
    }
}

if (!function_exists('brand_asset_url')) {
    function brand_asset_url($filename) {
        if (empty($filename)) {
            return '';
        }
        return '../assets/uploads/brand/' . rawurlencode(basename($filename));
    }
}
