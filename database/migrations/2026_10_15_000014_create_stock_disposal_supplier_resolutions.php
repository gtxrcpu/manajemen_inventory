<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_disposal_supplier_resolutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_disposal_item_id');
            $table->string('resolution_type', 32);
            $table->decimal('quantity', 14, 3);
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('reference', 150)->nullable();
            $table->unsignedBigInteger('replacement_stock_in_id')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamp('resolved_at');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('stock_disposal_item_id', 'sdsr_item_fk')->references('id')->on('stock_disposal_items')->restrictOnDelete();
            $table->foreign('replacement_stock_in_id', 'sdsr_stockin_fk')->references('id')->on('stock_ins')->restrictOnDelete();
            $table->foreign('recorded_by', 'sdsr_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->index(['stock_disposal_item_id', 'created_at'], 'sdsr_item_created_idx');
        });

        $this->grant('disposal.view', 'View supplier return and claim tracking', ['super_admin', 'finance']);
        $this->grant('disposal.resolve', 'Record supplier return claim outcomes', ['super_admin', 'purchasing', 'finance']);
    }

    private function grant(string $name, string $label, array $roles): void
    {
        $permissionId = DB::table('permissions')->where('name', $name)->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId(['name' => $name, 'label' => $label, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (DB::table('roles')->whereIn('name', $roles)->pluck('id') as $roleId) {
            DB::table('permission_role')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $roleId], []);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', ['disposal.view', 'disposal.resolve'])->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        Schema::dropIfExists('stock_disposal_supplier_resolutions');
    }
};
