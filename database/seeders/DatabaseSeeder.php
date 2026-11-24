<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data. Every product, price and account here is fictional.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Store Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);

        User::create([
            'name' => 'Demo Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $catalog = [
            'Electronics' => [
                ['Wireless Headphones', 8990, 6990, 'Over-ear, 30 hour battery'],
                ['Smart Watch', 14990, null, 'Heart rate, GPS and sleep tracking'],
                ['Portable Speaker', 5990, null, 'Waterproof Bluetooth speaker'],
                ['USB-C Charger 65W', 3490, 2990, 'Fast charging for laptop and phone'],
            ],
            'Home & Kitchen' => [
                ['Ceramic Coffee Mug Set', 2490, null, 'Set of four, dishwasher safe'],
                ['Cast Iron Skillet', 3990, null, 'Pre-seasoned 12 inch skillet'],
                ['Linen Throw Blanket', 4590, 3690, 'Soft and breathable'],
                ['Desk Lamp', 2990, null, 'Dimmable LED with USB port'],
            ],
            'Clothing' => [
                ['Cotton T-Shirt', 1990, null, 'Classic fit, 100% cotton'],
                ['Denim Jacket', 7990, 5990, 'Medium wash, relaxed fit'],
                ['Running Shoes', 9990, null, 'Lightweight with extra cushioning'],
                ['Wool Beanie', 1790, null, 'Warm knit hat'],
            ],
            'Sports & Outdoors' => [
                ['Yoga Mat', 2790, null, 'Non-slip, 6mm thick'],
                ['Insulated Water Bottle', 2290, null, 'Keeps drinks cold for 24 hours'],
                ['Camping Backpack 40L', 8490, 7490, 'Water resistant with rain cover'],
                ['Resistance Bands', 1590, null, 'Set of five levels'],
            ],
        ];

        $order = 1;
        $sku = 1000;

        foreach ($catalog as $name => $products) {
            $category = Category::create(['name' => $name, 'sort_order' => $order++, 'description' => "Browse our {$name} selection."]);

            foreach ($products as $i => [$title, $price, $sale, $short]) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => $title,
                    'sku' => 'SKU-'.(++$sku),
                    'short_description' => $short,
                    'description' => "{$short}.\n\nThis is a demo product. Replace it with your own from the admin panel.",
                    'price' => $price,
                    'sale_price' => $sale,
                    'stock' => 25,
                    'is_featured' => $i < 2,
                ]);
            }
        }

        Coupon::create(['code' => 'WELCOME10', 'type' => Coupon::PERCENT, 'value' => 10]);
        Coupon::create(['code' => 'SAVE5', 'type' => Coupon::FIXED, 'value' => 500, 'min_subtotal' => 3000]);
    }
}
