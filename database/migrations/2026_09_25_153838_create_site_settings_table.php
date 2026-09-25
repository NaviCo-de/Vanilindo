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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name', 160);
            $table->string('tagline')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_alt')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('whatsapp_number', 32)->nullable();
            $table->text('address')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
