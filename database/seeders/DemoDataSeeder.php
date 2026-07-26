<?php

namespace Database\Seeders;

use App\Models\Consumable;
use App\Models\CustomerPayment;
use App\Models\DeliveryOrder;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Sample staff, catalog, and customer data for local development and demos only.
     * Never run in production — the real admin account comes from AdminUserSeeder.
     */
    public function run(): void
    {
        $staffUser = User::factory()->create([
            'name' => 'Shaunti Staff',
            'email' => 'staff@shaunti.test',
        ]);
        $staffUser->assignRole('staff');
        Staff::factory()->create([
            'user_id' => $staffUser->id,
            'employee_id' => 'EMP-0001',
            'position' => 'Front Desk',
        ]);

        $products = collect([
            ['name' => 'Round Slim Refill', 'category' => 'refill', 'size' => '5 Gallon', 'unit_price' => 30, 'stock_quantity' => 48],
            ['name' => 'Round Standard Refill', 'category' => 'refill', 'size' => '5 Gallon', 'unit_price' => 25, 'stock_quantity' => 62],
            ['name' => 'Alkaline Water', 'category' => 'alkaline', 'size' => '5 Gallon', 'unit_price' => 45, 'stock_quantity' => 34],
            ['name' => 'Distilled Water', 'category' => 'distilled', 'size' => '5 Gallon', 'unit_price' => 35, 'stock_quantity' => 7, 'low_stock_threshold' => 10],
            ['name' => 'Purified Water', 'category' => 'purified', 'size' => '5 Gallon', 'unit_price' => 28, 'stock_quantity' => 55],
            ['name' => 'Mineral Water', 'category' => 'mineral', 'size' => '1 Liter', 'unit_price' => 12, 'stock_quantity' => 120],
            ['name' => 'Mineral Water', 'category' => 'mineral', 'size' => '500ml', 'unit_price' => 8, 'stock_quantity' => 85],
            ['name' => 'Container Deposit', 'category' => 'container', 'size' => 'Standard', 'unit_price' => 150, 'stock_quantity' => 999, 'low_stock_threshold' => 50],
        ])->map(fn (array $data) => Product::create(['low_stock_threshold' => 10, 'is_active' => true, ...$data]));

        $aquaParts = Supplier::firstOrCreate(['name' => 'AquaParts Supply']);
        $packWell = Supplier::firstOrCreate(['name' => 'PackWell Trading']);
        $chemSure = Supplier::firstOrCreate(['name' => 'ChemSure Inc.']);

        Consumable::create(['name' => 'Carbon Filter', 'category' => 'water_filters', 'unit' => 'pcs', 'quantity' => 40, 'low_stock_threshold' => 10, 'unit_cost' => 350, 'supplier_id' => $aquaParts->id]);
        Consumable::create(['name' => 'UV Lamp', 'category' => 'uv_lamps', 'unit' => 'pcs', 'quantity' => 3, 'low_stock_threshold' => 5, 'unit_cost' => 1200, 'supplier_id' => $aquaParts->id]);
        Consumable::create(['name' => 'Bottle Caps', 'category' => 'bottle_caps', 'unit' => 'packs', 'quantity' => 80, 'low_stock_threshold' => 20, 'unit_cost' => 90, 'supplier_id' => $packWell->id]);
        Consumable::create(['name' => 'Chlorine Tablets', 'category' => 'cleaning_supplies', 'unit' => 'kg', 'quantity' => 15, 'low_stock_threshold' => 5, 'unit_cost' => 220, 'supplier_id' => $chemSure->id]);

        MaintenanceLog::create(['equipment_name' => 'RO Machine #1', 'category' => 'ro_membrane', 'last_maintenance_date' => now()->subMonths(2), 'next_due_date' => now()->addDays(20), 'status' => 'ok']);
        MaintenanceLog::create(['equipment_name' => 'UV Sterilizer', 'category' => 'uv_lamp', 'last_maintenance_date' => now()->subMonths(4), 'next_due_date' => now()->subDays(5), 'status' => 'overdue']);

        $demoCustomer = User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'customer@shaunti.test',
            'contact_number' => '+63 917 123 4567',
            'address' => 'Purok 3, San Roque, Bato, Camarines Sur',
            'credit_balance' => 150,
        ]);
        $demoCustomer->assignRole('customer');

        DeliveryOrder::create([
            'order_code' => 'DEL-'.now()->format('Ymd').'-001',
            'customer_id' => $demoCustomer->id,
            'customer_name' => $demoCustomer->name,
            'customer_address' => $demoCustomer->address,
            'product_id' => $products->first()->id,
            'product_name' => $products->first()->name,
            'quantity' => 2,
            'unit_price' => $products->first()->unit_price,
            'total_amount' => $products->first()->unit_price * 2,
            'status' => 'pending',
            'preferred_delivery_date' => now()->addDays(2),
            'placed_by' => $demoCustomer->id,
        ]);

        CustomerPayment::create([
            'customer_id' => $demoCustomer->id,
            'customer_name' => $demoCustomer->name,
            'amount' => 50,
            'payment_date' => now()->subDays(5)->toDateString(),
            'recorded_by' => $staffUser->id,
            'staff_name' => $staffUser->name,
        ]);
    }
}
