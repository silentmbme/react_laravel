<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'discount_total')) $table->decimal('discount_total', 14, 2)->default(0)->after('subtotal');
            if (!Schema::hasColumn('orders', 'refunded_total')) $table->decimal('refunded_total', 14, 2)->default(0)->after('total');
            if (!Schema::hasColumn('orders', 'tax_snapshot')) $table->json('tax_snapshot')->nullable()->after('tax');
        });
        if (DB::getDriverName() !== 'mysql' || !DB::selectOne("SHOW INDEX FROM orders WHERE Key_name = 'orders_user_id_status_paid_at_index'")) Schema::table('orders', fn (Blueprint $table) => $table->index(['user_id', 'status', 'paid_at']));

        if (!Schema::hasTable('financial_transactions')) Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id')->unique();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 32)->index();
            $table->string('status', 32)->default('completed')->index();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('USD')->index();
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->decimal('base_amount', 14, 2);
            $table->char('base_currency', 3)->default('USD');
            $table->decimal('fee_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->string('payment_reference')->nullable()->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
            $table->index(['seller_id', 'currency', 'created_at']);
        });

        if (!Schema::hasTable('ledger_entries')) Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_transaction_id')->constrained()->restrictOnDelete();
            $table->string('account', 64)->index();
            $table->string('entry_type', 6); // debit or credit
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('USD');
            $table->decimal('base_amount', 14, 2);
            $table->char('base_currency', 3)->default('USD');
            $table->timestamps();
            // Explicit name stays under MySQL's 64-character identifier limit.
            $table->unique(['financial_transaction_id', 'account', 'entry_type'], 'ledger_tx_account_type_uq');
            $table->index(['account', 'currency', 'created_at']);
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasTable('ledger_entries') && !DB::selectOne("SHOW INDEX FROM ledger_entries WHERE Key_name = 'ledger_tx_account_type_uq'")) Schema::table('ledger_entries', fn (Blueprint $table) => $table->unique(['financial_transaction_id', 'account', 'entry_type'], 'ledger_tx_account_type_uq'));

        if (!Schema::hasTable('refunds')) Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('refund_id')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('status', 32)->default('pending')->index();
            $table->string('external_reference')->nullable()->unique();
            $table->string('idempotency_key')->unique();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        if (!Schema::hasTable('invoices')) Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->json('snapshot');
            $table->timestamp('issued_at');
            $table->timestamps();
        });

        if (!Schema::hasTable('tax_configurations')) Schema::create('tax_configurations', function (Blueprint $table) {
            $table->id();
            $table->boolean('tax_enabled')->default(false)->index();
            $table->string('tax_name');
            $table->string('tax_code')->nullable();
            $table->decimal('tax_rate', 7, 4);
            $table->string('tax_type', 32)->default('percentage');
            $table->string('country', 2)->nullable()->index();
            $table->string('region')->nullable()->index();
            $table->boolean('inclusive')->default(false);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('financial_audit_logs')) Schema::create('financial_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('entity_type', 128);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });

        if (!Schema::hasTable('payouts')) Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('payout_id')->unique();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('status', 32)->default('pending')->index();
            $table->string('payout_method')->nullable();
            $table->string('external_reference')->nullable()->unique();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->index(['seller_id', 'currency', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payouts'); Schema::dropIfExists('financial_audit_logs'); Schema::dropIfExists('tax_configurations');
        Schema::dropIfExists('invoices'); Schema::dropIfExists('refunds'); Schema::dropIfExists('ledger_entries'); Schema::dropIfExists('financial_transactions');
        Schema::table('orders', function (Blueprint $table) { $table->dropIndex(['user_id', 'status', 'paid_at']); $table->dropColumn(['discount_total','refunded_total','tax_snapshot']); });
    }
};
