<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'payout_method')) $table->string('payout_method', 32)->nullable()->after('freelance_url');
            if (!Schema::hasColumn('users', 'payout_destination')) $table->text('payout_destination')->nullable()->after('payout_method');
            if (!Schema::hasColumn('users', 'payout_destination_last4')) $table->string('payout_destination_last4', 4)->nullable()->after('payout_destination');
            if (!Schema::hasColumn('users', 'tax_residency_country')) $table->char('tax_residency_country', 2)->nullable()->after('payout_destination_last4');
            if (!Schema::hasColumn('users', 'tax_form_type')) $table->string('tax_form_type', 16)->nullable()->after('tax_residency_country');
            if (!Schema::hasColumn('users', 'tax_form_status')) $table->string('tax_form_status', 16)->default('unverified')->after('tax_form_type');
            if (!Schema::hasColumn('users', 'tax_form_completed_at')) $table->timestamp('tax_form_completed_at')->nullable()->after('tax_form_status');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $columns=['payout_method','payout_destination','payout_destination_last4','tax_residency_country','tax_form_type','tax_form_status','tax_form_completed_at'];
            foreach ($columns as $column) if (Schema::hasColumn('users', $column)) $table->dropColumn($column);
        });
    }
};