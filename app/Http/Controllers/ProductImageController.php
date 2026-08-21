<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImageController extends Controller
{
    public function show(Product $product): BinaryFileResponse|RedirectResponse
    {
        abort_unless($product->image_path, 404);

        $diskName = config('filesystems.product_image_disk');
        $disk = Storage::disk($diskName);

        abort_unless($disk->exists($product->image_path), 404);

        if (config("filesystems.disks.{$diskName}.driver") !== 'local') {
            return redirect()->away($disk->url($product->image_path));
        }

        return response()->file($disk->path($product->image_path));
    }
}
