<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Slider;
use Illuminate\Console\Command;

/**
 * Vitrindeki demo gorselleri GERCEK urun fotograflariyla degistirir.
 *
 * Kategori kartlari, slider ve afisler kurulumdan beri Wikimedia Commons
 * fotograflarini gosteriyordu: "Calisma Odasi" tekerlekli sandalye,
 * "Aydinlatma" antika terazi, slider'da muzelik oymali kanepe. Katalog
 * gercek urunlerle dolduktan sonra bunlarin durmasi icin sebep yok.
 *
 * Secim: kategorinin KENDI agacindaki urunlerin kapaklari arasindan, hedef
 * en-boy oranina en yakin olani. Kart kareye yakin, slider ise cok genis;
 * ayni fotografi iki yerde kullanmamak icin kullanilanlar isaretleniyor.
 *
 * Urunu olmayan kategoriye dokunulmaz — konacak fotograf yok, uydurmak da
 * demo gorselin yaptigi hatanin aynisi olurdu.
 */
class RefreshStorefrontImages extends Command
{
    protected $signature = 'vitrin:gorsel
        {--hepsi : Elle seçilmiş görselleri de değiştir}
        {--kuru : Hiçbir şey yazma, ne yapılacağını göster}';

    protected $description = 'Kategori, slider ve afiş görsellerini ürün fotoğraflarıyla değiştirir';

    /** Kart kareye yakin duruyor. */
    private const CARD_RATIO = 1.1;

    /** Slider serit halinde, cok genis. */
    private const SLIDER_RATIO = 2.4;

    /** Afisler yatay dikdortgen. */
    private const BANNER_RATIO = 1.6;

    /** Blog kartlari yatay dikdortgen. */
    private const POST_RATIO = 1.5;

    /**
     * Blog yazisi => fotografin alinacagi kategori.
     *
     * Yazinin konusu neyse fotograf o raftan gelsin; kadife/keten rehberine
     * yemek masasi fotografi koymanin anlami yok.
     */
    private const POST_CATEGORIES = [
        'kucuk-oturma-odasini-buyuk-gosteren-7-detay' => 'koltuk-takimlari',
        'notr-tonlarla-sicak-bir-ev' => 'koltuk-takimlari',
        'kadife-mi-keten-mi' => 'berjer-ve-koltuk',
        'masif-ahsap-mobilya-bakimi' => 'yemek-odasi-takimlari',
        'yemek-masasi-secerken' => 'yemek-masalari',
        'yatak-odasinda-isik-plani' => 'yatak-odasi-takimlari',
    ];

    /** Kac aday fotograf cozumlenip sahne kontrolunden gecirilir. */
    private const SCENE_CHECK_LIMIT = 25;

    /** Kenar pikselinin bu oranindan fazlasi beyazsa kesme cekimdir. */
    private const MAX_WHITE_EDGE = 0.6;

    /** Slider sirasi => hangi kategoriden fotograf alinsin. */
    private const SLIDER_CATEGORIES = [
        'koltuk-takimlari',
        'kose-takimlari',
        'yatak-odasi-takimlari',
    ];

    /**
     * Oda kategorisi => kartta hangi alt kategoriden fotograf alinsin.
     *
     * Butun agactan secince "Yatak Odasi" kartina ranza fotografi dustu:
     * dogru kategoride ama odanin yuzu olmaz. "Takimlari" alt kategorileri
     * tam oda cekimleri tasiyor, kart icin dogru yer orasi.
     */
    private const ROOM_FACE = [
        'oturma-odasi' => 'koltuk-takimlari',
        'yatak-odasi' => 'yatak-odasi-takimlari',
        'yemek-odasi' => 'yemek-odasi-takimlari',
        'calisma-odasi' => 'calisma-masalari',
        'dekorasyon' => 'aynalar',
    ];

    /** @var array<string, true> Bu calistirmada kullanilmis kapaklar */
    private array $used = [];

    /** Son secimde sahne bulunamayip kesme cekime dusuldu mu? */
    private bool $fellBackToCutout = false;

    /**
     * Bu gorsel degistirilmeli mi?
     *
     * Varsayilan olarak yalnizca KURULUMDAN KALMA demo gorseller degisir.
     * Otomatik secim fotografin konusunu anlamiyor — genis afise acilan
     * yatakli koltugun ciplak siltesi dusmustu — bu yuzden yonetimden ya da
     * elle secilen gorseller korunur, yoksa her calistirma onlari eziyordu.
     */
    private function shouldReplace(?string $current): bool
    {
        if ($this->option('hepsi')) {
            return true;
        }

        return $current === null || $current === '' || str_starts_with($current, 'demo/');
    }

    public function handle(): int
    {
        $dry = (bool) $this->option('kuru');

        $this->categories($dry);
        $this->sliders($dry);
        $this->banners($dry);
        $this->posts($dry);

        if ($dry) {
            $this->newLine();
            $this->info('[KURU] Hiçbir şey yazılmadı.');
        }

        return self::SUCCESS;
    }

    private function categories(bool $dry): void
    {
        $this->line('<comment>Kategoriler</comment>');

        $empty = [];
        $kept = [];

        foreach (Category::orderBy('parent_id')->orderBy('position')->get() as $category) {
            if (! $this->shouldReplace($category->image)) {
                $kept[] = $category->slug;

                continue;
            }

            $cover = $this->pick($this->categoryProductIds($category), self::CARD_RATIO);

            if ($cover === null) {
                $empty[] = $category->slug;

                continue;
            }

            $this->line(sprintf('  %-26s %s', $category->slug, $cover));

            if (! $dry) {
                $category->update(['image' => $cover]);
            }
        }

        if ($empty !== []) {
            $this->warn('  Ürünü olmayan kategoriler atlandı: '.implode(', ', $empty));
        }

        if ($kept !== []) {
            $this->line('  <fg=gray>Elle seçilmiş olanlara dokunulmadı: '.implode(', ', $kept).'</>');
        }
    }

    private function sliders(bool $dry): void
    {
        $this->newLine();
        $this->line('<comment>Slider</comment>');

        foreach (Slider::orderBy('position')->get()->values() as $index => $slider) {
            if (! $this->shouldReplace($slider->image)) {
                $this->line(sprintf('  #%-3d <fg=gray>elle seçilmiş, dokunulmadı</>', $slider->id));

                continue;
            }

            $slug = self::SLIDER_CATEGORIES[$index] ?? self::SLIDER_CATEGORIES[0];
            $category = Category::where('slug', $slug)->first();

            $cover = $category === null
                ? null
                : $this->pick($this->categoryProductIds($category), self::SLIDER_RATIO);

            if ($cover === null) {
                $this->warn(sprintf('  #%d (%s) için fotoğraf bulunamadı', $slider->id, $slug));

                continue;
            }

            $this->line(sprintf('  #%-3d %-22s %s', $slider->id, $slug, $cover));

            if (! $dry) {
                $slider->update(['image' => $cover]);
            }
        }
    }

    private function banners(bool $dry): void
    {
        $this->newLine();
        $this->line('<comment>Afişler</comment>');

        foreach (Banner::orderBy('placement')->get() as $banner) {
            if (! $this->shouldReplace($banner->image)) {
                $this->line(sprintf('  %-12s <fg=gray>elle seçilmiş, dokunulmadı</>', $banner->placement));

                continue;
            }

            // Afis zaten bir kategoriye baglaniyor; fotograf oradan gelsin.
            $slug = $this->categorySlugFromLink($banner->link);
            $category = $slug === null ? null : Category::where('slug', $slug)->first();

            $cover = $category === null
                ? null
                : $this->pick($this->categoryProductIds($category), self::BANNER_RATIO);

            if ($cover === null) {
                $this->warn(sprintf(
                    '  %-12s %-22s atlandı (%s)',
                    $banner->placement,
                    mb_substr($banner->t('title') ?? '-', 0, 22),
                    $slug === null ? 'kategori bağlantısı yok' : $slug.' boş'
                ));

                continue;
            }

            $this->line(sprintf('  %-12s %-22s %s', $banner->placement, $slug, $cover));

            if (! $dry) {
                $banner->update(['image' => $cover]);
            }
        }
    }

    private function posts(bool $dry): void
    {
        $this->newLine();
        $this->line('<comment>Blog</comment>');

        foreach (Post::orderBy('id')->get() as $post) {
            if (! $this->shouldReplace($post->cover)) {
                $this->line(sprintf('  %-44s <fg=gray>elle seçilmiş, dokunulmadı</>', mb_substr($post->slug, 0, 44)));

                continue;
            }

            $slug = self::POST_CATEGORIES[$post->slug] ?? null;
            $category = $slug === null ? null : Category::where('slug', $slug)->first();

            $cover = $category === null
                ? null
                : $this->pick($this->categoryProductIds($category), self::POST_RATIO);

            if ($cover === null) {
                $this->warn(sprintf(
                    '  %-44s atlandı (%s)',
                    mb_substr($post->slug, 0, 44),
                    $slug === null ? 'eşleşme tanımlı değil' : $slug.' boş'
                ));

                continue;
            }

            $this->line(sprintf(
                '  %-44s %-22s %s%s',
                mb_substr($post->slug, 0, 44),
                $slug,
                $cover,
                $this->fellBackToCutout ? '  ⚠ sahne yok, kesme çekim' : ''
            ));

            if (! $dry) {
                $post->update(['cover' => $cover]);
            }
        }
    }

    /** Kategori ve ALT kategorilerindeki yayinda urun id'leri. */
    private function categoryProductIds(Category $category): array
    {
        // Oda kategorisinin yuzu belirlenmisse once orayi dene.
        if (isset(self::ROOM_FACE[$category->slug])) {
            $face = Category::where('slug', self::ROOM_FACE[$category->slug])->first();

            if ($face !== null) {
                $ids = Product::where('category_id', $face->id)
                    ->where('is_active', true)
                    ->whereNotNull('cover')
                    ->pluck('id')
                    ->all();

                if ($ids !== []) {
                    return $ids;
                }
            }
        }

        $ids = Category::where('parent_id', $category->id)->pluck('id')->push($category->id);

        return Product::whereIn('category_id', $ids)
            ->where('is_active', true)
            ->whereNotNull('cover')
            ->pluck('id')
            ->all();
    }

    /**
     * Hedef en-boy oranina en yakin, henuz kullanilmamis kapak.
     *
     * @param  array<int, int>  $productIds
     */
    private function pick(array $productIds, float $target): ?string
    {
        if ($productIds === []) {
            return null;
        }

        $this->fellBackToCutout = false;
        $ranked = [];

        foreach (Product::whereIn('id', $productIds)->pluck('cover') as $cover) {
            if ($cover === null || isset($this->used[$cover])) {
                continue;
            }

            $path = public_path($cover);

            if (! is_file($path)) {
                continue;
            }

            $size = @getimagesize($path);

            if (! $size || $size[1] === 0) {
                continue;
            }

            $ranked[$cover] = abs(($size[0] / $size[1]) - $target);
        }

        asort($ranked);

        // Orana gore siraya dizip ILK SAHNE fotografini al. Beyaz fonda
        // kesilmis urun cekimi vitrinde ise yaramiyor: buyuk kutuda bos
        // duruyor ve uzerindeki beyaz yazi okunmuyordu (Calisma Odasi'na
        // masa, Dekorasyon'a ayna fotografi dusmustu). Cozumleme pahali
        // oldugu icin yalnizca en iyi birkac aday acilir.
        $checked = 0;

        foreach (array_keys($ranked) as $cover) {
            if ($checked++ >= self::SCENE_CHECK_LIMIT) {
                break;
            }

            if ($this->looksLikeScene(public_path($cover))) {
                $this->used[$cover] = true;

                return $cover;
            }
        }

        // Hicbiri sahne degilse orani en uygun olani kullan — gorselsiz
        // birakmaktan iyidir. Ama SESSIZCE yapma: blog gorsellerinde bu
        // durum "Kadife mi, keten mi?" yazisina beyaz fonda kesilmis deri
        // koltuk fotografi koymustu ve fark edilmeden gecti.
        $best = array_key_first($ranked);

        if ($best !== null) {
            $this->used[$best] = true;
            $this->fellBackToCutout = true;
        }

        return $best;
    }

    /**
     * Fotograf bir MEKAN cekimi mi, yoksa beyaz fonda kesilmis urun mu?
     *
     * Kesme cekimlerin kenarlari neredeyse tamamen beyazdir; mekan
     * cekimlerinde duvar, zemin ve isik degisir. Kenar seridi orneklenip
     * beyaza yakin piksel orani olculuyor.
     */
    private function looksLikeScene(string $path): bool
    {
        $image = @imagecreatefromjpeg($path);

        if ($image === false) {
            return false;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $samples = 0;
        $white = 0;

        // Dort kenardan esit arayla ornek al
        for ($i = 0; $i < 40; $i++) {
            $x = (int) ($w * $i / 40);
            $y = (int) ($h * $i / 40);

            foreach ([[$x, 2], [$x, $h - 3], [2, $y], [$w - 3, $y]] as [$px, $py]) {
                $rgb = @imagecolorat($image, max(0, min($w - 1, $px)), max(0, min($h - 1, $py)));

                if ($rgb === false) {
                    continue;
                }

                $samples++;

                if ((($rgb >> 16) & 0xFF) > 242 && (($rgb >> 8) & 0xFF) > 242 && ($rgb & 0xFF) > 242) {
                    $white++;
                }
            }
        }

        imagedestroy($image);

        return $samples > 0 && ($white / $samples) < self::MAX_WHITE_EDGE;
    }

    /** "/kategori/yemek-masalari" -> "yemek-masalari" */
    private function categorySlugFromLink(?string $link): ?string
    {
        if ($link === null || $link === '') {
            return null;
        }

        $parts = array_values(array_filter(explode('/', parse_url($link, PHP_URL_PATH) ?? '')));

        return count($parts) >= 2 ? end($parts) : null;
    }
}
