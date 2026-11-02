<?php

/**
 * Yasal metin taslaklari.
 *
 * Her metin database/data/legal/ altinda kendi dosyasinda; gocte de
 * ContentSeeder'da ayni kaynak okunur, boylece seeder tekrar calistirildiginda
 * metinler eski yer tutuculara DONMEZ.
 *
 * TASLAKTIR. Yayina almadan once hukuk danismaniniza okutun.
 *
 * @return array<int, array{slug: string, footer: bool, title: array, body: array}>
 */
return array_map(
    static fn (string $file) => require $file,
    glob(__DIR__.'/legal/*.php') ?: []
);
