<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LargeDatasetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::disableQueryLog();

        // 1. Create tags
        $tags = Tag::factory()->count(10)->create();
        $tagIds = $tags->pluck('id');

        // 2. Create translations in chunks
        $chunkSize = 1000;
        $totalTranslations = 100000;
        
        $allTranslationIds = collect();

        echo "Seeding translations...\n";
        for ($i = 0; $i < $totalTranslations / $chunkSize; $i++) {
            $translations = Translation::factory()->count($chunkSize)->create();
            $allTranslationIds = $allTranslationIds->merge($translations->pluck('id'));
            echo "Seeded " . ($i + 1) * $chunkSize . " translations.\n";
        }

        // 3. Create pivot table entries in chunks
        echo "Seeding pivot table...\n";
        $pivots = [];
        $pivotChunkSize = 5000;
        foreach ($allTranslationIds as $translationId) {
            // Attach 1 to 3 tags
            $numTags = rand(1, 3);
            for ($j=0; $j < $numTags; $j++) {
                $pivots[] = [
                    'tag_id' => $tagIds->random(),
                    'translation_id' => $translationId,
                ];
            }

            if (count($pivots) >= $pivotChunkSize) {
                DB::table('tag_translation')->insert(collect($pivots)->unique()->all());
                $pivots = [];
                echo "Inserted " . $pivotChunkSize . " pivot entries.\n";
            }
        }

        if (!empty($pivots)) {
            DB::table('tag_translation')->insert(collect($pivots)->unique()->all());
            echo "Inserted final " . count($pivots) . " pivot entries.\n";
        }

        echo "Seeding complete.\n";
    }
}
