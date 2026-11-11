<?php

/*
 * Only the keys set here override barryvdh/laravel-dompdf's defaults (vendor/barryvdh/laravel-dompdf/config/dompdf.php).
 *
 * public_path: dompdf resolves relative asset paths against it and throws "Cannot resolve public path" when the
 * folder does not exist. Servers that serve the app from a different web root (e.g. public_html) have no
 * base_path('public'), so fall back to the app root. Set DOMPDF_PUBLIC_PATH to point it at the real web root.
 */
$dompdfPublicPath = env('DOMPDF_PUBLIC_PATH');

if (!$dompdfPublicPath || !is_dir($dompdfPublicPath)) {
    $dompdfPublicPath = is_dir(public_path()) ? public_path() : base_path();
}

return [
    'public_path' => $dompdfPublicPath,
];
