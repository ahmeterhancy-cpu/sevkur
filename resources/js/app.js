/*
 |  Sevkur Mobilya — vitrin etkilesimleri
 |  Disaridan kutuphane kullanilmaz; her sey elle yazildi.
 |  JS kapaliyken icerik gorunur kalir, hareket tercihi kapaliysa animasyon durur.
 */

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* ------------------------------------------------------------------ bildirim */

function toast(message, type = 'success') {
    let stack = $('.toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'toast-stack';
        document.body.appendChild(stack);
    }
    const el = document.createElement('div');
    el.className = 'toast' + (type === 'error' ? ' toast-error' : '');
    el.setAttribute('role', 'status');
    el.textContent = message;
    stack.appendChild(el);
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transition = 'opacity .3s ease';
        setTimeout(() => el.remove(), 320);
    }, 3200);
}

async function postJson(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(data),
    });

    const payload = await response.json().catch(() => ({}));
    return { ok: response.ok, payload };
}

/* -------------------------------------------------------------- goruntuleme */

function initReveal() {
    const items = $$('.reveal');
    if (!items.length) return;

    if (!('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.05 }
    );

    items.forEach((el) => observer.observe(el));
}

/* ------------------------------------------------------------------- baslik */

function initHeader() {
    const header = $('[data-sticky-header]');
    if (!header) return;

    let ticking = false;

    const update = () => {
        header.classList.toggle('is-stuck', window.scrollY > 120);
        ticking = false;
    };

    window.addEventListener(
        'scroll',
        () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        },
        { passive: true }
    );

    update();
}

/* ------------------------------------------------------------------ panolar */

function openPanel(id) {
    const panel = document.getElementById(id);
    if (!panel) return;
    panel.classList.add('is-open');
    panel.setAttribute('aria-hidden', 'false');
    document.body.classList.add('no-scroll');
}

function closePanel(panel) {
    if (!panel) return;
    panel.classList.remove('is-open');
    panel.setAttribute('aria-hidden', 'true');
    if (!$('.panel.is-open')) document.body.classList.remove('no-scroll');
}

function initPanels() {
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-panel-open]');
        if (opener) {
            event.preventDefault();
            openPanel(opener.dataset.panelOpen);
            return;
        }

        const closer = event.target.closest('[data-panel-close]');
        if (closer) {
            event.preventDefault();
            closePanel(closer.closest('.panel'));
            return;
        }

        if (event.target.classList.contains('panel__backdrop')) {
            closePanel(event.target.closest('.panel'));
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            $$('.panel.is-open').forEach(closePanel);
            closeModal();
        }
    });

    // Mobil menude alt kategori acma
    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-submenu-toggle]');
        if (!toggle) return;
        event.preventDefault();
        const item = toggle.closest('[data-submenu-item]');
        item?.classList.toggle('is-open');
    });
}

/* ------------------------------------------------------------------- slider */

function initSliders() {
    $$('[data-slider]').forEach((root) => {
        const track = $('[data-slider-track]', root);
        const slides = $$('[data-slide]', root);
        if (!track || slides.length === 0) return;

        const dots = $$('[data-slider-dot]', root);
        const interval = Number(root.dataset.slider || 6000);
        let index = 0;
        let timer = null;

        const render = () => {
            track.style.transform = `translateX(-${index * 100}%)`;
            dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
            slides.forEach((slide, i) => slide.setAttribute('aria-hidden', String(i !== index)));
        };

        const go = (next) => {
            index = (next + slides.length) % slides.length;
            render();
        };

        const stop = () => timer && clearInterval(timer);

        const start = () => {
            if (slides.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            stop();
            timer = setInterval(() => go(index + 1), interval);
        };

        $$('[data-slider-prev]', root).forEach((b) => b.addEventListener('click', () => { go(index - 1); start(); }));
        $$('[data-slider-next]', root).forEach((b) => b.addEventListener('click', () => { go(index + 1); start(); }));
        dots.forEach((dot, i) => dot.addEventListener('click', () => { go(i); start(); }));

        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);

        let startX = null;
        root.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; stop(); }, { passive: true });
        root.addEventListener('touchend', (e) => {
            if (startX === null) return;
            const delta = e.changedTouches[0].clientX - startX;
            if (Math.abs(delta) > 45) go(index + (delta < 0 ? 1 : -1));
            startX = null;
            start();
        });

        render();
        start();
    });
}

/* -------------------------------------------------------------- yatay raylar */

function initRails() {
    $$('[data-rail]').forEach((root) => {
        const rail = $('[data-rail-track]', root);
        if (!rail) return;

        const step = () => rail.clientWidth * 0.8;

        $$('[data-rail-prev]', root).forEach((b) =>
            b.addEventListener('click', () => rail.scrollBy({ left: -step(), behavior: 'smooth' }))
        );
        $$('[data-rail-next]', root).forEach((b) =>
            b.addEventListener('click', () => rail.scrollBy({ left: step(), behavior: 'smooth' }))
        );
    });
}

/* -------------------------------------------------------------------- sepet */

function updateCartBadges(count, html) {
    $$('[data-cart-count]').forEach((el) => {
        el.textContent = count;
        el.classList.toggle('hidden', count === 0);
    });

    if (html) {
        const body = $('[data-mini-cart]');
        if (body) body.innerHTML = html;
    }
}

function initAddToCart() {
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-add-to-cart]');
        if (!form) return;

        event.preventDefault();

        const button = form.querySelector('[type="submit"]');
        button?.classList.add('is-disabled');

        const body = Object.fromEntries(new FormData(form).entries());
        const { ok, payload } = await postJson(form.action, body);

        button?.classList.remove('is-disabled');

        if (payload.message) toast(payload.message, ok ? 'success' : 'error');

        if (ok) {
            updateCartBadges(payload.count, payload.html);
            if (!form.hasAttribute('data-no-open')) openPanel('mini-cart-panel');
        }
    });
}

function refreshCartTotals(payload) {
    const map = { subtotal: payload.subtotal, discount: payload.discount, total: payload.total };
    Object.entries(map).forEach(([key, value]) => {
        if (value === undefined) return;
        $$(`[data-total="${key}"]`).forEach((el) => (el.textContent = value));
    });
}

function initCartPage() {
    document.addEventListener('click', async (event) => {
        const stepper = event.target.closest('[data-qty]');
        if (stepper) {
            const input = stepper.parentElement.querySelector('input[type="number"]');
            if (!input) return;
            const next = Math.max(1, Number(input.value || 1) + Number(stepper.dataset.qty));
            input.value = next;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        const remover = event.target.closest('[data-cart-remove]');
        if (remover) {
            event.preventDefault();
            const { ok, payload } = await postJson(remover.dataset.cartRemove, {
                item_id: remover.dataset.itemId,
            });
            if (ok) {
                if (payload.empty) {
                    window.location.reload();
                } else {
                    remover.closest('[data-cart-row]')?.remove();
                    updateCartBadges(payload.count, payload.html);
                    refreshCartTotals(payload);
                }
            }
        }
    });

    document.addEventListener('change', async (event) => {
        const input = event.target.closest('[data-cart-qty]');
        if (!input) return;

        const { ok, payload } = await postJson(input.dataset.cartQty, {
            item_id: input.dataset.itemId,
            quantity: input.value,
        });

        if (ok) {
            updateCartBadges(payload.count, payload.html);
            refreshCartTotals(payload);
            if (payload.empty) window.location.reload();
            else window.dispatchEvent(new CustomEvent('cart:updated'));
        }
    });

    // Satir toplamlari sunucu ile tutarli kalsin diye sepet sayfasi tazelenir.
    window.addEventListener('cart:updated', () => {
        if (document.body.dataset.page === 'cart') window.location.reload();
    });
}

/* --------------------------------------------------------- favori / karsilastir */

function initListToggles() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-toggle-list]');
        if (!button) return;

        event.preventDefault();

        const { ok, payload } = await postJson(button.dataset.toggleList, {
            product_id: button.dataset.productId,
        });

        if (!ok) return;

        if (payload.message) toast(payload.message, payload.limited ? 'error' : 'success');

        if (!payload.limited) {
            button.classList.toggle('is-active', payload.added);
            button.setAttribute('aria-pressed', String(payload.added));
        }

        const badge = button.dataset.badge;
        if (badge) {
            $$(`[data-${badge}-count]`).forEach((el) => {
                el.textContent = payload.count;
                el.classList.toggle('hidden', payload.count === 0);
            });
        }
    });
}

/* -------------------------------------------------------------- hizli bakis */

function closeModal() {
    const modal = $('[data-modal]');
    if (!modal) return;
    modal.remove();
    document.body.classList.remove('no-scroll');
}

function initQuickView() {
    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-quick-view]');
        if (!trigger) return;

        event.preventDefault();

        const modal = document.createElement('div');
        modal.dataset.modal = '';
        modal.className = 'fixed inset-0 z-[70] flex items-center justify-center p-4';
        modal.innerHTML =
            '<div class="absolute inset-0 bg-black/50" data-modal-close></div>' +
            '<div class="relative bg-white w-full max-w-4xl max-h-[90vh] overflow-y-auto"><div class="p-12 text-center text-muted">…</div></div>';
        document.body.appendChild(modal);
        document.body.classList.add('no-scroll');

        try {
            const response = await fetch(trigger.dataset.quickView, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            modal.querySelector('div.relative').innerHTML = await response.text();
            initProductGallery(modal);
        } catch (e) {
            closeModal();
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-modal-close]')) closeModal();
    });
}

/* ------------------------------------------------------------------ galeri */

function initProductGallery(root = document) {
    $$('[data-gallery]', root).forEach((gallery) => {
        const main = $('[data-gallery-main]', gallery);
        const thumbs = $$('[data-gallery-thumb]', gallery);
        if (!main || !thumbs.length) return;

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                main.src = thumb.dataset.galleryThumb;
                thumbs.forEach((t) => t.classList.remove('is-active'));
                thumb.classList.add('is-active');
            });
        });
    });
}

/* -------------------------------------------------------------------- sekme */

function initTabs() {
    $$('[data-tabs]').forEach((root) => {
        const buttons = $$('[data-tab]', root);
        const panels = $$('[data-tab-panel]', root);

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                buttons.forEach((b) => b.classList.toggle('is-active', b === button));
                panels.forEach((panel) =>
                    panel.classList.toggle('hidden', panel.dataset.tabPanel !== button.dataset.tab)
                );
            });
        });
    });
}

/* ------------------------------------------------------------------ akordeon */

function initAccordions() {
    $$('[data-accordion]').forEach((root) => {
        $$('[data-accordion-trigger]', root).forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const item = trigger.closest('[data-accordion-item]');
                const isOpen = item.classList.contains('is-open');

                if (!root.hasAttribute('data-accordion-multi')) {
                    $$('[data-accordion-item]', root).forEach((i) => i.classList.remove('is-open'));
                }

                item.classList.toggle('is-open', !isOpen);
                trigger.setAttribute('aria-expanded', String(!isOpen));
            });
        });
    });
}

/* ------------------------------------------------------------------- sayac */

function initCountdowns() {
    const nodes = $$('[data-countdown]');
    if (!nodes.length) return;

    const pad = (n) => String(n).padStart(2, '0');

    const tick = () => {
        const now = Date.now();

        nodes.forEach((node) => {
            const end = Number(node.dataset.countdown) * 1000;
            let diff = Math.max(0, end - now);

            const days = Math.floor(diff / 86400000); diff -= days * 86400000;
            const hours = Math.floor(diff / 3600000); diff -= hours * 3600000;
            const minutes = Math.floor(diff / 60000); diff -= minutes * 60000;
            const seconds = Math.floor(diff / 1000);

            const set = (key, value) => {
                const el = node.querySelector(`[data-unit="${key}"]`);
                if (el) el.textContent = pad(value);
            };

            set('days', days);
            set('hours', hours);
            set('minutes', minutes);
            set('seconds', seconds);
        });
    };

    tick();
    setInterval(tick, 1000);
}

/* -------------------------------------------------------------------- arama */

function initSearch() {
    const overlay = $('#search-panel');
    if (!overlay) return;

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-panel-open="search-panel"]')) {
            setTimeout(() => $('input[type="search"]', overlay)?.focus(), 120);
        }
    });
}

/* --------------------------------------------------------------- suzgecler */

function initFilters() {
    $$('[data-filter-form]').forEach((form) => {
        $$('input[type="checkbox"], select', form).forEach((input) => {
            input.addEventListener('change', () => form.submit());
        });
    });
}

/* ------------------------------------------------------------------ bulten */

function initNewsletter() {
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-newsletter]');
        if (!form) return;

        event.preventDefault();
        const body = Object.fromEntries(new FormData(form).entries());
        const { ok, payload } = await postJson(form.action, body);

        if (payload.message) toast(payload.message, ok ? 'success' : 'error');
        if (ok) form.reset();
    });
}

/* ------------------------------------------------------------- sunucu mesaji */

function initFlash() {
    $$('[data-flash]').forEach((flash) => {
        toast(flash.dataset.flash, flash.dataset.flashType || 'success');
        flash.remove();
    });
}

/* ---------------------------------------------------------------------- ana */

function boot() {
    initReveal();
    initHeader();
    initPanels();
    initSliders();
    initRails();
    initAddToCart();
    initCartPage();
    initListToggles();
    initQuickView();
    initProductGallery();
    initTabs();
    initAccordions();
    initCountdowns();
    initSearch();
    initFilters();
    initNewsletter();
    initFlash();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
