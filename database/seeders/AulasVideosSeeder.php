<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AulasVideosSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/aulas_videos.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("❌ aulas_videos.json no encontrado");
            return;
        }

        $videos = json_decode(file_get_contents($jsonPath), true);

        if (!$videos) {
            $this->command->error('❌ Error al leer aulas_videos.json');
            return;
        }

        $this->command->info("📦 Insertando " . count($videos) . " videos...");

        foreach ($videos as $video) {
            DB::table('aula_videos')->insert([
                'id' => $video['id'],
                'aula_id' => $video['aula_id'],
                'url' => $video['url'],
                'created_at' => $video['created_at'],
                'updated_at' => $video['updated_at'],
            ]);
        }

        $this->command->info('✅ Videos insertados');
    }
}
