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
        if (!Schema::hasTable('online_donations')) {
            Schema::create('online_donations', function (Blueprint $table) {
                $table->id();
                $table->string('donation_code', 32)->unique();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
                $table->string('kategori', 50)->default('Infaq');
                $table->string('donor_name', 150);
                $table->string('donor_phone', 25);
                $table->string('donor_email', 120)->nullable();
                $table->string('alamat', 255)->nullable();
                $table->boolean('is_anonymous')->default(false);
                $table->decimal('amount', 15, 2);
                $table->integer('unique_code')->default(0);
                $table->decimal('total_amount', 15, 2);
                $table->decimal('nominal_terverifikasi', 15, 2)->nullable();
                $table->string('payment_method', 50)->default('qris');
                $table->text('qris_url')->nullable()->comment('URL gambar atau QR string Dynamic QRIS dari Payment Gateway');
                $table->text('payment_gateway_payload')->nullable()->comment('Raw callback / webhook payload dari Payment Gateway');
                $table->string('payment_status', 30)->default('pending'); // pending, verified, expired, cancelled
                $table->text('prayer_message')->nullable();
                $table->string('payment_proof', 255)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('payment_status');
                $table->index(['payment_status', 'kategori'], 'idx_donations_status_kategori');
                $table->index(['payment_status', 'created_at'], 'idx_donations_status_created');
            });
        } else {
            Schema::table('online_donations', function (Blueprint $table) {
                if (!Schema::hasColumn('online_donations', 'qris_url')) {
                    $table->text('qris_url')->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('online_donations', 'payment_gateway_payload')) {
                    $table->text('payment_gateway_payload')->nullable()->after('qris_url');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('online_donations')) {
            Schema::table('online_donations', function (Blueprint $table) {
                if (Schema::hasColumn('online_donations', 'payment_gateway_payload')) {
                    $table->dropColumn('payment_gateway_payload');
                }
                if (Schema::hasColumn('online_donations', 'qris_url')) {
                    $table->dropColumn('qris_url');
                }
            });
        }
    }
};
