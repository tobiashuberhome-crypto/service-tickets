<?php

namespace Database\Seeders;

use App\Models\ServiceDefault;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['NM-Klein', 'NM-Service', 'VDE'] as $ref) {
            ServiceDefault::query()->updateOrCreate(
                ['product_ref' => $ref],
                ['label' => $ref, 'quantity' => 1, 'active' => true]
            );
        }
    }
}
