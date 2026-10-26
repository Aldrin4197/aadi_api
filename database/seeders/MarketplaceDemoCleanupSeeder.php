<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\ServiceListing;
use App\Models\User;
use App\Models\VehicleListing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Removes only the rows created by MarketplaceDemoSeeder. */
class MarketplaceDemoCleanupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $ids = fn (int $start) => array_map(fn ($n) => $this->id($start + $n), range(0, 4));

            Listing::whereIn('id', $ids(10))->delete();
            VehicleListing::withTrashed()->whereIn('id', $ids(20))->forceDelete();
            ServiceListing::withTrashed()->whereIn('id', $ids(30))->forceDelete();
            User::where('id', $this->id(0))->delete();
        });

        $this->command?->info('Marketplace demo data removed.');
    }

    private function id(int $number): string
    {
        return str_pad('01JAD1DEM', 20, '0').str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}