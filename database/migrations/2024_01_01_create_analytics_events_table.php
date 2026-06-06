<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_analytics_events', function (Blueprint $table) {
            $table->id();

            // Event classification
            $table->string('event_type', 50)->index();           // payment_successful | payment_failed

            // Core transaction fields
            $table->string('transaction_id', 100)->nullable()->index();
            $table->string('phone_number', 20)->nullable()->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 10)->default('KES');

            // M-Pesa result
            $table->integer('result_code')->nullable()->index();
            $table->string('result_description', 255)->nullable();

            // Payment context
            $table->string('payment_method', 50)->default('mpesa');
            $table->string('account_reference', 100)->nullable();
            $table->string('business_short_code', 20)->nullable()->index();
            $table->string('transaction_type', 50)->nullable();

            // Flexible metadata (extra fields)
            $table->json('metadata')->nullable();

            // When the transaction actually occurred (may differ from created_at)
            $table->timestamp('occurred_at')->index();

            $table->timestamps();

            // Composite indexes for common analytics queries
            $table->index(['event_type', 'occurred_at']);
            $table->index(['phone_number', 'occurred_at']);
            $table->index(['business_short_code', 'occurred_at']);
            $table->index(['result_code', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_analytics_events');
    }
};
