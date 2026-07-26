<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use App\Models\GallonStock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    private const ITEM_TYPES = [
        'product' => Product::class,
        'consumable' => Consumable::class,
        'gallon_stock' => GallonStock::class,
    ];

    public function index(): View
    {
        $this->authorize('viewAny', StockMovement::class);

        $itemType = request('item_type');
        $type = request('type');
        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        $movements = StockMovement::with(['movable', 'recordedBy'])
            ->when($itemType && isset(self::ITEM_TYPES[$itemType]), fn ($q) => $q->where('movable_type', self::ITEM_TYPES[$itemType]))
            ->when($type, fn ($q) => $q->ofType($type))
            ->between($dateFrom, $dateTo)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.stock-movements.index', [
            'movements' => $movements,
            'itemType' => $itemType,
            'type' => $type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
