<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use Illuminate\Database\Seeder;

/**
 * Konten CONTOH untuk mencoba platform. Bukan materi final: tinjau oleh pengajar sebelum dipakai.
 * Seluruh teks ditulis orisinal; tidak disalin dari buku atau soal kompetisi.
 */
class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->lessons();
        $this->questions();
    }

    private function lessons(): void
    {
        $module = Module::where('slug', 'memahami-enzim')->first();

        Lesson::updateOrCreate(['slug' => 'memahami-enzim-dan-cara-kerjanya'], [
            'module_id' => $module->id,
            'title' => 'Memahami Enzim dan Cara Kerjanya',
            'difficulty' => 'fondasi',
            'est_minutes' => 25,
            'status' => 'published',
            'sort' => 1,
            'objectives' => '<ul>'
                . '<li>Menjelaskan enzim sebagai katalis biologis dan mengapa sel membutuhkannya.</li>'
                . '<li>Menjelaskan model kunci-gembok dan <em>induced fit</em> pada sisi aktif.</li>'
                . '<li>Memprediksi pengaruh suhu, pH, konsentrasi substrat, dan konsentrasi enzim terhadap laju reaksi.</li>'
                . '<li>Membedakan inhibitor kompetitif dan nonkompetitif dari pola grafiknya.</li></ul>',
            'explanation' => '<blockquote>Catatan: ini materi contoh untuk mendemonstrasikan platform. Tinjau kembali oleh pengajar sebelum dijadikan materi final.</blockquote>'
                . '<p>Reaksi kimia di dalam sel harus berlangsung cepat dan terkendali pada suhu tubuh. <strong>Enzim</strong> adalah katalis biologis (umumnya protein) yang mempercepat reaksi dengan menurunkan <strong>energi aktivasi</strong>, yaitu energi minimum yang dibutuhkan agar reaksi dapat dimulai.</p>'
                . '<p>Enzim tidak mengubah arah kesetimbangan reaksi dan tidak habis dipakai: setelah satu siklus, enzim kembali bebas untuk mengikat substrat berikutnya. Yang berubah hanyalah <em>seberapa cepat</em> kesetimbangan itu tercapai.</p>'
                . '<h3>Sisi aktif dan kespesifikan</h3>'
                . '<p>Substrat mengikat enzim pada <strong>sisi aktif</strong>, yaitu kantung kecil yang bentuk dan sifat kimianya sesuai dengan substrat. Pada model <em>induced fit</em>, pengikatan substrat sedikit mengubah bentuk sisi aktif sehingga cocok lebih rapat. Karena itu satu enzim biasanya hanya bekerja pada satu atau sedikit substrat.</p>'
                . '<h3>Faktor yang memengaruhi laju reaksi</h3>'
                . '<ul><li><strong>Suhu</strong>: laju naik seiring suhu hingga suhu optimum, lalu turun tajam karena protein mengalami denaturasi.</li>'
                . '<li><strong>pH</strong>: setiap enzim punya pH optimum; perubahan pH mengubah muatan gugus asam amino dan bentuk sisi aktif.</li>'
                . '<li><strong>Konsentrasi substrat</strong>: laju naik lalu mendatar (jenuh) ketika semua enzim sedang terikat substrat.</li>'
                . '<li><strong>Konsentrasi enzim</strong>: bila substrat berlebih, laju kira-kira sebanding dengan jumlah enzim.</li></ul>'
                . '<h3>Pola saturasi</h3>'
                . '<p>Hubungan laju awal $v$ dengan konsentrasi substrat $[S]$ sering digambarkan dengan persamaan Michaelis–Menten:</p>'
                . '<p>$$v = \\frac{V_{max}\\,[S]}{K_m + [S]}$$</p>'
                . '<p>$V_{max}$ adalah laju maksimum, dan $K_m$ adalah konsentrasi substrat saat laju mencapai setengah $V_{max}$. Semakin kecil $K_m$, semakin tinggi afinitas enzim terhadap substrat.</p>',
            'key_concepts' => '<ul>'
                . '<li>Enzim menurunkan energi aktivasi; tidak mengubah kesetimbangan.</li>'
                . '<li>Sisi aktif bersifat spesifik; bentuknya bergantung pada struktur protein.</li>'
                . '<li>Denaturasi (suhu tinggi/pH ekstrem) merusak struktur sehingga aktivitas hilang, dan umumnya tidak pulih.</li>'
                . '<li>Kofaktor (ion logam) dan koenzim (mis. turunan vitamin) membantu aktivitas banyak enzim.</li>'
                . '<li>Inhibitor kompetitif: bersaing di sisi aktif. Inhibitor nonkompetitif: mengikat di tempat lain dan menurunkan $V_{max}$.</li></ul>',
            'osn_thinking' => '<p>Soal OSN jarang menanyakan definisi; biasanya Anda diminta <em>membaca grafik atau tabel</em> lalu menyimpulkan mekanisme. Gunakan urutan berikut:</p>'
                . '<ol><li>Tentukan variabel bebas dan terikat pada sumbu grafik.</li>'
                . '<li>Cari bagian kurva yang naik dan bagian yang mendatar; jelaskan penyebab masing-masing.</li>'
                . '<li>Bandingkan dua kurva (dengan dan tanpa perlakuan): apakah $V_{max}$ berubah? apakah $K_m$ tampak berubah?</li>'
                . '<li>Petakan ke mekanisme: $V_{max}$ sama dan $K_m$ tampak naik → kompetitif; $V_{max}$ turun dan $K_m$ sama → nonkompetitif.</li>'
                . '<li>Periksa asumsi: apakah substrat berlebih? apakah suhu/pH dijaga tetap?</li></ol>',
            'worked_example' => '<p><strong>Data hipotetis (contoh).</strong> Suatu enzim diuji tanpa zat X: $V_{max} = 100$ dan $K_m = 10$ (satuan arbitrer). Dengan zat X: $V_{max} = 100$ dan $K_m$ tampak $20$.</p>'
                . '<p><em>Analisis.</em> $V_{max}$ tidak berubah, artinya pada substrat sangat tinggi enzim tetap dapat mencapai laju yang sama. $K_m$ tampak naik, artinya dibutuhkan lebih banyak substrat untuk mencapai setengah $V_{max}$. Pola ini sesuai dengan zat X yang <strong>bersaing dengan substrat di sisi aktif</strong> (inhibitor kompetitif), sebab kelebihan substrat dapat "mengalahkan" inhibitor.</p>',
            'practice' => '<ol><li>Gambarkan sketsa kurva laju terhadap suhu, beri label suhu optimum dan jelaskan mengapa kurva turun di sebelah kanan.</li>'
                . '<li>Jika enzim terikat inhibitor nonkompetitif, bagaimana bentuk kurva $v$ terhadap $[S]$ dibanding tanpa inhibitor?</li>'
                . '<li>Jelaskan dengan kalimat sendiri mengapa menambah enzim tidak menaikkan laju bila substrat habis.</li></ol>'
                . '<p>Lanjutkan dengan latihan soal pada topik ini di menu Bank Soal.</p>',
            'summary' => '<ul><li>Enzim = katalis biologis; menurunkan energi aktivasi.</li><li>Sisi aktif spesifik; <em>induced fit</em>.</li>'
                . '<li>Suhu & pH: ada optimum, ekstrem menyebabkan denaturasi.</li><li>Substrat: kurva jenuh dengan $V_{max}$ dan $K_m$.</li>'
                . '<li>Kompetitif: $K_m$ tampak naik, $V_{max}$ tetap. Nonkompetitif: $V_{max}$ turun.</li></ul>',
            'glossary' => [
                ['term' => 'Enzim', 'definition' => 'Katalis biologis, umumnya protein, yang mempercepat reaksi tanpa habis terpakai.'],
                ['term' => 'Substrat', 'definition' => 'Molekul yang diubah oleh enzim.'],
                ['term' => 'Sisi aktif', 'definition' => 'Bagian enzim tempat substrat terikat dan reaksi berlangsung.'],
                ['term' => 'Energi aktivasi', 'definition' => 'Energi minimum yang dibutuhkan agar reaksi dapat dimulai.'],
                ['term' => 'Denaturasi', 'definition' => 'Kerusakan struktur tiga dimensi protein sehingga fungsinya hilang.'],
                ['term' => 'Kofaktor / koenzim', 'definition' => 'Zat nonprotein (ion logam / molekul organik) yang diperlukan beberapa enzim agar aktif.'],
                ['term' => 'Inhibitor', 'definition' => 'Zat yang menurunkan aktivitas enzim.'],
                ['term' => 'Km', 'definition' => 'Konsentrasi substrat saat laju reaksi setengah dari laju maksimum.'],
            ],
            'reference_list' => [
                ['text' => 'Campbell Biology (edisi ke-13) — bagian tentang enzim dan metabolisme (rujukan bacaan, bukan salinan).'],
                ['text' => 'OpenStax Biology 2e — bab metabolisme, bagian enzim.', 'url' => 'https://openstax.org/details/books/biology-2e'],
                ['text' => 'Panduan/silabus resmi OSN 2026 — verifikasi cakupan materi ke penyelenggara.'],
            ],
        ]);

        // Lesson CONTOH di modul berbayar & modul draft.
        $paid = Module::where('slug', 'contoh-modul-berbayar')->first();
        Lesson::updateOrCreate(['slug' => 'contoh-materi-berbayar'], [
            'module_id' => $paid->id, 'title' => '[Contoh] Materi berbayar — kerangka', 'difficulty' => 'pendalaman', 'est_minutes' => 15,
            'objectives' => '<p>CONTOH: target pembelajaran belum ditulis.</p>', 'explanation' => '<p>CONTOH konten berbayar. Hanya bisa dibuka dengan paket Full Course.</p>',
            'summary' => '<p>CONTOH ringkasan.</p>', 'status' => 'published', 'sort' => 1,
        ]);
        $draft = Module::where('slug', 'contoh-modul-draft')->first();
        Lesson::updateOrCreate(['slug' => 'contoh-materi-draft'], [
            'module_id' => $draft->id, 'title' => '[Contoh] Materi draft', 'difficulty' => 'fondasi', 'est_minutes' => 5,
            'explanation' => '<p>CONTOH konten draft.</p>', 'status' => 'draft', 'sort' => 1,
        ]);
    }

    private function questions(): void
    {
        $genetika = Topic::where('slug', 'genetika-dan-evolusi')->first();
        $sel = Topic::where('slug', 'biologi-sel-dan-molekuler')->first();
        $enzim = Module::where('slug', 'memahami-enzim')->first();

        $hw = $this->question('hw-1', [
            'topic_id' => $genetika->id, 'type' => 'mcq', 'difficulty' => 'pendalaman',
            'body' => '<p>Dalam populasi yang memenuhi asumsi keseimbangan Hardy–Weinberg, frekuensi genotipe resesif homozigot adalah 0,09. Berapa frekuensi alel dominan?</p>',
            'explanation' => [
                'correct_answer' => '<p><strong>C. 0,70</strong></p>',
                'concept_tested' => '<p>Keseimbangan Hardy–Weinberg: $p + q = 1$ dan $p^2 + 2pq + q^2 = 1$. Frekuensi genotipe resesif homozigot sama dengan $q^2$.</p>',
                'solution_steps' => '<ol><li>Frekuensi resesif homozigot: $q^2 = 0{,}09$.</li><li>Akarkan: $q = \\sqrt{0{,}09} = 0{,}30$.</li><li>Alel dominan: $p = 1 - q = 1 - 0{,}30 = 0{,}70$.</li></ol>',
                'why_others_wrong' => '<ul><li><strong>A (0,09)</strong>: ini $q^2$ (genotipe), bukan frekuensi alel dominan.</li><li><strong>B (0,30)</strong>: ini $q$, frekuensi alel resesif, sedangkan yang ditanya alel dominan.</li><li><strong>D (0,91)</strong>: ini $1 - q^2 = p^2 + 2pq$, frekuensi <em>fenotipe</em> dominan, bukan frekuensi alel.</li></ul>',
                'practical_trick' => '<p>Selalu mulai dari $q^2 \\rightarrow q \\rightarrow p$. Cek cepat: $p^2 + 2pq + q^2 = 0{,}49 + 0{,}42 + 0{,}09 = 1$.</p>',
                'common_mistakes' => '<ul><li>Lupa mengakarkan $q^2$ sehingga memakai 0,09 sebagai $q$.</li><li>Mencampur frekuensi alel dengan frekuensi genotipe/fenotipe.</li><li>Memakai rumus HW padahal populasi tidak memenuhi asumsi (mis. ada seleksi atau migrasi).</li></ul>',
                'similar_questions' => '<ol><li>Frekuensi individu resesif homozigot 0,04. Berapa frekuensi heterozigot? (petunjuk: $2pq = 0{,}32$)</li><li>Di populasi 10.000 orang, 1.600 berfenotipe resesif. Perkirakan jumlah pembawa (heterozigot).</li></ol>',
            ],
            'tags' => ['hardy-weinberg', 'genetika-populasi', 'perhitungan'],
            'options' => [['A', '0,09', false], ['B', '0,30', false], ['C', '0,70', true], ['D', '0,91', false]],
        ]);

        $e1 = $this->question('enzim-1', [
            'topic_id' => $sel->id, 'module_id' => $enzim->id, 'type' => 'mcq', 'difficulty' => 'pendalaman',
            'body' => '<p>[Soal contoh] Penambahan zat X pada reaksi enzimatik membuat $K_m$ tampak meningkat, sedangkan $V_{max}$ tidak berubah. Pernyataan yang paling tepat tentang zat X adalah ....</p>',
            'explanation' => [
                'correct_answer' => '<p><strong>A.</strong> Inhibitor kompetitif yang bersaing dengan substrat pada sisi aktif.</p>',
                'concept_tested' => '<p>Membaca perubahan $K_m$ dan $V_{max}$ untuk menentukan jenis inhibisi.</p>',
                'solution_steps' => '<ol><li>$V_{max}$ tetap → pada substrat sangat tinggi, laju maksimum masih bisa dicapai.</li><li>$K_m$ tampak naik → perlu lebih banyak substrat untuk setengah $V_{max}$.</li><li>Pola ini khas bila zat X menempati sisi aktif dan dapat diatasi oleh kelebihan substrat.</li></ol>',
                'why_others_wrong' => '<ul><li><strong>B</strong>: inhibitor nonkompetitif menurunkan $V_{max}$.</li><li><strong>C</strong>: koenzim yang meningkatkan afinitas justru menurunkan $K_m$.</li><li><strong>D</strong>: aktivator alosterik umumnya meningkatkan aktivitas, bukan menaikkan $K_m$ tampak dengan $V_{max}$ tetap.</li></ul>',
                'practical_trick' => '<p>Ingat: <em>kompetitif</em> → "bersaing", dapat dikalahkan substrat → $V_{max}$ tetap.</p>',
                'common_mistakes' => '<p>Tertukar antara kompetitif dan nonkompetitif; menganggap $K_m$ turun berarti afinitas turun (justru sebaliknya).</p>',
                'similar_questions' => '<p>Bagaimana grafik $v$ terhadap $[S]$ bila inhibitor nonkompetitif ditambahkan?</p>',
            ],
            'tags' => ['enzim', 'inhibisi', 'grafik', 'contoh'],
            'options' => [
                ['A', 'Inhibitor kompetitif yang bersaing dengan substrat pada sisi aktif', true],
                ['B', 'Inhibitor nonkompetitif yang mengikat di luar sisi aktif', false],
                ['C', 'Koenzim yang meningkatkan afinitas enzim terhadap substrat', false],
                ['D', 'Aktivator alosterik yang menstabilkan bentuk aktif enzim', false],
            ],
        ]);

        $e2 = $this->question('enzim-2', [
            'topic_id' => $sel->id, 'module_id' => $enzim->id, 'type' => 'truefalse', 'difficulty' => 'fondasi',
            'body' => '<p>[Soal contoh] Enzim menggeser letak kesetimbangan reaksi sehingga lebih banyak produk terbentuk.</p>',
            'explanation' => [
                'correct_answer' => '<p><strong>Salah.</strong></p>',
                'concept_tested' => '<p>Peran enzim sebagai katalis: memengaruhi kecepatan, bukan kesetimbangan.</p>',
                'solution_steps' => '<ol><li>Enzim menurunkan energi aktivasi untuk reaksi maju dan balik sekaligus.</li><li>Kedua arah dipercepat, sehingga rasio produk/reaktan pada kesetimbangan tidak berubah.</li></ol>',
                'why_others_wrong' => '<p>Pilihan "Benar" mengira enzim menambah hasil akhir; yang bertambah hanyalah kecepatan mencapai kesetimbangan.</p>',
                'practical_trick' => '<p>Katalis = "mempercepat sampai", bukan "mengubah tujuan".</p>',
                'common_mistakes' => '<p>Menyamakan percepatan reaksi dengan peningkatan hasil.</p>',
                'similar_questions' => '<p>Apakah enzim mengubah nilai $\\Delta G$ reaksi? Jelaskan.</p>',
            ],
            'tags' => ['enzim', 'katalis', 'contoh'],
            'options' => [['A', 'Benar', false], ['B', 'Salah', true]],
        ]);

        $e3 = $this->question('enzim-3', [
            'topic_id' => $sel->id, 'module_id' => $enzim->id, 'type' => 'essay', 'difficulty' => 'tantangan',
            'body' => '<p>[Soal contoh] Jelaskan mengapa laju reaksi enzimatik turun tajam pada suhu jauh di atas suhu optimum, dan mengapa penurunan suhu mendekati 0 °C berbeda sifatnya dari kenaikan suhu berlebih.</p>',
            'explanation' => [
                'correct_answer' => '<p>Suhu terlalu tinggi menyebabkan <strong>denaturasi</strong>: ikatan yang menjaga struktur 3D enzim rusak, sisi aktif berubah bentuk, dan perubahan ini umumnya tidak pulih. Suhu sangat rendah hanya mengurangi energi kinetik molekul sehingga tumbukan dan laju turun; struktur enzim tetap utuh dan aktivitas pulih saat suhu naik kembali.</p>',
                'concept_tested' => '<p>Pengaruh suhu terhadap laju reaksi vs. stabilitas struktur protein.</p>',
                'solution_steps' => '<ol><li>Sebutkan efek suhu naik: tumbukan lebih sering → laju naik sampai optimum.</li><li>Di atas optimum: denaturasi → sisi aktif rusak.</li><li>Bandingkan dengan suhu rendah: reversibel.</li></ol>',
                'why_others_wrong' => '<p>Soal uraian: jawaban yang hanya menyebut "enzim mati" tanpa menyebut perubahan struktur sisi aktif dianggap kurang lengkap.</p>',
                'practical_trick' => '<p>Gunakan kata kunci: denaturasi, sisi aktif, struktur tersier, reversibel/ireversibel.</p>',
                'common_mistakes' => '<p>Menyebut enzim sebagai makhluk hidup yang "mati"; tidak membedakan efek suhu rendah dan tinggi.</p>',
                'similar_questions' => '<p>Jelaskan mengapa pH ekstrem juga dapat menghentikan aktivitas enzim.</p>',
            ],
            'tags' => ['enzim', 'suhu', 'denaturasi', 'contoh'],
            'options' => [],
        ]);

        $quiz = Quiz::updateOrCreate(['title' => '[Contoh] Kuis Enzim & Hardy–Weinberg'], [
            'description' => 'Kuis contoh bertimer (durasi diatur admin).', 'duration_minutes' => 15,
            'access_level' => 0, 'status' => 'published', 'topic_id' => $sel->id,
        ]);
        $quiz->items()->delete();
        foreach ([$hw, $e1, $e2, $e3] as $i => $q) {
            $quiz->items()->create(['question_id' => $q->id, 'sort' => $i, 'weight' => $q->type === 'essay' ? 1 : 2]);
        }
    }

    /** Buat/ubah soal berdasarkan kunci seed (tag "seed:<key>") supaya seeder idempoten. */
    private function question(string $key, array $d): Question
    {
        $tag = "seed:$key";
        $existing = Question::whereHas('tags', fn ($t) => $t->where('tag', $tag))->first();
        $options = $d['options'];
        $tags = $d['tags'];
        unset($d['options'], $d['tags']);

        $q = $existing ?? new Question;
        $q->fill($d + ['access_level' => 0, 'status' => 'published'])->save();

        $q->options()->delete();
        foreach ($options as $i => [$label, $text, $correct]) {
            $q->options()->create(['label' => $label, 'text' => $text, 'is_correct' => $correct, 'sort' => $i]);
        }
        $q->tags()->delete();
        foreach (array_merge($tags, [$tag]) as $t) {
            $q->tags()->create(['tag' => $t]);
        }

        return $q;
    }
}
