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
        // 1. Kategori & 5 Pilar Program NU Care
        Schema::create('program_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('icon', 50)->default('fa-heart');
            $table->string('color_code', 20)->default('#0a6b38');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Kampanye Penggalangan Dana / Program Crowdfunding
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('program_categories')->cascadeOnDelete();
            $table->foreignId('mwc_id')->nullable()->constrained('mwcs')->nullOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('short_description', 350);
            $table->longText('story_description');
            $table->decimal('target_amount', 15, 2)->default(0.00);
            $table->decimal('collected_amount', 15, 2)->default(0.00);
            $table->string('banner_image', 255)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('active'); // active, completed, suspended
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_emergency')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'is_featured']);
        });

        // 3. Donasi & Infaq Online (Masyarakat Umum)
        Schema::create('online_donations', function (Blueprint $table) {
            $table->id();
            $table->string('donation_code', 32)->unique();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('donor_name', 150);
            $table->string('donor_phone', 25);
            $table->string('donor_email', 120)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->decimal('amount', 15, 2);
            $table->integer('unique_code')->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method', 50); // bsi, bri, mandiri, qris, cash
            $table->string('payment_status', 30)->default('pending'); // pending, verified, expired, cancelled
            $table->text('prayer_message')->nullable();
            $table->string('payment_proof', 255)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('payment_status');
        });

        // 4. Portal Berita & Kabar Pentasyarufan
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('program_categories')->nullOnDelete();
            $table->foreignId('mwc_id')->nullable()->constrained('mwcs')->nullOnDelete();
            $table->foreignId('ranting_id')->nullable()->constrained('rantings')->nullOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('post_type', 40)->default('berita'); // berita, pentasyarufan, edukasi_zis, pengumuman
            $table->string('excerpt', 400);
            $table->longText('content');
            $table->string('thumbnail_image', 255)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('published'); // draft, published, archived
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['post_type', 'status']);
        });

        // 5. Slider Banner Utama (Hero Carousel)
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('tagline', 255)->nullable();
            $table->string('image_desktop', 255)->nullable();
            $table->string('button_text', 50)->default('Donasi Sekarang');
            $table->string('button_url', 255)->default('#donasi-cepat');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Laporan Transparansi Keuangan Publik
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->year('report_year');
            $table->string('report_period', 50); // Semester I, Triwulan IV, Tahunan
            $table->decimal('total_inflow', 15, 2)->default(0.00);
            $table->decimal('total_outflow', 15, 2)->default(0.00);
            $table->string('audit_status', 100)->default('Wajar Tanpa Pengecualian (WTP)');
            $table->string('file_pdf', 255)->nullable();
            $table->date('published_at');
            $table->timestamps();
        });

        // 7. Pengaturan Situs & Rekening Resmi
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->text('value')->nullable();
            $table->string('group_name', 50)->default('general');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('financial_reports');
        Schema::dropIfExists('sliders');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('online_donations');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('program_categories');
    }
};
