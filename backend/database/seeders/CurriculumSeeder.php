<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use App\Models\Topic;
use Illuminate\Database\Seeder;

/**
 * Kerangka 7 bidang teori + jalur praktikum (hanya struktur; isi materi ditulis pengajar).
 * Modul "kerangka" bertanda CONTOH.
 */
class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::updateOrCreate(['slug' => 'osn-biologi'], [
            'title' => 'OSN Biology Academy', 'description' => 'Persiapan OSN Biologi SMA/MA.', 'syllabus_version' => '2026.1',
        ]);

        $topics = [
            [1, 'biologi-sel-dan-molekuler', 'Biologi Sel dan Molekuler', 'Struktur sel, biomolekul, enzim, metabolisme, dan dasar biologi molekuler.', 'theory'],
            [2, 'anatomi-fisiologi-tumbuhan', 'Anatomi dan Fisiologi Tumbuhan', 'Struktur jaringan, transpor, fotosintesis, hormon, dan respons tumbuhan.', 'theory'],
            [3, 'anatomi-fisiologi-hewan-manusia', 'Anatomi dan Fisiologi Hewan/Manusia', 'Sistem organ, homeostasis, dan fisiologi komparatif.', 'theory'],
            [4, 'genetika-dan-evolusi', 'Genetika dan Evolusi', 'Pewarisan sifat, genetika populasi, mekanisme evolusi.', 'theory'],
            [5, 'ekologi', 'Ekologi', 'Populasi, komunitas, ekosistem, dan biologi konservasi.', 'theory'],
            [6, 'etologi', 'Etologi', 'Dasar perilaku hewan, adaptasi perilaku, dan komunikasi.', 'theory'],
            [7, 'biosistematika', 'Biosistematika', 'Taksonomi, filogeni, dan keanekaragaman hayati.', 'theory'],
            [8, 'eksperimen-dan-praktikum', 'Jalur Eksperimen dan Praktikum', 'Desain eksperimen, analisis data, dan keterampilan laboratorium. Materi daring/virtual tidak menggantikan praktik laboratorium nyata.', 'practicum'],
        ];

        foreach ($topics as [$n, $slug, $title, $desc, $type]) {
            Topic::updateOrCreate(['slug' => $slug], [
                'course_id' => $course->id, 'number' => $n <= 7 ? $n : null, 'title' => $title, 'description' => $desc,
                'type' => $type, 'status' => 'published', 'sort' => $n, 'syllabus_version' => '2026.1',
            ]);
        }

        $cell = Topic::where('slug', 'biologi-sel-dan-molekuler')->first();

        // Modul 1: lengkap (lihat SampleContentSeeder). Dibuat di sini agar urutan terjaga.
        Module::updateOrCreate(['slug' => 'memahami-enzim'], [
            'topic_id' => $cell->id, 'title' => 'Memahami Enzim dan Cara Kerjanya',
            'summary' => 'Enzim sebagai katalis biologis: sisi aktif, faktor yang memengaruhi laju reaksi, dan inhibisi. (Materi contoh)',
            'access_level' => 0, 'status' => 'published', 'sort' => 1,
        ]);

        // Modul berbayar CONTOH untuk memperlihatkan status "terkunci".
        Module::updateOrCreate(['slug' => 'contoh-modul-berbayar'], [
            'topic_id' => $cell->id, 'title' => '[Contoh] Modul Full Course — kerangka',
            'summary' => 'CONTOH kerangka modul berbayar. Isi belum ditulis.',
            'access_level' => 1, 'status' => 'published', 'sort' => 2,
        ]);

        // Modul draft CONTOH: tidak boleh terlihat siswa.
        Module::updateOrCreate(['slug' => 'contoh-modul-draft'], [
            'topic_id' => $cell->id, 'title' => '[Contoh] Modul masih draft',
            'summary' => 'CONTOH konten draft; tidak tampil bagi siswa.',
            'access_level' => 0, 'status' => 'draft', 'sort' => 3,
        ]);
    }
}
