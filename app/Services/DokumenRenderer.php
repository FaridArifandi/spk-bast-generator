<?php

namespace App\Services;

use App\Models\BastSmPpk;
use App\Models\Dokumen;

/**
 * Pintu tunggal untuk membuat pratinjau HTML dan berkas Word dari Dokumen / BastSmPpk.
 */
class DokumenRenderer
{
    public const JENIS = [
        'spk' => 'Perjanjian Kerja (SPK)',
        'ppl-pml' => 'BAST PPL → PML',
        'ppl-sm' => 'BAST PPL → SM',
        'pml-sm' => 'BAST PML → SM',
        'entri-sm' => 'BAST Entri → SM',
        'sm-ppk' => 'BAST SM → PPK',
    ];

    public function __construct(
        private DokumenContextBuilder $builder,
        private HtmlTemplateRenderer $html,
        private DocxTemplateFiller $docx,
    ) {}

    /** @return array{file:string, docx:string} nama berkas template (tanpa ekstensi) */
    public function pilihTemplate(string $jenis, bool $pmlMitra): array
    {
        $nama = match ($jenis) {
            'spk' => 'spk',
            'ppl-pml' => 'bast-ppl-pml-'.($pmlMitra ? 'mitra' : 'organik'),
            'ppl-sm' => 'bast-ppl-sm',
            'pml-sm' => 'bast-pml-sm-'.($pmlMitra ? 'mitra' : 'organik'),
            'entri-sm' => 'bast-entri-sm',
            'sm-ppk' => 'bast-sm-ppk',
            default => abort(404, 'Jenis dokumen tidak dikenal.'),
        };
        return ['file' => $nama.'.html', 'docx' => $nama.'.docx'];
    }

    public function html(Dokumen $d, string $jenis): string
    {
        $ctx = $this->builder->build($d);
        $tpl = $this->pilihTemplate($jenis, $ctx['pml_mitra']);
        return $this->html->render($tpl['file'], $ctx[$jenis], $ctx['details']);
    }

    public function htmlSmPpk(BastSmPpk $b): string
    {
        return $this->html->render('bast-sm-ppk.html', $this->builder->buildSmPpk($b));
    }

    /** @return array{path:string,nama:string} */
    public function word(Dokumen $d, string $jenis): array
    {
        $ctx = $this->builder->build($d);
        $tpl = $this->pilihTemplate($jenis, $ctx['pml_mitra']);
        $path = $this->docx->isi(
            resource_path('doc-templates/docx/'.$tpl['docx']),
            $ctx[$jenis],
            $jenis === 'spk' ? $ctx['details'] : []
        );

        $petugas = preg_replace('/[^A-Za-z0-9]+/', '_', $d->petugas_nama) ?: 'petugas';
        $prefix = $jenis === 'spk' ? 'SPK' : 'BAST_'.strtoupper(str_replace('-', '_', $jenis));

        return ['path' => $path, 'nama' => trim("{$prefix}_{$petugas}_{$d->bulan}_{$d->tahun}", '_').'.docx'];
    }

    /** @return array{path:string,nama:string} */
    public function wordSmPpk(BastSmPpk $b): array
    {
        $path = $this->docx->isi(resource_path('doc-templates/docx/bast-sm-ppk.docx'), $this->builder->buildSmPpk($b));
        return ['path' => $path, 'nama' => "BAST_SM_PPK_{$b->id}_{$b->bulan}_{$b->tahun}.docx"];
    }
}
