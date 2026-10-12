<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $appAccess = Permission::updateOrCreate(
            ['name' => 'app.access'],
            ['label' => 'Access application pages'],
        );

        $roles = [
            'super_admin' => 'Super Admin',
            'staff_gudang' => 'Staff Gudang',
            'pic_outlet' => 'PIC Outlet',
            'management' => 'Management',
            'purchasing' => 'Purchasing',
            'finance' => 'Finance',
        ];

        foreach ($roles as $name => $label) {
            $role = Role::firstOrCreate(['name' => $name], ['label' => $label]);
            if ($role->wasRecentlyCreated) {
                $role->permissions()->attach($appAccess->id);
            }
        }

        $userManagement = Permission::updateOrCreate(
            ['name' => 'users.manage'],
            ['label' => 'Manage users and roles'],
        );

        Role::where('name', 'super_admin')->firstOrFail()
            ->permissions()->syncWithoutDetaching([$userManagement->id]);

        Role::where('name', '!=', 'super_admin')->each(
            fn (Role $role) => $role->permissions()->detach($userManagement->id),
        );

        $stockManagement = Permission::updateOrCreate(
            ['name' => 'stock.manage'],
            ['label' => 'Record stock movements'],
        );
        Role::whereIn('name', ['super_admin', 'staff_gudang'])->each(
            fn (Role $role) => $role->permissions()->syncWithoutDetaching([$stockManagement->id]),
        );
        Role::whereNotIn('name', ['super_admin', 'staff_gudang'])->each(
            fn (Role $role) => $role->permissions()->detach($stockManagement->id),
        );

        $opnameManage = Permission::updateOrCreate(['name' => 'opname.manage'], ['label' => 'Create and count stock opname']);
        Role::whereIn('name', ['super_admin', 'staff_gudang'])->each(
            fn (Role $role) => $role->permissions()->syncWithoutDetaching([$opnameManage->id]),
        );
        Role::whereNotIn('name', ['super_admin', 'staff_gudang'])->each(
            fn (Role $role) => $role->permissions()->detach($opnameManage->id),
        );

        $opnameApprove = Permission::updateOrCreate(['name' => 'opname.approve'], ['label' => 'Review and approve stock opname']);
        Role::whereIn('name', ['super_admin', 'purchasing'])->each(
            fn (Role $role) => $role->permissions()->syncWithoutDetaching([$opnameApprove->id]),
        );
        Role::whereNotIn('name', ['super_admin', 'purchasing'])->each(
            fn (Role $role) => $role->permissions()->detach($opnameApprove->id),
        );

        $adjustmentManage = Permission::updateOrCreate(['name' => 'adjust.manage'], ['label' => 'Manage stock adjustments']);
        Role::where('name', 'super_admin')->firstOrFail()->permissions()->syncWithoutDetaching([$adjustmentManage->id]);
        Role::where('name', '!=', 'super_admin')->each(fn (Role $role) => $role->permissions()->detach($adjustmentManage->id));

        $purchasingManage = Permission::updateOrCreate(['name' => 'purchasing.manage'], ['label' => 'Create purchase requests and orders']);
        Role::whereIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$purchasingManage->id]));
        Role::whereNotIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->detach($purchasingManage->id));

        $transferManage = Permission::updateOrCreate(['name' => 'transfer.manage'], ['label' => 'Dispatch stock transfers']);
        Role::whereIn('name', ['super_admin', 'staff_gudang'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$transferManage->id]));
        Role::whereNotIn('name', ['super_admin', 'staff_gudang'])->each(fn (Role $role) => $role->permissions()->detach($transferManage->id));

        $transferReceive = Permission::updateOrCreate(['name' => 'transfer.receive'], ['label' => 'Receive stock transfers at outlets']);
        Role::whereIn('name', ['super_admin', 'pic_outlet'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$transferReceive->id]));
        Role::whereNotIn('name', ['super_admin', 'pic_outlet'])->each(fn (Role $role) => $role->permissions()->detach($transferReceive->id));

        $purchasingApprove = Permission::updateOrCreate(['name' => 'purchasing.approve'], ['label' => 'Approve or reject purchase requests']);
        Role::where('name', 'super_admin')->firstOrFail()->permissions()->syncWithoutDetaching([$purchasingApprove->id]);
        Role::where('name', '!=', 'super_admin')->each(fn (Role $role) => $role->permissions()->detach($purchasingApprove->id));

        $reportsView = Permission::updateOrCreate(['name' => 'reports.view'], ['label' => 'View inventory and operational reports']);
        Role::whereIn('name', ['super_admin', 'staff_gudang', 'pic_outlet', 'management', 'purchasing', 'finance'])
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$reportsView->id]));

        $lowStockView = Permission::updateOrCreate(['name' => 'low_stock.view'], ['label' => 'View low stock alerts and history']);
        Role::whereIn('name', ['super_admin', 'staff_gudang', 'pic_outlet'])
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$lowStockView->id]));
        Role::whereNotIn('name', ['super_admin', 'staff_gudang', 'pic_outlet'])
            ->each(fn (Role $role) => $role->permissions()->detach($lowStockView->id));

        $lowStockManage = Permission::updateOrCreate(['name' => 'low_stock.manage'], ['label' => 'Manage per-location minimum stock thresholds']);
        Role::where('name', 'super_admin')->firstOrFail()->permissions()->syncWithoutDetaching([$lowStockManage->id]);
        Role::where('name', '!=', 'super_admin')->each(fn (Role $role) => $role->permissions()->detach($lowStockManage->id));

        $whatsappManage = Permission::updateOrCreate(['name' => 'whatsapp.manage'], ['label' => 'Configure WhatsApp low stock alerts']);
        Role::where('name', 'super_admin')->firstOrFail()->permissions()->syncWithoutDetaching([$whatsappManage->id]);
        Role::where('name', '!=', 'super_admin')->each(fn (Role $role) => $role->permissions()->detach($whatsappManage->id));

    }
}
