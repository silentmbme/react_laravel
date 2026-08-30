<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function hasIndex(string $table, string $name): bool {
        return collect(Schema::getIndexes($table))->contains(fn ($index) => $index['name'] === $name);
    }

    public function up(): void {
        // Preserve foreign-key indexes before replacing legacy unique indexes.
        if (!$this->hasIndex('download_entitlements', 'download_entitlements_user_id_fk_index')) {
            Schema::table('download_entitlements', fn (Blueprint $table) => $table->index('user_id', 'download_entitlements_user_id_fk_index'));
        }
        if (!$this->hasIndex('download_entitlements', 'download_entitlements_order_item_fk_index')) {
            Schema::table('download_entitlements', fn (Blueprint $table) => $table->index('order_item_id', 'download_entitlements_order_item_fk_index'));
        }
        if (!$this->hasIndex('purchase_verifications', 'purchase_verifications_order_item_fk_index')) {
            Schema::table('purchase_verifications', fn (Blueprint $table) => $table->index('order_item_id', 'purchase_verifications_order_item_fk_index'));
        }
        Schema::table('download_entitlements', function (Blueprint $table) {
            $table->dropUnique('download_entitlements_user_id_order_item_id_unique');
            $table->unsignedInteger('unit_number')->default(1)->after('order_item_id');
            $table->unique(['user_id', 'order_item_id', 'unit_number'], 'entitlement_purchase_unit_unique');
        });
        Schema::table('purchase_verifications', function (Blueprint $table) {
            $table->dropUnique('purchase_verifications_order_item_id_unique');
            $table->unsignedInteger('unit_number')->default(1)->after('order_item_id');
            $table->unique(['order_item_id', 'unit_number'], 'verification_purchase_unit_unique');
        });
    }

    public function down(): void {
        Schema::table('purchase_verifications', function (Blueprint $table) {
            $table->dropUnique('verification_purchase_unit_unique');
            $table->dropColumn('unit_number');
            $table->unique('order_item_id');
        });
        Schema::table('download_entitlements', function (Blueprint $table) {
            $table->dropUnique('entitlement_purchase_unit_unique');
            $table->dropColumn('unit_number');
            $table->unique(['user_id', 'order_item_id']);
        });
    }
};