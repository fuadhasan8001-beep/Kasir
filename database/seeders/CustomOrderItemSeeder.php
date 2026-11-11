<?php

namespace Database\Seeders;

use App\Models\CustomOrder;
use Illuminate\Database\Seeder;

class CustomOrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CustomOrder::query()->with('items')->each(function (CustomOrder $order): void {
            if ($order->items->isNotEmpty()) {
                return;
            }

            $order->items()->create([
                'product_id' => $order->product_id,
                'product_name' => $order->product_name,
                'quantity' => $order->quantity,
                'stock_basis' => $order->stock_basis,
                'stock_unit' => $order->stock_unit,
                'estimated_weight' => $order->estimated_weight,
                'actual_weight' => $order->actual_weight,
                'price_basis' => $order->price_basis,
                'agreed_price' => $order->agreed_price,
                'total_price' => $order->total_price,
                'specifications' => $order->specifications,
                'diameter' => $order->diameter,
                'shape' => $order->shape,
                'notes' => $order->notes,
            ]);
        });
    }
}
