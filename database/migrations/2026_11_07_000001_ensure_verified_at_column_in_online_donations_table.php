<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('online_donations')) {
            Schema::table('online_donations', function (Blueprint $table) {
                if (!Schema::hasColumn('online_donations', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('payment_proof');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Biarkan tetap ada demi menjaga audit trail verifikasi
    }
};

