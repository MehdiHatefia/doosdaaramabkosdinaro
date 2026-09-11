<?php

namespace Tests\Feature;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_locations_schema_and_self_referencing_relationship_are_ready(): void
    {
        $connection = DB::connection()->getName();
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type = 'table'");
        Log::info('Database schema diagnostic started', [
            'function' => __METHOD__,
            'user_id' => null,
            'payload' => ['connection' => $connection, 'tables' => array_map(fn ($table) => $table->name, $tables)],
            'trace' => null,
        ]);

        $this->assertTrue(Schema::hasTable('locations'));
        $this->assertTrue(Schema::hasColumns('locations', ['id', 'parent_id', 'name', 'slug']));
        $this->assertTrue(Schema::hasColumn('advertisements', 'location_id'));

        $province = Location::factory()->create([
            'parent_id' => null,
            'name' => 'استان تشخیصی',
            'slug' => 'diagnostic-province',
        ]);
        $city = Location::factory()->create([
            'parent_id' => $province->id,
            'name' => 'شهر تشخیصی',
            'slug' => 'diagnostic-city',
        ]);

        $this->assertTrue($province->children->contains($city));
        $this->assertTrue($city->parent->is($province));

        Log::info('Database schema diagnostic completed', [
            'function' => __METHOD__,
            'user_id' => null,
            'payload' => [
                'locations_table' => true,
                'location_columns' => ['id', 'parent_id', 'name', 'slug'],
                'advertisements_location_id' => true,
                'province_id' => $province->id,
                'city_id' => $city->id,
            ],
            'trace' => null,
        ]);
    }
}