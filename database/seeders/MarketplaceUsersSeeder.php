<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Fictional accounts and offers for testing each provider's own workspace. */
class MarketplaceUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('aadi_demo.password') ?: Str::random(24);
        if (!is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('AADI_DEMO_PASSWORD must contain at least 12 characters, or be left empty to generate one.');
        }
        $market = app(Marketplace::class);
        $accounts = DB::transaction(function () use ($market, $password): array {
            $accounts = [];
            foreach ($this->people() as $number => [$name, $email, $roles, $city, $lat, $lng]) {
                $user = User::find($this->id($number));
                $created = $user === null;
                if ($created) {
                    if (User::where('email', $email)->exists()) {
                        throw new RuntimeException('Demo email already belongs to another account: '.$email.'. No records were changed.');
                    }
                    $user = new User;
                    $user->forceFill([
                        'id'=>$this->id($number), 'name'=>'[Demo] '.$name, 'email'=>$email,
                        'password'=>$password, 'email_verified_at'=>now(), 'is_admin'=>false,
                        'landlord_status'=>in_array('spaces', $roles, true) ? 'approved' : 'none',
                        'vehicle_owner_status'=>in_array('haul', $roles, true) ? 'approved' : 'none',
                        'service_provider_status'=>in_array('raketeer', $roles, true) ? 'approved' : 'none',
                    ])->save();
                }

                $accounts[] = [$user->email, implode(', ', $roles), $created ? 'Created' : 'Preserved'];
                foreach ($roles as $roleIndex => $service) {
                    // Respect a role revoked after the initial seed; never approve it again.
                    if (!$market->approved($user, $service)) continue;
                    $count = count($roles) === 1 ? 3 : 1;
                    for ($index = 0; $index < $count; $index++) {
                        $model = $market->model($service);
                        $ownerField = $service === 'raketeer' ? 'provider_id' : 'owner_id';
                        $query = $model::query();
                        if ($service !== 'spaces') $query->withTrashed();
                        $id = $this->id(100 + $number * 10 + $roleIndex * 3 + $index);
                        $existing = $query->find($id);
                        if ($existing) {
                            if ((string) $existing->{$ownerField} !== (string) $user->id) {
                                throw new RuntimeException('A reserved demo listing ID belongs to another user. No records were changed.');
                            }
                            continue;
                        }
                        $fields = $this->offer($service, $index, $number, $city, $lat, $lng);
                        (new $model)->forceFill(['id'=>$id, $ownerField=>$user->id, ...$fields,
                            ...($service === 'spaces' ? ['owner_name'=>$user->name] : []),
                        ])->save();
                    }
                }
            }
            return $accounts;
        });

        $this->command?->info('Demo users and their listings are ready. Existing records and passwords were preserved.');
        $this->command?->table(['Email', 'Provider roles', 'Account'], $accounts);
        if (collect($accounts)->contains(fn ($account) => $account[2] === 'Created')) {
            $this->command?->info('Password for newly created demo accounts: '.$password);
        }
    }

    private function people(): array
    {
        return [
            1 => ['Maria Santos', 'maria@users.aadi.example', ['spaces'], 'Catbalogan', 11.7753, 124.8861],
            2 => ['Pedro Reyes', 'pedro@users.aadi.example', ['spaces'], 'Calbayog', 12.0668, 124.5962],
            3 => ['Carlo Cruz', 'carlo@users.aadi.example', ['haul'], 'Catbalogan', 11.7770, 124.8880],
            4 => ['Ana Garcia', 'ana@users.aadi.example', ['haul'], 'Calbayog', 12.0690, 124.5980],
            5 => ['Luis Ramos', 'luis@users.aadi.example', ['raketeer'], 'Catbalogan', 11.7740, 124.8850],
            6 => ['Leah Tan', 'leah@users.aadi.example', ['raketeer'], 'Tacloban', 11.2447, 125.0038],
            7 => ['Alex Rivera', 'alex@users.aadi.example', ['spaces', 'haul', 'raketeer'], 'Cebu City', 10.3167, 123.8907],
        ];
    }

    private function offer(string $service, int $index, int $person, string $city, float $lat, float $lng): array
    {
        $lat += $index * 0.0012;
        $lng += $index * 0.0010;
        $base = [
            'currency'=>'PHP',
            'description'=>'DEMO LISTING — fictional offer belonging to a demo account. Locations, prices and features are illustrative. Not available for booking.',
        ];
        if ($service === 'spaces') {
            [$title, $type, $rate, $amenities] = [
                ['Studio with a workspace', 'apartment', 6500, ['Desk', 'Kitchen']],
                ['Student bedspace', 'bedspace', 2500, ['Shared kitchen', 'Study area']],
                ['Two-bedroom family home', 'house', 12000, ['Two bedrooms', 'Parking area']],
            ][$index];
            return [...$base, 'title'=>'[Sample] '.$title.' — '.$city,
                'category'=>'space_rental', 'type'=>$type, 'price'=>$rate + $person * 100,
                'status'=>'active', 'city'=>$city, 'address'=>'Sample location, '.$city,
                'lat'=>$lat, 'lng'=>$lng, 'photos'=>[], 'amenities'=>$amenities, 'property_verification'=>'none'];
        }
        if ($service === 'haul') {
            [$title, $type, $brand, $model, $rate, $seats] = [
                ['Passenger van', 'van', 'Toyota', 'Hiace', 3200, 15],
                ['Light moving truck', 'truck', 'Isuzu', 'Traviz', 2600, 3],
                ['City scooter', 'motorcycle', 'Honda', 'Click', 500, 2],
            ][$index];
            return [...$base, 'title'=>'[Sample] '.$title.' — '.$city,
                'vehicle_type'=>$type, 'brand'=>$brand, 'model'=>$model, 'year'=>2022,
                'seating_capacity'=>$seats, 'price_per_day'=>$rate + $person * 50,
                'status'=>'available', 'location'=>'Sample pickup point, '.$city,
                'latitude'=>$lat, 'longitude'=>$lng, 'images'=>[], 'features'=>[]];
        }
        [$title, $type, $rate, $unit, $skills] = [
            ['Home cleaning visit', 'homeCleaning', 900, 'fixed', ['Home cleaning']],
            ['Computer setup and support', 'itSupport', 700, 'fixed', ['Computer setup']],
            ['Math tutoring session', 'tutoringService', 300, 'hourly', ['Mathematics']],
        ][$index];
        return [...$base, 'title'=>'[Sample] '.$title.' — '.$city,
            'service_type'=>$type, 'price_amount'=>$rate + $person * 20, 'price_type'=>$unit,
            'status'=>'available', 'service_area'=>'Sample service area, '.$city,
            'latitude'=>$lat, 'longitude'=>$lng, 'images'=>[], 'skills_required'=>$skills];
    }

    private function id(int $number): string
    {
        // Distinct from MarketplaceDemoSeeder's identifiers; stable across runs.
        return str_pad('01JAD1ACC', 20, '0').str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
