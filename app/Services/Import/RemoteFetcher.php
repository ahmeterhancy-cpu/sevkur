<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Http;

/**
 * Disaridan icerik indirir.
 *
 * GUVENLIK: Adresi yonetici giriyor. Onlem alinmazsa panelden
 * "http://127.0.0.1/…" ya da bulut saglayicilarin 169.254.169.254 metadata
 * ucu istenip sunucunun IC AGINA erisilebilir (SSRF). Bu yuzden:
 *   - yalnizca http/https,
 *   - alan adi cozulup OZEL/YEREL IP araliklari reddedilir,
 *   - yonlendirmeler elle takip edilir ve her adimda ayni denetim yapilir,
 *   - boyut ve sure sinirlanir.
 */
class RemoteFetcher
{
    private const MAX_REDIRECTS = 4;

    private const TIMEOUT = 20;

    private const MAX_HTML_BYTES = 3_000_000;

    private const MAX_IMAGE_BYTES = 8_000_000;

    /** @var (callable(string): array<int, string>)|null */
    private mixed $resolver = null;

    /**
     * Alan adi cozumleyiciyi degistirir.
     *
     * Testlerde gercek DNS sorgusu yapilmasin diye var; koruma mantigi
     * (ozel/yerel IP reddi) aynen calismaya devam eder.
     *
     * @param  callable(string): array<int, string>  $resolver
     */
    public function resolveUsing(callable $resolver): void
    {
        $this->resolver = $resolver;
    }

    /** @return array<int, string> */
    private function resolve(string $host): array
    {
        if ($this->resolver !== null) {
            return ($this->resolver)($host);
        }

        $addresses = [];

        foreach ([DNS_A, DNS_AAAA] as $type) {
            foreach (@dns_get_record($host, $type) ?: [] as $record) {
                $addresses[] = $record['ip'] ?? $record['ipv6'] ?? null;
            }
        }

        $addresses = array_values(array_filter($addresses));

        if ($addresses === []) {
            $resolved = gethostbyname($host);

            if ($resolved !== $host) {
                $addresses[] = $resolved;
            }
        }

        return $addresses;
    }

    /**
     * @return array{ok: bool, body: string|null, url: string, error: string|null}
     */
    public function html(string $url): array
    {
        return $this->get($url, self::MAX_HTML_BYTES, ['text/html', 'application/xhtml+xml']);
    }

    /**
     * Site haritasi gibi XML belgeler.
     *
     * @return array{ok: bool, body: string|null, url: string, error: string|null}
     */
    public function xml(string $url): array
    {
        return $this->get($url, self::MAX_HTML_BYTES, ['xml', 'text/plain']);
    }

    /**
     * @return array{ok: bool, body: string|null, url: string, error: string|null}
     */
    public function image(string $url): array
    {
        return $this->get($url, self::MAX_IMAGE_BYTES, ['image/']);
    }

    /**
     * @param  array<int, string>  $allowedTypes
     * @return array{ok: bool, body: string|null, url: string, error: string|null}
     */
    private function get(string $url, int $maxBytes, array $allowedTypes): array
    {
        $current = $url;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $guard = $this->guard($current);

            if ($guard !== null) {
                return ['ok' => false, 'body' => null, 'url' => $current, 'error' => $guard];
            }

            try {
                $response = Http::withoutRedirecting()
                    ->withoutVerifying()
                    ->timeout(self::TIMEOUT)
                    ->withHeaders([
                        'User-Agent' => 'SevkurMobilya/1.0 (urun ice aktarma; +'.config('app.url').')',
                        'Accept' => '*/*',
                    ])
                    ->get($current);
            } catch (\Throwable $e) {
                return ['ok' => false, 'body' => null, 'url' => $current, 'error' => 'Adrese ulaşılamadı: '.$e->getMessage()];
            }

            if ($response->redirect()) {
                $location = $response->header('Location');

                if ($location === null || $location === '') {
                    return ['ok' => false, 'body' => null, 'url' => $current, 'error' => 'Yönlendirme adresi okunamadı.'];
                }

                $current = $this->absolute($location, $current);

                continue;
            }

            if (! $response->successful()) {
                return ['ok' => false, 'body' => null, 'url' => $current, 'error' => 'Sunucu '.$response->status().' döndü.'];
            }

            $type = strtolower((string) $response->header('Content-Type'));
            $typeOk = false;

            foreach ($allowedTypes as $allowed) {
                if (str_contains($type, $allowed)) {
                    $typeOk = true;
                    break;
                }
            }

            if (! $typeOk) {
                return ['ok' => false, 'body' => null, 'url' => $current, 'error' => 'Beklenmeyen içerik türü: '.($type ?: 'bilinmiyor')];
            }

            $body = $response->body();

            if (strlen($body) > $maxBytes) {
                return ['ok' => false, 'body' => null, 'url' => $current, 'error' => 'Dosya çok büyük.'];
            }

            return ['ok' => true, 'body' => $body, 'url' => $current, 'error' => null];
        }

        return ['ok' => false, 'body' => null, 'url' => $current, 'error' => 'Çok fazla yönlendirme.'];
    }

    /**
     * Adres guvenli mi? Sorun varsa Turkce sebep, yoksa null doner.
     */
    public function guard(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return 'Adres okunamadı.';
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            return 'Yalnızca http ve https adresleri desteklenir.';
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (! in_array($port, [80, 443, 8080, 8443], true)) {
            return 'Bu port kapalı: '.$port;
        }

        $host = $parts['host'];

        // Alan adini coz; hem IPv4 hem IPv6 kayitlarina bak
        $addresses = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses[] = $host;
        } else {
            $addresses = $this->resolve($host);
        }

        if ($addresses === []) {
            return 'Alan adı çözülemedi: '.$host;
        }

        foreach ($addresses as $address) {
            if ($this->isPrivate($address)) {
                return 'Yerel/özel ağ adresleri içe aktarılamaz ('.$address.').';
            }
        }

        return null;
    }

    /** Ozel, yerel, baglanti-yerel ve ayrilmis araliklar. */
    private function isPrivate(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        // filter_var bu bayraklarla genel olmayan adresleri eler
        $public = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($public === false) {
            return true;
        }

        // Bulut metadata ucu ozellikle engellenir
        return str_starts_with($ip, '169.254.');
    }

    /** Goreli adresi mutlak hale getirir. */
    public function absolute(string $candidate, string $base): string
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            return $base;
        }

        if (str_starts_with($candidate, '//')) {
            return (parse_url($base, PHP_URL_SCHEME) ?: 'https').':'.$candidate;
        }

        if (preg_match('#^https?://#i', $candidate)) {
            return $candidate;
        }

        $parts = parse_url($base);
        $root = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '')
            .(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($candidate, '/')) {
            return $root.$candidate;
        }

        $path = $parts['path'] ?? '/';
        $directory = str_ends_with($path, '/') ? $path : dirname($path).'/';

        return $root.$directory.$candidate;
    }
}
