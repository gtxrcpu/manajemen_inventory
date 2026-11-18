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
            'staff_outlet' => 'Staff Outlet',
            'pic_outlet' => 'PIC Outlet',
            'pic_warehouse' => 'PIC Gudang',
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

        $stockInApprove = Permission::updateOrCreate(['name' => 'stock-in.approve'], ['label' => 'Approve outlet stock receipts']);
        Role::whereIn('name', ['super_admin', 'pic_outlet'])->each(
            fn (Role $role) => $role->permissions()->syncWithoutDetaching([$stockInApprove->id]),
        );
        Role::whereNotIn('name', ['super_admin', 'pic_outlet'])->each(
            fn (Role $role) => $role->permissions()->detach($stockInApprove->id),
        );

        $opnameManage = Permission::updateOrCreate(['name' => 'opname.manage'], ['label' => 'Create and count stock opname']);
        Role::whereIn('name', ['super_admin', 'staff_gudang', 'staff_outlet', 'pic_outlet'])->each(
            fn (Role $role) => $role->permissions()->syncWithoutDetaching([$opnameManage->id]),
        );
        Role::whereNotIn('name', ['super_admin', 'staff_gudang', 'staff_outlet', 'pic_outlet'])->each(
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

        $disposalManage = Permission::updateOrCreate(['name' => 'disposal.manage'], ['label' => 'Submit stock write-off and supplier return requests']);
        Role::whereIn('name', ['super_admin', 'staff_gudang'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$disposalManage->id]));
        Role::whereNotIn('name', ['super_admin', 'staff_gudang'])->each(fn (Role $role) => $role->permissions()->detach($disposalManage->id));

        $disposalApprove = Permission::updateOrCreate(['name' => 'disposal.approve'], ['label' => 'Review stock write-off and supplier return requests']);
        Role::whereIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$disposalApprove->id]));
        Role::whereNotIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->detach($disposalApprove->id));

        $disposalView = Permission::updateOrCreate(['name' => 'disposal.view'], ['label' => 'View supplier return and claim tracking']);
        Role::whereIn('name', ['super_admin', 'finance'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$disposalView->id]));
        Role::whereNotIn('name', ['super_admin', 'finance'])->each(fn (Role $role) => $role->permissions()->detach($disposalView->id));

        $disposalResolve = Permission::updateOrCreate(['name' => 'disposal.resolve'], ['label' => 'Record supplier return claim outcomes']);
        Role::whereIn('name', ['super_admin', 'purchasing', 'finance'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$disposalResolve->id]));
        Role::whereNotIn('name', ['super_admin', 'purchasing', 'finance'])->each(fn (Role $role) => $role->permissions()->detach($disposalResolve->id));

        $purchasingManage = Permission::updateOrCreate(['name' => 'purchasing.manage'], ['label' => 'Create purchase requests and orders']);
        Role::whereIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$purchasingManage->id]));
        Role::whereNotIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->detach($purchasingManage->id));

        $invoiceView = Permission::updateOrCreate(['name' => 'invoice.view'], ['label' => 'View supplier purchase invoices']);
        Role::whereIn('name', ['super_admin', 'purchasing', 'finance'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$invoiceView->id]));
        Role::whereNotIn('name', ['super_admin', 'purchasing', 'finance'])->each(fn (Role $role) => $role->permissions()->detach($invoiceView->id));

        $invoiceManage = Permission::updateOrCreate(['name' => 'invoice.manage'], ['label' => 'Create and submit supplier purchase invoices']);
        Role::whereIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$invoiceManage->id]));
        Role::whereNotIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->detach($invoiceManage->id));

        $invoiceReview = Permission::updateOrCreate(['name' => 'invoice.review'], ['label' => 'Review supplier purchase invoices and view inventory quantities for valuation']);
        Role::whereIn('name', ['super_admin', 'finance'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$invoiceReview->id]));
        Role::whereNotIn('name', ['super_admin', 'finance'])->each(fn (Role $role) => $role->permissions()->detach($invoiceReview->id));

        $invoicePayment = Permission::updateOrCreate(['name' => 'invoice.payment.manage'], ['label' => 'Record supplier invoice payments']);
        Role::whereIn('name', ['super_admin', 'finance'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$invoicePayment->id]));
        Role::whereNotIn('name', ['super_admin', 'finance'])->each(fn (Role $role) => $role->permissions()->detach($invoicePayment->id));

        $transferManage = Permission::updateOrCreate(['name' => 'transfer.manage'], ['label' => 'Dispatch stock transfers']);
        Role::whereIn('name', ['super_admin', 'staff_gudang'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$transferManage->id]));
        Role::whereNotIn('name', ['super_admin', 'staff_gudang'])->each(fn (Role $role) => $role->permissions()->detach($transferManage->id));

        $transferApprove = Permission::updateOrCreate(['name' => 'transfer.approve'], ['label' => 'Approve warehouse stock transfers before dispatch']);
        Role::whereIn('name', ['super_admin', 'pic_warehouse'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$transferApprove->id]));
        Role::whereNotIn('name', ['super_admin', 'pic_warehouse'])->each(fn (Role $role) => $role->permissions()->detach($transferApprove->id));

        $transferReceive = Permission::updateOrCreate(['name' => 'transfer.receive'], ['label' => 'Receive stock transfers at outlets']);
        Role::whereIn('name', ['super_admin', 'staff_outlet', 'pic_outlet'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$transferReceive->id]));
        Role::whereNotIn('name', ['super_admin', 'staff_outlet', 'pic_outlet'])->each(fn (Role $role) => $role->permissions()->detach($transferReceive->id));

        $transferResolveDiscrepancy = Permission::updateOrCreate(['name' => 'transfer.resolve_discrepancy'], ['label' => 'Resolve stock transfer discrepancies']);
        Role::whereIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$transferResolveDiscrepancy->id]));
        Role::whereNotIn('name', ['super_admin', 'purchasing'])->each(fn (Role $role) => $role->permissions()->detach($transferResolveDiscrepancy->id));

        $reportsView = Permission::updateOrCreate(['name' => 'reports.view'], ['label' => 'View inventory and operational reports']);
        Role::whereIn('name', ['super_admin', 'staff_gudang', 'staff_outlet', 'pic_outlet', 'management', 'purchasing', 'finance'])
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$reportsView->id]));

        $lowStockView = Permission::updateOrCreate(['name' => 'low_stock.view'], ['label' => 'View low stock alerts and history']);
        Role::whereIn('name', ['super_admin', 'staff_gudang', 'staff_outlet', 'pic_outlet'])
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$lowStockView->id]));
        Role::whereNotIn('name', ['super_admin', 'staff_gudang', 'staff_outlet', 'pic_outlet'])
            ->each(fn (Role $role) => $role->permissions()->detach($lowStockView->id));

        $lowStockManage = Permission::updateOrCreate(['name' => 'low_stock.manage'], ['label' => 'Manage per-location minimum stock thresholds']);
        Role::where('name', 'super_admin')->firstOrFail()->permissions()->syncWithoutDetaching([$lowStockManage->id]);
        Role::where('name', '!=', 'super_admin')->each(fn (Role $role) => $role->permissions()->detach($lowStockManage->id));

        $whatsappManage = Permission::updateOrCreate(['name' => 'whatsapp.manage'], ['label' => 'Configure WhatsApp low stock alerts']);
        Role::where('name', 'super_admin')->firstOrFail()->permissions()->syncWithoutDetaching([$whatsappManage->id]);
        Role::where('name', '!=', 'super_admin')->each(fn (Role $role) => $role->permissions()->detach($whatsappManage->id));

    }
}
