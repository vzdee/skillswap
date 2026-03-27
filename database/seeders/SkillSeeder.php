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
            'Análisis Financiero',
            'Arduino IOT',
            'Arquitectura API',
            'Bioinformatica',
            'Bioinstrumentacion',
            'C++',
            'Canto',
            'Cálculo Diferencial',
            'Cálculo Integral',
            'Comercio Exterior',
            'Contabilidad Costos',
            'CRM Management',
            'Ciberseguridad',
            'Diseño Protesis',
            'Docker Containers',
            'Ecuaciones Diferenciales',
            'Edicion Video',
            'Estadística',
            'Excel Avanzado',
            'Figma',
            'Finanzas Corporativas',
            'Frances',
            'Metodologías Ágiles',
            'Git GitHub',
            'Google Ads',
            'Illustrator',
            'Imagenologia Medica',
            'Inglés',
            'Java',
            'JavaScript',
            'KPIs Negocio',
            'LabVIEW Design',
            'Laravel',
            'Lean Manufacturing',
            'Liderazgo Equipos',
            'Linux Terminal',
            'Logística Suministros',
            'Machine Learning',
            'Marketing Digital',
            'MATLAB Simulink',
            'Modelado 3D',
            'Node.js',
            'Photoshop',
            'Piano',
            'Power BI',
            'Principios Eléctricos',
            'Procesamiento Imagenes',
            'Python',
            'React',
            'Redaccion Técnica',
            'Redaccion',
            'SAP ERP',
            'SEO SEM',
            'SEO',
            'Simulación Arena',
            'Six Sigma',
            'SolidWorks CAD',
            'SQL Server',
            'TypeScript',
            'UI Design',
            'UX Research',
            'Auditoria Sistemas',
            'AutoCAD 3D',
            'AWS Cloud',
        ];

        $skills = collect($skills)->unique()->values()->all();

        Skill::query()->whereNotIn('name', $skills)->delete();

        foreach ($skills as $skillName) {
            Skill::query()->firstOrCreate(['name' => $skillName]);
        }
    }
}
