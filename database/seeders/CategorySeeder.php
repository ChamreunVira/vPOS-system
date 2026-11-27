<?php

namespace Database\Seeders;

use App\Enums\SoftDeleteStatus;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Food & Beverages
            ['name' => 'Drinks', 'description' => 'Water, soft drinks, juice, energy drinks and beverages.'],
            ['name' => 'Coffee & Tea', 'description' => 'Coffee, tea, instant coffee and tea products.'],
            ['name' => 'Snacks', 'description' => 'Chips, crackers, biscuits and packaged snacks.'],
            ['name' => 'Instant Food', 'description' => 'Instant noodles, canned food and ready-to-eat meals.'],
            ['name' => 'Fresh Food', 'description' => 'Fresh fruits, vegetables and other fresh food products.'],
            ['name' => 'Frozen Food', 'description' => 'Frozen meat, seafood, vegetables and ready-to-cook food.'],
            ['name' => 'Dairy Products', 'description' => 'Milk, yogurt, cheese, butter and dairy products.'],
            ['name' => 'Bakery', 'description' => 'Bread, cakes, pastries and bakery products.'],
            ['name' => 'Cooking Ingredients', 'description' => 'Sauces, cooking oil, flour, salt and other ingredients.'],
            ['name' => 'Spices & Seasonings', 'description' => 'Spices, herbs, seasoning powders and flavor enhancers.'],
            ['name' => 'Canned Food', 'description' => 'Canned fish, meat, vegetables and preserved food.'],

            // Personal Care
            ['name' => 'Personal Care', 'description' => 'Daily personal hygiene and body care products.'],
            ['name' => 'Hair Care', 'description' => 'Shampoo, conditioner, hair oil and hair styling products.'],
            ['name' => 'Skin Care', 'description' => 'Facial cleansers, moisturizers, sunscreen and skincare products.'],
            ['name' => 'Oral Care', 'description' => 'Toothpaste, toothbrushes, mouthwash and dental products.'],
            ['name' => 'Health & Wellness', 'description' => 'General wellness and non-prescription health products.'],

            // Household
            ['name' => 'Household Products', 'description' => 'Daily household supplies and essential home products.'],
            ['name' => 'Cleaning Supplies', 'description' => 'Detergent, dishwashing liquid, floor cleaner and disinfectants.'],
            ['name' => 'Kitchen Supplies', 'description' => 'Kitchen utensils, storage containers and cooking accessories.'],
            ['name' => 'Laundry', 'description' => 'Laundry detergent, fabric softener and washing products.'],
            ['name' => 'Paper Products', 'description' => 'Tissues, toilet paper, paper towels and napkins.'],

            // Baby & Family
            ['name' => 'Baby Products', 'description' => 'Baby diapers, wipes, baby food and baby care products.'],
            ['name' => 'Baby Food & Milk', 'description' => 'Infant formula, baby cereal and baby food products.'],

            // Other
            ['name' => 'Stationery', 'description' => 'Pens, notebooks, paper and office supplies.'],
            ['name' => 'Electronics & Accessories', 'description' => 'Chargers, cables, batteries and small electronic accessories.'],
            ['name' => 'Pet Supplies', 'description' => 'Pet food, pet accessories and animal care products.'],
            ['name' => 'Tobacco Products', 'description' => 'Cigarettes and other tobacco-related retail products.'],
            ['name' => 'Miscellaneous', 'description' => 'Products that do not belong to other categories.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'status' => SoftDeleteStatus::Active->value,
                ]
            );
        }
    }
}
