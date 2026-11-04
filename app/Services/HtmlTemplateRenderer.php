<?php

namespace App\Services;

use App\Support\Indonesia as Id;

/**
 * Merender template HTML dokumen (untuk pratinjau & cetak lewat browser).
 * Nilai selalu di-escape; hanya tabel lampiran yang disusun di sini sebagai HTML.
 */
class HtmlTemplateRenderer
{
    public function render(string $file, array $context, array $baris = []): string
    {
        $path = resource_path('doc-templates/html/'.$file);
        $html = file_get_contents($path);

        $context['TABEL_LAMPIRAN_ROWS'] = null;
        $tabel = $this->tabelLampiran($baris);

        return preg_replace_callback('/<<([A-Z0-9_\/\-]+)>>/', function ($m) use ($context, $tabel) {
            $k = $m[1];
            if ($k === 'TABEL_LAMPIRAN_ROWS') {
                return $tabel;
            }
            if (array_key_exists($k, $context) && $context[$k] !== null) {
                return e((string) $context[$k]);
            }
            return e($m[0]); // biarkan terlihat agar data yang kurang mudah ketahuan
        }, $html);
    }

    private function tabelLampiran(array $baris): string
    {
        if (! $baris) {
            return '<tr><td colspan="8" style="text-align:center;color:#999;">Tidak ada data pekerjaan</td></tr>';
        }

        $out = '';
        foreach ($baris as $r) {
            $out .= '<tr>'
                .'<td class="tc">'.e($r['NO_URUT']).'</td>'
                .'<td>'.e($r['URAIAN_TUGAS']).'</td>'
                .'<td>'.e($r['JANGKA_WAKTU']).'</td>'
                .'<td class="tc">'.e($r['VOLUME']).'</td>'
                .'<td class="tc">'.e($r['SATUAN']).'</td>'
                .'<td class="tr">Rp '.e($r['HARGA_SATUAN']).'</td>'
                .'<td class="tr">Rp '.e($r['NILAI_PERJANJIAN']).'</td>'
                .'<td style="font-size:7.5pt;">'.e($r['BEBAN_ANGGARAN']).'</td>'
                .'</tr>';
        }
        return $out;
    }
}
