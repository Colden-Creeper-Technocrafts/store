<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ShareController extends Controller
{
    public function product(string $slug): Response
    {
        // Mirror StorefrontRepository: join default variant → primary image
        $product = DB::table('products')
            ->leftJoin('product_variants as pv', function ($join) {
                $join->on('pv.id', '=', DB::raw(
                    '(SELECT id FROM product_variants WHERE product_id = products.id ORDER BY is_default DESC, id ASC LIMIT 1)'
                ));
            })
            ->leftJoin('product_images', function ($join) {
                $join->on('product_images.product_variant_id', '=', 'pv.id')
                    ->where('product_images.is_primary', true);
            })
            ->select(['products.name', 'products.short_description', 'product_images.image'])
            ->where(function ($q) use ($slug) {
                $q->where('products.slug', $slug)->orWhere('products.sku', $slug);
            })
            ->first();

        $name   = e($product?->name ?? 'Check out this product');
        $desc   = e($product?->short_description ?? $product?->name ?? '');
        $rawImg = $product?->image ?? '';
        $image  = $rawImg ? e(asset('storage/' . ltrim($rawImg, '/'))) : '';

        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $spaUrl      = $frontendUrl . '/product/' . rawurlencode($slug);
        $spaUrlSafe  = e($spaUrl);
        $spaUrlJs    = json_encode($spaUrl);

        $ogImageTags = $image
            ? "  <meta property=\"og:image\" content=\"{$image}\">\n  <meta property=\"og:image:width\" content=\"1200\">\n  <meta name=\"twitter:image\" content=\"{$image}\">"
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
