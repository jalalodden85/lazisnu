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
        Schema::table('rantings', function (Blueprint $table) {
            if (!Schema::hasColumn('rantings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('address')->comment('Status keikutsertaan ranting dalam program KOIN NU');
            }
            if (!Schema::hasIndex('rantings', ['is_active'])) {
                $table->index('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rantings', function (Blueprint $table) {
            if (Schema::hasIndex('rantings', ['is_active'])) {
                $table->dropIndex(['is_active']);
            }
            if (Schema::hasColumn('rantings', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
