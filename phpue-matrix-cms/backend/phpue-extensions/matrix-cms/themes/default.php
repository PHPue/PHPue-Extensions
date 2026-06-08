<?php
// themes/default.php
// PHPue Matrix — Editorial Blog Theme
// Zero-database, pure PHP rendering via closures.

return [
    'meta' => [
        'name' => 'Editorial Blog Theme',
        'version' => '2.0',
        'author' => 'Edward Patch',
        'description' => 'A comprehensive blog theme with 15+ section types. No database required.',
    ],

    'shortcodes' => [
        // Wildcard: any [[phpueLang.something]] → $GLOBALS['phpueLang']['something']
        'phpueLang' => fn($key) => \PHPueExt\PHPueMatrix::$phpueLang[$key] ?? "[[phpueLang.{$key}]]",
        
        // Static shortcodes
        'year'      => fn() => date('Y'),
        'site_url'  => fn() => ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'],
    ],

    'sections' => [

        // ═══════════════════════════════════════════
        // HERO VARIANTS
        // ═══════════════════════════════════════════

        'hero' => [
            'label' => 'Hero Banner (Full)',
            'fields' => ['heading', 'subheading', 'bg_image', 'cta_text', 'cta_url'],
            'render' => function (array $data): string {
                $heading    = htmlspecialchars($data['heading'] ?? '');
                $sub        = htmlspecialchars($data['subheading'] ?? '');
                $bg         = htmlspecialchars($data['bg_image'] ?? 'https://picsum.photos/1920/1080');
                $ctaText    = htmlspecialchars($data['cta_text'] ?? '');
                $ctaUrl     = htmlspecialchars($data['cta_url'] ?? '#');
                $ctaHtml    = $ctaText ? "<a href=\"{$ctaUrl}\" class=\"inline-block mt-6 bg-white text-stone-900 px-8 py-3 rounded-full font-semibold text-sm uppercase tracking-wider hover:bg-stone-100 transition\">{$ctaText}</a>" : '';
                return <<<HTML
                <section class="relative h-[70vh] flex items-center justify-center bg-cover bg-center" style="background-image:url('{$bg}')">
                    <div class="absolute inset-0 bg-gradient-to-b from-stone-900/60 to-stone-900/40"></div>
                    <div class="relative z-10 text-center text-white px-6 max-w-4xl">
                        <h1 class="font-serif text-5xl md:text-7xl tracking-wide leading-tight">{$heading}</h1>
                        <p class="mt-6 text-lg md:text-xl opacity-90 max-w-2xl mx-auto">{$sub}</p>
                        {$ctaHtml}
                    </div>
                </section>
                HTML;
            },
        ],

        'hero-minimal' => [
            'label' => 'Hero Banner (Minimal)',
            'fields' => ['heading', 'subheading'],
            'render' => function (array $data): string {
                $heading = htmlspecialchars($data['heading'] ?? '');
                $sub     = htmlspecialchars($data['subheading'] ?? '');
                return <<<HTML
                <section class="py-24 md:py-32 px-6 text-center bg-stone-50">
                    <h1 class="font-serif text-4xl md:text-6xl text-stone-900 tracking-tight">{$heading}</h1>
                    <p class="mt-4 text-stone-500 text-lg max-w-xl mx-auto">{$sub}</p>
                    <div class="mt-8 w-16 h-px bg-stone-300 mx-auto"></div>
                </section>
                HTML;
            },
        ],

        // ═══════════════════════════════════════════
        // CONTENT BLOCKS
        // ═══════════════════════════════════════════

        'content' => [
            'label' => 'Rich Text Block',
            'fields' => ['heading', 'body'],
            'render' => function (array $data): string {
                $heading = htmlspecialchars($data['heading'] ?? '');
                $body    = nl2br(strip_tags($data['body'] ?? '', '<strong><em><a><ul><ol><li><br><p><h1><h2><h3><h4><h5><h6><code><pre>'));
                return <<<HTML
                <section class="max-w-3xl mx-auto py-16 px-6">
                    <h2 class="font-serif text-3xl text-stone-900 mb-6">{$heading}</h2>
                    <div class="prose prose-stone max-w-none text-stone-600 leading-relaxed space-y-4">{$body}</div>
                </section>
                HTML;
            },
        ],

        'content-wide' => [
            'label' => 'Wide Content Block',
            'fields' => ['heading', 'body', 'bg_color'],
            'render' => function (array $data): string {
                $heading = htmlspecialchars($data['heading'] ?? '');
                $body    = nl2br(strip_tags($data['body'] ?? '', '<strong><em><a><ul><ol><li><br><p><h1><h2><h3><h4><h5><h6><code><pre>'));
                $bg      = $data['bg_color'] ?? 'bg-stone-50';
                return <<<HTML
                <section class="{$bg} py-20 px-6">
                    <div class="max-w-4xl mx-auto">
                        <h2 class="font-serif text-3xl text-stone-900 mb-6">{$heading}</h2>
                        <div class="text-stone-600 leading-relaxed space-y-4">{$body}</div>
                    </div>
                </section>
                HTML;
            },
        ],

        // ═══════════════════════════════════════════
        // SPLIT / SIDEBYSIDE
        // ═══════════════════════════════════════════

        'split-image-text' => [
            'label' => 'Split: Image + Text',
            'fields' => ['heading', 'body', 'image', 'image_position'],
            'render' => function (array $data): string {
                $heading    = htmlspecialchars($data['heading'] ?? '');
                $body       = nl2br(strip_tags($data['body'] ?? '', '<strong><em><a><ul><ol><li><br><p>'));
                $img        = htmlspecialchars($data['image'] ?? 'https://picsum.photos/800/600');
                $position   = $data['image_position'] ?? 'left';
                $imgCol     = $position === 'right' ? 'md:order-2' : '';
                $textCol    = $position === 'right' ? 'md:order-1' : '';
                return <<<HTML
                <section class="grid md:grid-cols-2 gap-0">
                    <div class="{$textCol} flex items-center p-12 lg:p-20 bg-stone-50">
                        <div>
                            <h2 class="font-serif text-3xl text-stone-900 mb-6">{$heading}</h2>
                            <div class="text-stone-600 leading-relaxed">{$body}</div>
                        </div>
                    </div>
                    <div class="{$imgCol} h-80 md:h-auto bg-cover bg-center" style="background-image:url('{$img}')"></div>
                </section>
                HTML;
            },
        ],

        'split-text-text' => [
            'label' => 'Split: Two Text Columns',
            'fields' => ['left_heading', 'left_body', 'right_heading', 'right_body'],
            'render' => function (array $data): string {
                $lHead = htmlspecialchars($data['left_heading'] ?? '');
                $lBody = nl2br(strip_tags($data['left_body'] ?? '', '<strong><em><a><ul><ol><li><br><p>'));
                $rHead = htmlspecialchars($data['right_heading'] ?? '');
                $rBody = nl2br(strip_tags($data['right_body'] ?? '', '<strong><em><a><ul><ol><li><br><p>'));
                return <<<HTML
                <section class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-stone-200">
                    <div class="p-12 lg:p-20">
                        <h3 class="font-serif text-2xl text-stone-900 mb-4">{$lHead}</h3>
                        <div class="text-stone-600 leading-relaxed">{$lBody}</div>
                    </div>
                    <div class="p-12 lg:p-20">
                        <h3 class="font-serif text-2xl text-stone-900 mb-4">{$rHead}</h3>
                        <div class="text-stone-600 leading-relaxed">{$rBody}</div>
                    </div>
                </section>
                HTML;
            },
        ],

        // ═══════════════════════════════════════════
        // CARDS / GRID
        // ═══════════════════════════════════════════

        'card-grid-3' => [
            'label' => '3-Column Card Grid',
            'fields' => ['heading', 'card1_title', 'card1_body', 'card1_icon', 'card2_title', 'card2_body', 'card2_icon', 'card3_title', 'card3_body', 'card3_icon'],
            'render' => function (array $data): string {
                $heading = htmlspecialchars($data['heading'] ?? '');
                $cards = '';
                for ($i = 1; $i <= 3; $i++) {
                    $title = htmlspecialchars($data["card{$i}_title"] ?? '');
                    $body  = htmlspecialchars($data["card{$i}_body"] ?? '');
                    $icon  = htmlspecialchars($data["card{$i}_icon"] ?? 'fa-solid fa-star');
                    $cards .= <<<CARD
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-stone-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center mb-5">
                            <i class="{$icon} text-lg"></i>
                        </div>
                        <h3 class="font-serif text-xl text-stone-900 mb-3">{$title}</h3>
                        <p class="text-stone-500 text-sm leading-relaxed">{$body}</p>
                    </div>
                    CARD;
                }
                return <<<HTML
                <section class="py-20 px-6 bg-stone-50">
                    <div class="max-w-6xl mx-auto">
                        <h2 class="font-serif text-3xl text-stone-900 text-center mb-12">{$heading}</h2>
                        <div class="grid md:grid-cols-3 gap-8">{$cards}</div>
                    </div>
                </section>
                HTML;
            },
        ],

        'post-grid' => [
            'label' => 'Blog Post Grid (Latest)',
            'fields' => ['heading', 'count'],
            'render' => function (array $data): string {
                $heading = htmlspecialchars($data['heading'] ?? 'Latest Posts');
                $count   = intval($data['count'] ?? 3);
                $pages   = \PHPueExt\PHPueMatrix::listPages('blogs');
                $live    = array_filter($pages, fn($p) => ($p['status'] ?? '') === 'live');
                $live    = array_slice($live, 0, $count);
                $cards   = '';
                foreach ($live as $p) {
                    $title   = htmlspecialchars($p['title'] ?? 'Untitled');
                    $slug    = htmlspecialchars($p['slug'] ?? '#');
                    $date    = htmlspecialchars($p['created'] ?? '');
                    $cards  .= <<<CARD
                    <a href="/blogs?slug={$slug}" class="group bg-white rounded-2xl overflow-hidden shadow-sm border border-stone-100 hover:shadow-md transition">
                        <div class="p-6">
                            <span class="text-xs text-stone-400">{$date}</span>
                            <h3 class="font-serif text-lg text-stone-900 mt-2 group-hover:text-amber-600 transition">{$title}</h3>
                            <span class="inline-block mt-4 text-sm text-amber-600 font-medium">Read more →</span>
                        </div>
                    </a>
                    CARD;
                }
                if (empty($cards)) $cards = '<p class="text-stone-400 text-center col-span-full">No posts yet.</p>';
                return <<<HTML
                <section class="py-20 px-6">
                    <div class="max-w-6xl mx-auto">
                        <h2 class="font-serif text-3xl text-stone-900 text-center mb-12">{$heading}</h2>
                        <div class="grid md:grid-cols-3 gap-8">{$cards}</div>
                    </div>
                </section>
                HTML;
            },
        ],

        // ═══════════════════════════════════════════
        // MEDIA
        // ═══════════════════════════════════════════

        'full-width-image' => [
            'label' => 'Full-Width Image',
            'fields' => ['image', 'alt_text', 'caption'],
            'render' => function (array $data): string {
                $img     = htmlspecialchars($data['image'] ?? 'https://picsum.photos/1920/600');
                $alt     = htmlspecialchars($data['alt_text'] ?? '');
                $caption = htmlspecialchars($data['caption'] ?? '');
                $capHtml = $caption ? "<p class=\"text-center text-sm text-stone-400 mt-4 italic\">{$caption}</p>" : '';
                return <<<HTML
                <section class="py-8 px-0">
                    <img src="{$img}" alt="{$alt}" class="w-full h-auto max-h-[500px] object-cover">
                    {$capHtml}
                </section>
                HTML;
            },
        ],

        'gallery-3' => [
            'label' => '3-Image Gallery',
            'fields' => ['image1', 'image2', 'image3', 'caption'],
            'render' => function (array $data): string {
                $caption = htmlspecialchars($data['caption'] ?? '');
                $images = '';
                for ($i = 1; $i <= 3; $i++) {
                    $img = htmlspecialchars($data["image{$i}"] ?? 'https://picsum.photos/400/400?random=' . $i);
                    $images .= <<<IMG
                    <div class="aspect-square bg-cover bg-center rounded-2xl" style="background-image:url('{$img}')"></div>
                    IMG;
                }
                $capHtml = $caption ? "<p class=\"text-center text-sm text-stone-400 mt-6 italic\">{$caption}</p>" : '';
                return <<<HTML
                <section class="py-16 px-6">
                    <div class="max-w-5xl mx-auto">
                        <div class="grid grid-cols-3 gap-4">{$images}</div>
                        {$capHtml}
                    </div>
                </section>
                HTML;
            },
        ],

        // ═══════════════════════════════════════════
        // QUOTES / TESTIMONIALS
        // ═══════════════════════════════════════════

        'pull-quote' => [
            'label' => 'Pull Quote',
            'fields' => ['quote', 'author', 'author_title'],
            'render' => function (array $data): string {
                $quote  = htmlspecialchars($data['quote'] ?? '');
                $author = htmlspecialchars($data['author'] ?? '');
                $title  = htmlspecialchars($data['author_title'] ?? '');
                $attr   = $author ? "<footer class=\"mt-6 text-sm text-stone-500\">— {$author}" . ($title ? ", {$title}" : "") . "</footer>" : '';
                return <<<HTML
                <section class="py-20 px-6 bg-stone-900 text-white text-center">
                    <div class="max-w-3xl mx-auto">
                        <i class="fa-solid fa-quote-left text-4xl text-amber-400 mb-6 block"></i>
                        <blockquote class="font-serif text-2xl md:text-3xl leading-relaxed">{$quote}</blockquote>
                        {$attr}
                    </div>
                </section>
                HTML;
            },
        ],

        'testimonial' => [
            'label' => 'Testimonial Card',
            'fields' => ['quote', 'author', 'avatar', 'rating'],
            'render' => function (array $data): string {
                $quote   = htmlspecialchars($data['quote'] ?? '');
                $author  = htmlspecialchars($data['author'] ?? '');
                $avatar  = htmlspecialchars($data['avatar'] ?? 'https://picsum.photos/80/80');
                $rating  = intval($data['rating'] ?? 5);
                $stars   = str_repeat('<i class="fa-solid fa-star text-amber-400 text-sm"></i>', min($rating, 5));
                return <<<HTML
                <section class="py-16 px-6">
                    <div class="max-w-2xl mx-auto bg-white rounded-2xl p-8 shadow-sm border border-stone-100 text-center">
                        <img src="{$avatar}" alt="{$author}" class="w-16 h-16 rounded-full mx-auto mb-4 object-cover">
                        <div class="mb-4">{$stars}</div>
                        <blockquote class="text-stone-600 italic leading-relaxed">"{$quote}"</blockquote>
                        <p class="mt-4 font-semibold text-stone-900">{$author}</p>
                    </div>
                </section>
                HTML;
            },
        ],

        // ═══════════════════════════════════════════
        // CTAs
        // ═══════════════════════════════════════════

        'cta' => [
            'label' => 'Call To Action',
            'fields' => ['heading', 'subheading', 'button_text', 'button_url'],
            'render' => function (array $data): string {
                $heading    = htmlspecialchars($data['heading'] ?? '');
                $sub        = htmlspecialchars($data['subheading'] ?? '');
                $btn        = htmlspecialchars($data['button_text'] ?? 'Learn More');
                $url        = htmlspecialchars($data['button_url'] ?? '#');
                $subHtml    = $sub ? "<p class=\"text-stone-400 text-lg mb-8\">{$sub}</p>" : '';
                return <<<HTML
                <section class="py-20 bg-stone-900 text-center px-6">
                    <h2 class="font-serif text-3xl md:text-4xl text-white mb-4">{$heading}</h2>
                    {$subHtml}
                    <a href="{$url}" class="inline-block bg-amber-500 hover:bg-amber-400 text-stone-950 px-10 py-4 rounded-full font-semibold uppercase tracking-wider text-sm transition shadow-lg shadow-amber-500/20">
                        {$btn}
                    </a>
                </section>
                HTML;
            },
        ],

        'newsletter-cta' => [
            'label' => 'Newsletter Signup CTA',
            'fields' => ['heading', 'subheading', 'button_text', 'placeholder'],
            'render' => function (array $data): string {
                $heading     = htmlspecialchars($data['heading'] ?? 'Stay in the loop');
                $sub         = htmlspecialchars($data['subheading'] ?? 'Get the latest posts delivered to your inbox.');
                $btn         = htmlspecialchars($data['button_text'] ?? 'Subscribe');
                $placeholder = htmlspecialchars($data['placeholder'] ?? 'your@email.com');
                return <<<HTML
                <section class="py-20 px-6 bg-stone-100">
                    <div class="max-w-xl mx-auto text-center">
                        <i class="fa-solid fa-envelope-open-text text-4xl text-amber-500 mb-6 block"></i>
                        <h2 class="font-serif text-3xl text-stone-900 mb-4">{$heading}</h2>
                        <p class="text-stone-500 mb-8">{$sub}</p>
                        <form class="flex gap-3 max-w-md mx-auto" onsubmit="event.preventDefault()">
                            <input type="email" placeholder="{$placeholder}" class="flex-1 px-4 py-3 bg-white border border-stone-300 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                            <button type="submit" class="bg-stone-900 hover:bg-stone-800 text-white px-6 py-3 rounded-xl text-sm font-medium transition">{$btn}</button>
                        </form>
                    </div>
                </section>
                HTML;
            },
        ],

        // ═══════════════════════════════════════════
        // UTILITY
        // ═══════════════════════════════════════════

        'divider' => [
            'label' => 'Section Divider',
            'fields' => [],
            'render' => function (array $data): string {
                return <<<HTML
                <div class="max-w-3xl mx-auto px-6 py-8">
                    <div class="h-px bg-stone-200"></div>
                </div>
                HTML;
            },
        ],

        'faq' => [
            'label' => 'FAQ Section',
            'fields' => ['heading', 'q1', 'a1', 'q2', 'a2', 'q3', 'a3'],
            'render' => function (array $data): string {
                $heading = htmlspecialchars($data['heading'] ?? 'Frequently Asked Questions');
                $items = '';
                for ($i = 1; $i <= 3; $i++) {
                    $q = htmlspecialchars($data["q{$i}"] ?? '');
                    $a = htmlspecialchars($data["a{$i}"] ?? '');
                    if (!$q) continue;
                    $items .= <<<ITEM
                    <div class="border-b border-stone-200 pb-6">
                        <h4 class="font-semibold text-stone-900 mb-2">{$q}</h4>
                        <p class="text-stone-600 text-sm leading-relaxed">{$a}</p>
                    </div>
                    ITEM;
                }
                return <<<HTML
                <section class="py-20 px-6">
                    <div class="max-w-3xl mx-auto">
                        <h2 class="font-serif text-3xl text-stone-900 text-center mb-12">{$heading}</h2>
                        <div class="space-y-6">{$items}</div>
                    </div>
                </section>
                HTML;
            },
        ],

        'author-bio' => [
            'label' => 'Author Bio',
            'fields' => ['name', 'bio', 'avatar', 'social_twitter', 'social_website'],
            'render' => function (array $data): string {
                $name    = htmlspecialchars($data['name'] ?? '');
                $bio     = htmlspecialchars($data['bio'] ?? '');
                $avatar  = htmlspecialchars($data['avatar'] ?? 'https://picsum.photos/120/120');
                $twitter = htmlspecialchars($data['social_twitter'] ?? '');
                $website = htmlspecialchars($data['social_website'] ?? '');
                $social  = '';
                if ($twitter) $social .= "<a href=\"{$twitter}\" class=\"text-stone-400 hover:text-stone-600 transition\"><i class=\"fa-brands fa-twitter\"></i></a>";
                if ($website) $social .= "<a href=\"{$website}\" class=\"text-stone-400 hover:text-stone-600 transition\"><i class=\"fa-solid fa-globe\"></i></a>";
                return <<<HTML
                <section class="py-16 px-6">
                    <div class="max-w-2xl mx-auto bg-stone-50 rounded-2xl p-8 flex flex-col sm:flex-row items-center gap-6 border border-stone-200">
                        <img src="{$avatar}" alt="{$name}" class="w-20 h-20 rounded-full object-cover flex-shrink-0">
                        <div class="text-center sm:text-left">
                            <h4 class="font-serif text-xl text-stone-900">{$name}</h4>
                            <p class="text-stone-500 text-sm mt-2 leading-relaxed">{$bio}</p>
                            <div class="flex gap-4 mt-3 justify-center sm:justify-start">{$social}</div>
                        </div>
                    </div>
                </section>
                HTML;
            },
        ],

        'related-posts' => [
            'label' => 'Related Posts',
            'fields' => ['heading', 'count'],
            'render' => function (array $data): string {
                $heading = htmlspecialchars($data['heading'] ?? 'You Might Also Like');
                $count   = intval($data['count'] ?? 2);
                $pages   = \PHPueExt\PHPueMatrix::listPages('blogs');
                $live    = array_filter($pages, fn($p) => ($p['status'] ?? '') === 'live');
                shuffle($live);
                $live    = array_slice($live, 0, $count);
                $cards   = '';
                foreach ($live as $p) {
                    $title = htmlspecialchars($p['title'] ?? 'Untitled');
                    $slug  = htmlspecialchars($p['slug'] ?? '#');
                    $date  = htmlspecialchars($p['created'] ?? '');
                    $cards .= <<<CARD
                    <a href="/blogs?slug={$slug}" class="group block bg-white rounded-xl p-6 border border-stone-100 hover:border-amber-200 hover:shadow-sm transition">
                        <span class="text-xs text-stone-400">{$date}</span>
                        <h4 class="font-serif text-lg text-stone-900 mt-2 group-hover:text-amber-600 transition">{$title}</h4>
                    </a>
                    CARD;
                }
                if (empty($cards)) return '';
                return <<<HTML
                <section class="py-16 px-6 bg-stone-50">
                    <div class="max-w-4xl mx-auto">
                        <h3 class="font-serif text-2xl text-stone-900 text-center mb-8">{$heading}</h3>
                        <div class="grid sm:grid-cols-2 gap-6">{$cards}</div>
                    </div>
                </section>
                HTML;
            },
        ],
    ],
];