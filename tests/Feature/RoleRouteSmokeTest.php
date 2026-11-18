<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_registered_http_route_responds_without_server_error_for_each_role(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $warehouse = Location::create(['code' => 'WH-SMOKE', 'name' => 'Gudang Smoke', 'type' => 'warehouse', 'is_active' => true]);
        $outlet = Location::create(['code' => 'OT-SMOKE', 'name' => 'Outlet Smoke', 'type' => 'outlet', 'is_active' => true]);
        $locations = [
            'staff_gudang' => $warehouse,
            'staff_outlet' => $outlet,
            'pic_outlet' => $outlet,
            'pic_warehouse' => $warehouse,
        ];

        $routes = collect(Route::getRoutes())
            ->filter(fn (RoutingRoute $route) => in_array('auth', $route->gatherMiddleware(), true))
            ->reject(fn (RoutingRoute $route) => $route->getName() === 'logout')
            ->values();

        foreach (['super_admin', 'staff_gudang', 'staff_outlet', 'pic_outlet', 'pic_warehouse', 'management', 'purchasing', 'finance'] as $roleName) {
            $user = User::factory()->create(['location_id' => $locations[$roleName]->id ?? null]);
            $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());
            $this->actingAs($user);

            foreach ($routes as $route) {
                $path = preg_replace_callback('/\{[^}]+\}/', fn () => '999999', '/'.ltrim($route->uri(), '/'));
                foreach (array_intersect($route->methods(), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']) as $method) {
                    $response = $this->call($method, $path);
                    $this->assertLessThan(500, $response->getStatusCode(), sprintf('%s %s failed for role %s with HTTP %s: %s', $method, $path, $roleName, $response->getStatusCode(), str($response->getContent())->stripTags()->limit(500)));
                }
            }
        }
    }
}
