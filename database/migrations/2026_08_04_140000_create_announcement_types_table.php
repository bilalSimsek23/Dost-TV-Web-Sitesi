<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $defaultTypes = [
            ['name' => 'Genel Bilgilendirme', 'slug' => 'general', 'sort_order' => 1],
            ['name' => 'Cuma Mesajı', 'slug' => 'friday', 'sort_order' => 2],
            ['name' => 'Kandil', 'slug' => 'kandil', 'sort_order' => 3],
            ['name' => 'Ramazan', 'slug' => 'ramadan', 'sort_order' => 4],
            ['name' => 'Bayram', 'slug' => 'holiday', 'sort_order' => 5],
            ['name' => 'Yayın Bilgilendirmesi', 'slug' => 'broadcast', 'sort_order' => 6],
            ['name' => 'Teknik Duyuru', 'slug' => 'maintenance', 'sort_order' => 7],
            ['name' => 'Diğer', 'slug' => 'other', 'sort_order' => 8],
        ];

        $now = now();
        foreach ($defaultTypes as $type) {
            DB::table('announcement_types')->insert([
                'name' => $type['name'],
                'slug' => $type['slug'],
                'is_active' => true,
                'sort_order' => $type['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('announcements', function (Blueprint $table) {
            $table->text('message')->nullable()->change();
            $table->foreignId('announcement_type_id')->nullable()->after('type')->constrained('announcement_types')->nullOnDelete();
        });

        // Backfill announcement_type_id for existing announcements
        $typesMap = DB::table('announcement_types')->pluck('id', 'slug')->toArray();
        $announcements = DB::table('announcements')->get();

        foreach ($announcements as $announcement) {
            $slug = $announcement->type ?? 'general';
            $typeId = $typesMap[$slug] ?? ($typesMap['general'] ?? null);

            if ($typeId) {
                DB::table('announcements')
                    ->where('id', $announcement->id)
                    ->update(['announcement_type_id' => $typeId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['announcement_type_id']);
            $table->dropColumn('announcement_type_id');
        });

        Schema::dropIfExists('announcement_types');
    }
};
