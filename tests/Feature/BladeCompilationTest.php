<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Her Blade sablonu derlenip PHP soz dizimi acisindan denetlenir.
 *
 * Tek satirlik @php(...) icine ic ice parantezli bir cagri yazmak Blade'i
 * bozuyor ve sayfa yalnizca gercekten render edildiginde patliyor. Kasa
 * sayfasi bu yuzden hicbir testte yakalanmadan kirik kalmisti.
 */
class BladeCompilationTest extends TestCase
{
    public function test_every_blade_view_compiles_to_valid_php(): void
    {
        $files = Finder::create()
            ->files()
            ->in(resource_path('views'))
            ->name('*.blade.php');

        $broken = [];
        $checked = 0;

        foreach ($files as $file) {
            $compiled = Blade::compileString($file->getContents());
            $checked++;

            $error = $this->phpSyntaxError($compiled);

            if ($error !== null) {
                $broken[] = $file->getRelativePathname().' → '.$error;
            }
        }

        $this->assertGreaterThan(20, $checked, 'Beklenenden az sablon tarandi');
        $this->assertSame([], $broken, "Derlenemeyen sablon(lar):\n".implode("\n", $broken));
    }

    /** Derlenmis kaynagi ayristirip hata mesajini doner, sorun yoksa null. */
    private function phpSyntaxError(string $code): ?string
    {
        try {
            // eval yerine token bazli ayristirma: kod CALISTIRILMAZ.
            $parsed = @token_get_all($code, TOKEN_PARSE);
        } catch (\ParseError $e) {
            return $e->getMessage();
        }

        return $parsed === false ? 'ayristirilamadi' : null;
    }
}
