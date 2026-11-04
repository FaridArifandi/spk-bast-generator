<?php

namespace Database\Seeders;

/** Membaca berkas JSON data awal di database/seeders/data. */
trait BacaDataSeed
{
    /** @return array<int,array<string,mixed>> */
    protected function dataSeed(string $nama): array
    {
        $path = __DIR__.'/data/'.$nama.'.json';
        if (! is_file($path)) {
            $this->command?->warn("Berkas data seed {$nama}.json tidak ditemukan, dilewati.");

            return [];
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
