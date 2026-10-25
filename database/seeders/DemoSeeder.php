<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo data may only be seeded outside production.');
        }
        User::query()->firstOrCreate(['email' => 'demo@rightmo.test'], ['name' => 'Alex Morgan', 'password' => 'RightmoDemo!2026']);
        $groups = [
            'Audio' => [['Studio Wireless Headphones', 129.00, 4.8], ['Pocket Bluetooth Speaker', 49.00, 4.6], ['Everyday Earbuds', 79.00, 4.7], ['Studio Monitor Speaker', 189.00, 4.9], ['Travel Noise-Canceling Headphones', 159.00, 4.5], ['Mini Desk Speaker', 35.00, 4.2]],
            'Workspace' => [['Mechanical Keyboard', 119.00, 4.9], ['Wireless Desk Mouse', 45.00, 4.6], ['Aluminum Laptop Stand', 59.00, 4.8], ['Minimal Desk Lamp', 69.00, 4.7], ['Wool Desk Mat', 29.00, 4.4], ['USB-C Dock', 89.00, 4.5]],
            'Lifestyle' => [['Everyday Backpack', 95.00, 4.8], ['Insulated Travel Bottle', 32.00, 4.7], ['Canvas Weekender', 125.00, 4.9], ['Pocket Notebook', 12.00, 4.3], ['Ceramic Coffee Cup', 24.00, 4.6], ['Compact Umbrella', 28.00, 4.2]],
            'Wearables' => [['Essential Smartwatch', 199.00, 4.8], ['Activity Band', 65.00, 4.5], ['Sport Watch', 149.00, 4.7], ['Classic Digital Watch', 89.00, 4.6], ['Fitness Tracker', 55.00, 4.4], ['Everyday Timepiece', 109.00, 4.9]],
        ];
        foreach ($groups as $name => $items) {
            $category = Category::query()->firstOrCreate(['name' => $name]);
            foreach ($items as [$title,$price,$rating]) {
                $product = Product::query()->firstOrCreate(['name' => $title], ['description' => 'Thoughtfully designed for your everyday. '.$title.' combines considered materials, reliable performance, and a clean, understated finish. A practical addition to your collection, built to feel at home wherever your day takes you.', 'category_id' => $category->id, 'price' => $price, 'rating' => $rating, 'image_path' => null]);
                if ($product->wasRecentlyCreated) {
                    $productPath = 'products/demo-'.$product->id.'.svg';
                    Storage::disk('public')->put($productPath, File::get(database_path('seeders/images/'.strtolower($name).'.svg')));
                    $product->update(['image_path' => $productPath]);
                }
            }
        }
    }
}
