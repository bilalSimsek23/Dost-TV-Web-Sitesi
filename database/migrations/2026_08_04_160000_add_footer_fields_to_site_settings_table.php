<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('footer_logo')->nullable()->after('logo_alt_text');
            $table->text('footer_description')->nullable()->after('footer_logo');
            $table->string('phone')->nullable()->after('footer_description');
            $table->string('email')->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
            $table->string('kep_address')->nullable()->after('address');
            $table->string('facebook_url')->nullable()->after('kep_address');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->string('x_url')->nullable()->after('instagram_url');
            $table->string('youtube_url')->nullable()->after('x_url');
            $table->string('whatsapp_url')->nullable()->after('youtube_url');
            $table->string('telegram_url')->nullable()->after('whatsapp_url');
            $table->string('copyright_text')->nullable()->after('telegram_url');
            $table->boolean('footer_show_socials')->default(true)->after('copyright_text');
            $table->boolean('footer_show_contact')->default(true)->after('footer_show_socials');
            $table->boolean('footer_show_bank_link')->default(true)->after('footer_show_contact');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'footer_logo',
                'footer_description',
                'phone',
                'email',
                'address',
                'kep_address',
                'facebook_url',
                'instagram_url',
                'x_url',
                'youtube_url',
                'whatsapp_url',
                'telegram_url',
                'copyright_text',
                'footer_show_socials',
                'footer_show_contact',
                'footer_show_bank_link',
            ]);
        });
    }
};
