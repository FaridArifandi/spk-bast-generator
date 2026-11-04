<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

/**
 * Mengisi template Word (.docx) asli BPS dengan placeholder <<KEY>>.
 *
 * Word sering memecah satu placeholder menjadi beberapa "run" XML
 * (<w:r><w:t>&lt;&lt;NA</w:t></w:r><w:r><w:t>MA&gt;&gt;</w:t></w:r>), jadi
 * sebelum diganti, teks placeholder disatukan dulu. Kunci dicocokkan tanpa
 * peduli huruf besar/kecil, spasi, underscore, strip, dan slash.
 */
class DocxTemplateFiller
{
    private const SKIP = ['styles', 'settings', 'fontTable', 'webSettings', 'theme', 'numbering'];

    /**
     * @param array<string,mixed> $context  kamus placeholder
     * @param array<int,array<string,string>> $baris  baris tabel lampiran (duplikasi baris yang memuat URAIAN_TUGAS)
     * @return string path file .docx sementara (hapus setelah dikirim)
     */
    public function isi(string $templatePath, array $context, array $baris = []): string
    {
        if (! is_file($templatePath)) {
            throw new RuntimeException("Template tidak ditemukan: $templatePath");
        }

        $tmp = tempnam(sys_get_temp_dir(), 'spk_').'.docx';
        copy($templatePath, $tmp);

        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            throw new RuntimeException('File template .docx tidak dapat dibuka.');
        }

        $kamus = [];
        foreach ($context as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $kamus[$this->normal((string) $k)] = (string) $v;
            }
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nama = $zip->getNameIndex($i);
            if (! str_starts_with($nama, 'word/') || ! str_ends_with($nama, '.xml')) {
                continue;
            }
            foreach (self::SKIP as $skip) {
                if (str_contains($nama, $skip)) {
                    continue 2;
                }
            }

            $xml = $zip->getFromIndex($i);
            if ($xml === false || ! str_contains($xml, '<<') && ! str_contains($xml, '&lt;&lt;') && ! str_contains($xml, '«')) {
                continue;
            }

            $xml = $this->satukanPlaceholder($xml);

            if ($nama === 'word/document.xml' && $baris) {
                $xml = $this->gandakanBaris($xml, $baris);
            }

            $xml = $this->ganti($xml, $kamus);

            // "Nomor Nomor" ganda akibat template + input
            $xml = preg_replace('/\b(nomor|NOMOR)\s+(nomor|NOMOR)\b/u', '$1', $xml) ?? $xml;

            $zip->addFromString($nama, $xml);
        }

        $zip->close();

        return $tmp;
    }

    /** Pola placeholder: <<...>> dalam bentuk ter-escape XML, mentah, atau «...» */
    private const POLA = '/(&lt;&lt;|<<|«)(.*?)(&gt;&gt;|>>|»)/su';

    private function satukanPlaceholder(string $xml): string
    {
        return preg_replace_callback(self::POLA, function ($m) {
            $bersih = trim(preg_replace('/<[^>]+>/', '', $m[2]));
            return '&lt;&lt;'.$bersih.'&gt;&gt;';
        }, $xml) ?? $xml;
    }

    private function ganti(string $xml, array $kamus): string
    {
        return preg_replace_callback(self::POLA, function ($m) use ($kamus) {
            $kunci = $this->normal(preg_replace('/<[^>]+>/', '', $m[2]));
            if (array_key_exists($kunci, $kamus)) {
                return $this->esc($kamus[$kunci]);
            }
            return $m[0];
        }, $xml) ?? $xml;
    }

    /** Cari <w:tr> yang memuat URAIAN_TUGAS lalu gandakan untuk tiap baris data. */
    private function gandakanBaris(string $xml, array $baris): string
    {
        $pos = strpos($xml, 'URAIAN_TUGAS');
        if ($pos === false) {
            return $xml;
        }

        $sebelum = substr($xml, 0, $pos);
        if (! preg_match_all('/<w:tr[ >]/', $sebelum, $m, PREG_OFFSET_CAPTURE) || ! $m[0]) {
            return $xml;
        }
        $awal = end($m[0])[1];
        $akhirTag = strpos($xml, '</w:tr>', $pos);
        if ($akhirTag === false) {
            return $xml;
        }
        $akhir = $akhirTag + strlen('</w:tr>');

        $templateBaris = substr($xml, $awal, $akhir - $awal);

        $hasil = '';
        foreach ($baris as $i => $row) {
            $kamusBaris = [];
            foreach ($row as $k => $v) {
                $kamusBaris[$this->normal($k)] = (string) $v;
            }
            $hasil .= $this->ganti($templateBaris, $kamusBaris);
        }

        return substr($xml, 0, $awal).$hasil.substr($xml, $akhir);
    }

    private function normal(string $k): string
    {
        return preg_replace('/[\s\-_\/]+/u', '', mb_strtoupper(trim($k))) ?? '';
    }

    private function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
