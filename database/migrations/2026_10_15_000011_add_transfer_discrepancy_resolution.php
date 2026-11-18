<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'stock_transfer_discrepancy_resolutions';
        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_transfer_item_id');
                $table->string('resolution_type', 30);
                $table->decimal('quantity', 14, 3);
                $table->text('reason');
                $table->foreignId('resolved_by');
                $table->timestamp('resolved_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex($tableName, 'transfer_discrepancy_resolution_lookup_index')) {
            Schema::table($tableName, fn (Blueprint $table) => $table->index(
                ['stock_transfer_item_id', 'resolved_at'],
                'transfer_discrepancy_resolution_lookup_index'
            ));
        }

        $foreignKeys = Schema::getForeignKeys($tableName);
        $hasItemForeignKey = collect($foreignKeys)->contains(fn (array $key) =>
            $key['columns'] === ['stock_transfer_item_id']
            && $key['foreign_table'] === 'stock_transfer_items'
            && $key['foreign_columns'] === ['id']
        );
        if (! $hasItemForeignKey) {
            Schema::table($tableName, fn (Blueprint $table) => $table
                ->foreign('stock_transfer_item_id', 'str_disc_res_transfer_item_fk')
                ->references('id')->on('stock_transfer_items')->restrictOnDelete());
        }

        $hasResolverForeignKey = collect($foreignKeys)->contains(fn (array $key) =>
            $key['columns'] === ['resolved_by']
            && $key['foreign_table'] === 'users'
            && $key['foreign_columns'] === ['id']
        );
        if (! $hasResolverForeignKey) {
            Schema::table($tableName, fn (Blueprint $table) => $table
                ->foreign('resolved_by', 'str_disc_res_resolver_fk')
                ->references('id')->on('users')->restrictOnDelete());
        }

        DB::table('stock_transfers')
            ->where('status', 'received_with_discrepancy')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('stock_transfer_items')
                ->whereColumn('stock_transfer_items.stock_transfer_id', 'stock_transfers.id')
                ->where('stock_transfer_items.discrepancy_quantity', '>', 0))
            ->update(['status' => 'discrepancy_open']);

        $permissionId = DB::table('permissions')->where('name', 'transfer.resolve_discrepancy')->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'transfer.resolve_discrepancy',
                'label' => 'Resolve stock transfer discrepancies',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('roles')->whereIn('name', ['super_admin', 'purchasing'])->pluck('id') as $roleId) {
            DB::table('permission_role')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $roleId], []);
        }
    }

    public function down(): void
    {
        DB::table('stock_transfers')->where('status', 'discrepancy_open')->update(['status' => 'received_with_discrepancy']);
        $permissionId = DB::table('permissions')->where('name', 'transfer.resolve_discrepancy')->value('id');
        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
        Schema::dropIfExists('stock_transfer_discrepancy_resolutions');
    }
};
