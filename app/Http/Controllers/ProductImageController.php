<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImageController extends Controller
{
    public function show(Product $product): StreamedResponse
    {
        abort_unless($product->image_path, 404);

        $diskName = config('filesystems.product_image_disk');
        $disk = Storage::disk($diskName);

        abort_unless($disk->exists($product->image_path), 404);

        // Read through the configured disk so private cloud objects work too.
        return $disk->response($product->image_path);
    }
}
