<?php

namespace Tests\Feature;

use App\Models\Slider;
use Tests\ShopTestCase;

/**
 * Hero yazi panosu.
 *
 * Slayt metni artik fotografin UZERINDE degil, yanindaki krem panoda
 * duruyor. Yoneticide beyaz yazi secilmis bir slaytta metin panoda
 * kaybolurdu; panel_ink acik renkleri murekkebe dusuruyor.
 */
class HeroPanelTest extends ShopTestCase
{
    public function test_a_light_text_colour_falls_back_to_ink_on_the_cream_panel(): void
    {
        $this->assertSame('var(--color-ink)', panel_ink('#ffffff'));
        $this->assertSame('var(--color-ink)', panel_ink('#eeeeee'));
    }

    public function test_a_dark_text_colour_is_kept(): void
    {
        $this->assertSame('#111111', panel_ink('#111111'));
        $this->assertSame('#2a3b4c', panel_ink('#2a3b4c'));
    }

    public function test_a_missing_or_broken_colour_falls_back_to_ink(): void
    {
        $this->assertSame('var(--color-ink)', panel_ink(null));
        $this->assertSame('var(--color-ink)', panel_ink(''));
        $this->assertSame('var(--color-ink)', panel_ink('mavi'));
    }

    public function test_the_three_digit_shorthand_is_understood(): void
    {
        $this->assertSame('#000000', panel_ink('#000'));
        $this->assertSame('var(--color-ink)', panel_ink('#fff'));
    }

    public function test_a_white_slide_still_renders_readable_text(): void
    {
        Slider::query()->update(['text_color' => '#ffffff']);

        $html = $this->get(localized_route('home'))->assertOk()->getContent();

        // Kategori mozaiginde beyaz yazi DOGRU (fotograf uzerinde duruyor),
        // bu yuzden kontrol yalnizca hero bolumune bakiyor.
        $hero = mb_substr($html, 0, mb_strpos($html, 'cat-tile') ?: mb_strlen($html));

        $this->assertStringNotContainsString('color: #ffffff', $hero);
        $this->assertStringContainsString('color: var(--color-ink)', $hero);
    }
}
