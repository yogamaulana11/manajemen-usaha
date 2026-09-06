<?php
/**
 * Return nav-here if current path begins with this path.
 *
 * @param string $path
 * @return string
 */
if (!function_exists('setActive')) {
    function setActive($path)
    {
        return Request::is($path . '*') ? ' active' :  '';
    }
}

if (!function_exists('rupiah')) {
    function rupiah($angka)
    {
        $hasil_rupiah = "Rp. " . number_format($angka, 2, ',', '.');
        return $hasil_rupiah;
    }
}

