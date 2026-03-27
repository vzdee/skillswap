<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Seed the application's skills catalog.
     */
    public function run(): void
    {
        $skills = [
            'Python',
            'Java',
            'C++',
            'JavaScript',
            'TypeScript',
            'UI Design',
            'UX Research',
            'Figma',
            'Photoshop',
            'Illustrator',
            'Excel',
            'Power BI',
            'Marketing Digital',
            'SEO',
            'Oratoria',
            'Ingles',
            'Frances',
            'Redaccion',
            'Matematicas',
            'Estadistica',
            'Machine Learning',
            'Desarrollo Web',
            'Laravel',
            'React',
            'Node.js',
        ];

        foreach ($skills as $skillName) {
            Skill::query()->firstOrCreate(['name' => $skillName]);
        }
    }
}
