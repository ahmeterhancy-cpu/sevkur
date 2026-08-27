<?php

/*
 |  Indirilen demo fotograflarini konu kurallarina gore denetler.
 |  Kullanim: php tools/audit-demo-photos.php [--sil]
 */

require __DIR__.'/demo-photo-match.php';
$rules = require __DIR__.'/demo-photo-rules.php';
$dir = __DIR__.'/../public/demo/urunler';
$creditsFile = $dir.'/KAYNAKLAR.json';
$credits = json_decode(file_get_contents($creditsFile), true) ?: [];
$delete = in_array('--sil', $argv, true);

$bad = [];

foreach ($credits as $path => $info) {
    if (! preg_match('/urunler\/(.+)-\d+\.jpg$/', $path, $m)) {
        continue;
    }

    $type = $m[1];
    $rule = $rules[$type] ?? null;

    if (! $rule) {
        continue;
    }

    $haystack = mb_strtolower($info['baslik'].' '.basename($path));

    $verdict = subjectAllowed($haystack, $rule);

    if (! $verdict['uygun']) {
        $bad[$path] = [$type, $info['baslik'], $verdict['sebep']];
    }
}

foreach ($bad as $path => [$type, $title, $reason]) {
    printf("%-18s %-46s %s\n", $type, mb_substr($title, 0, 44), $reason);

    if ($delete) {
        @unlink(__DIR__.'/../public/'.$path);
        unset($credits[$path]);
    }
}

if ($delete) {
    file_put_contents($creditsFile, json_encode($credits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

printf("\n%d uygunsuz gorsel%s\n", count($bad), $delete ? ' silindi' : ' (silmek icin --sil)');
