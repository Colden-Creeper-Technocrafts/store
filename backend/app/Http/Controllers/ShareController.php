<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

class ShareController extends Controller
{
    public function product(string $slug): Response
    {
        $product = Product::where('sku', $slug)->first();

        $name    = e($product?->name ?? 'Check out this product');
        $desc    = e($product?->short_description ?? $product?->name ?? '');
        $rawImg  = $product?->image ?? '';
        $image   = $rawImg
            ? (str_starts_with($rawImg, 'http') ? e($rawImg) : e(rtrim(config('app.url'), '/') . '/' . ltrim($rawImg, '/')))
            : '';

        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $spaUrl      = $frontendUrl . '/product/' . rawurlencode($slug);
        $spaUrlSafe  = e($spaUrl);
        $spaUrlJs    = json_encode($spaUrl);

        $ogImageTags = $image
            ? "  <meta property=\"og:image\" content=\"{$image}\">\n  <meta name=\"twitter:image\" content=\"{$image}\">"
            : '';

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{$name}</title>
  <meta property="og:title" content="{$name}">
  <meta property="og:description" content="{$desc}">
{$ogImageTags}
  <meta property="og:url" content="{$spaUrlSafe}">
  <meta property="og:type" content="product">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{$name}">
  <meta http-equiv="refresh" content="0;url={$spaUrlSafe}">
</head>
<body>
  <script>window.location.replace({$spaUrlJs});</script>
  <a href="{$spaUrlSafe}">{$name}</a>
</body>
</html>
HTML;

        return response()->make($html, 200, ['Content-Type' => 'text/html']);
    }
}
