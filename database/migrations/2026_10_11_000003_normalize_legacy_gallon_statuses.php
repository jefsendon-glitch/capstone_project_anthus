<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\GallonStock;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('gallon_stocks')->whereIn('status', ['company_owned', 'returned'])->orderBy('id')->each(function (object $legacy): void {
            $empty = DB::table('gallon_stocks')->where(['product_id' => $legacy->product_id, 'status' => 'empty'])->first();
            if ($empty) {
                DB::table('gallon_stocks')->where('id', $empty->id)->increment('quantity', $legacy->quantity);
                DB::table('stock_movements')
                    ->where('movable_type', GallonStock::class)
                    ->where('movable_id', $legacy->id)
                    ->update(['movable_id' => $empty->id]);
            } else {
                DB::table('gallon_stocks')->where('id', $legacy->id)->update(['status' => 'empty']);
                return;
            }
            DB::table('gallon_stocks')->where('id', $legacy->id)->delete();
        });
    }

    public function down(): void
    {
        // Historical legacy buckets cannot be reconstructed without losing data.
    }
};
