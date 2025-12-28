<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('related_account_id')->nullable()->constrained('accounts')->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'transfer']);
            $table->enum('direction', ['in', 'out']);
            $table->bigInteger('amount');
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['transaction_code', 'amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
