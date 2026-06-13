<?php 
    /* Author(s): Edward Patch */
    
    namespace PHPueExt;

    if (defined('PHPUE_VERSION') && version_compare(PHPUE_VERSION, '0.0.2', '>=')) {
        class PHPueMatrix
        {
            private static ?self $instance = null;
            private static string $themesDir;
            private static string $pagesDir;
            public static array $phpueLang;

            private function __construct() {
                self::$themesDir = __DIR__ . '/themes';
                self::$pagesDir  = __DIR__ . '/pages';
            }

            public static function getInstance(): self {
                if(self::$instance === null)
                    self::$instance = new self();

                return self::$instance;
            }

            // ═══════════════════════════════════════════
            // PAGE RENDERING
            // ═══════════════════════════════════════════

            public static function getPage(string $route, string $slug): string {
                $inst = self::getInstance();
                $pagePath = self::$pagesDir . "/{$route}/{$slug}.php";

                if(!file_exists($pagePath)) return '';

                $pageData = include $pagePath;

                $themeName = $pageData['theme'] ?? 'default';
                $themePath = self::$themesDir . "/{$themeName}.php";

                if(!file_exists($themePath)) return '<p>Theme not found.</p>';

                $theme = include $themePath;
                $sectionRenderers = $theme['sections'];

                $html = '';
                foreach($pageData['sections'] as $section) {
                    $type = $section['type'] ?? '';
                    $data = $section['data'] ?? [];

                    if(isset($sectionRenderers[$type]['render']))
                        $html .= $sectionRenderers[$type]['render']($data);
                }

                $shortcodes = $theme['shortcodes'] ?? [];
                $html = self::parseShortcodes($html, $shortcodes);
        
                return $html;
            }

            public static function getPagePreview(string $route, string $slug): string
            {
                return self::getPage($route, $slug);
            }

            // ═══════════════════════════════════════════
            // SEO METADATA
            // ═══════════════════════════════════════════

            public static function getPageSeo(string $route, string $slug): array
            {
                $pagePath = self::$pagesDir . "/{$route}/{$slug}.php";
                if (!file_exists($pagePath)) return [];
                
                $pageData = include $pagePath;
                return $pageData['seo'] ?? [];
            }

            public static function getPageTitle(string $route, string $slug): string
            {
                $pagePath = self::$pagesDir . "/{$route}/{$slug}.php";
                if (!file_exists($pagePath)) return '';
                
                $pageData = include $pagePath;
                return $pageData['seo']['meta_title'] ?? $pageData['title'] ?? '';
            }

            public static function getPageDescription(string $route, string $slug): string
            {
                $seo = self::getPageSeo($route, $slug);
                
                if (!empty($seo['meta_description'])) {
                    return $seo['meta_description'];
                }
                
                $pagePath = self::$pagesDir . "/{$route}/{$slug}.php";
                if (!file_exists($pagePath)) return '';
                
                $pageData = include $pagePath;
                $sections = $pageData['sections'] ?? [];
                
                foreach ($sections as $section) {
                    $type = $section['type'] ?? '';
                    $data = $section['data'] ?? [];
                    
                    if (in_array($type, ['content', 'content-wide', 'section-a'])) {
                        $body = $data['body'] ?? $data['text'] ?? '';
                        if ($body) {
                            $body = strip_tags($body);
                            return mb_strlen($body) > 160 
                                ? mb_substr($body, 0, 157) . '...' 
                                : $body;
                        }
                    }
                    
                    if ($type === 'hero' && !empty($data['subheading'] ?? null)) {
                        $sub = strip_tags($data['subheading']);
                        return mb_strlen($sub) > 160 
                            ? mb_substr($sub, 0, 157) . '...' 
                            : $sub;
                    }
                }
                
                return '';
            }

            public static function getPageOgImage(string $route, string $slug): string
            {
                $seo = self::getPageSeo($route, $slug);
                
                if (!empty($seo['og_image'])) {
                    return $seo['og_image'];
                }
                
                $pagePath = self::$pagesDir . "/{$route}/{$slug}.php";
                if (!file_exists($pagePath)) return '';
                
                $pageData = include $pagePath;
                $sections = $pageData['sections'] ?? [];
                
                foreach ($sections as $section) {
                    $type = $section['type'] ?? '';
                    $data = $section['data'] ?? [];
                    
                    if ($type === 'hero') {
                        return $data['bg_image'] ?? $data['image'] ?? '';
                    }
                    
                    if ($type === 'full-width-image') {
                        return $data['image'] ?? '';
                    }
                }
                
                return '';
            }

            // ═══════════════════════════════════════════
            // NEW: Twitter Card Image
            // ═══════════════════════════════════════════

            public static function getPageTwitterImage(string $route, string $slug): string
            {
                $seo = self::getPageSeo($route, $slug);
                
                // Use dedicated Twitter image if set, fall back to OG image
                if (!empty($seo['twitter_image'])) {
                    return $seo['twitter_image'];
                }
                
                // Fallback to OG image (which itself has fallbacks)
                return self::getPageOgImage($route, $slug);
            }

            public static function getPageKeywords(string $route, string $slug): string
            {
                $seo = self::getPageSeo($route, $slug);
                return $seo['meta_keywords'] ?? '';
            }

            public static function getPageCanonical(string $route, string $slug): string
            {
                $seo = self::getPageSeo($route, $slug);
                return $seo['canonical_url'] ?? '';
            }

            public static function getPageRobots(string $route, string $slug): string
            {
                $seo = self::getPageSeo($route, $slug);
                
                if (!empty($seo['noindex'])) {
                    return 'noindex, nofollow';
                }
                
                return 'index, follow';
            }

            // ═══════════════════════════════════════════
            // PAGE CRUD
            // ═══════════════════════════════════════════

            public static function createPage(string $route, string $slug, string $theme, array $sections, string $title = '', string $status = 'live', array $seo = []): bool
            {
                $inst = self::getInstance();
                $dir = self::$pagesDir.'/'.$route;

                if(!is_dir($dir))
                    mkdir($dir, 0755, true);

                $filePath = "{$dir}/{$slug}.php";

                $date = date('Y-m-d');
                $exportSections = var_export($sections, true);
                $exportSeo = var_export($seo, true);
                $titleSafe = addslashes($title);
                $slugSafe = addslashes($slug);
                $themeSafe = addslashes($theme);
                $statusSafe = addslashes($status);

                $content = <<<PHP
                <?php
                //Auto-generated by PHPue Matrix CMS on {$date}
                return [
                    'title' => '{$titleSafe}',
                    'slug' => '{$slugSafe}',
                    'theme' => '{$themeSafe}',
                    'status' => '{$statusSafe}',
                    'created' => '{$date}',
                    'sections' => {$exportSections},
                    'seo' => {$exportSeo},
                ];
                PHP;

                $written = file_put_contents($filePath, $content) !== false;
    
                if ($written && function_exists('opcache_invalidate')) {
                    opcache_invalidate($filePath, true);
                }
                
                return $written;
            }

            public static function deletePage(string $route, string $slug): bool
            {
                $inst = self::getInstance();
                $filePath = self::$pagesDir . "/{$route}/{$slug}.php";
                
                if (!file_exists($filePath)) {
                    return false;
                }
                
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($filePath, true);
                }
                
                return unlink($filePath);
            }

            // ═══════════════════════════════════════════
            // THEME MANAGEMENT
            // ═══════════════════════════════════════════

            public static function getTheme(string $name = 'default'): array
            {
                $inst = self::getInstance();
                $themePath = self::$themesDir . "/{$name}.php";

                if(!file_exists($themePath))
                    return [];

                $theme = include $themePath;

                $result = [
                    'meta' => $theme['meta'] ?? [],
                    'sections' => []
                ];

                foreach($theme['sections'] as $type => $def)
                {
                    $result['sections'][$type] = [
                        'label' => $def['label'],
                        'fields' => $def['fields']
                    ];
                }

                return $result;
            }

            // ═══════════════════════════════════════════
            // LISTING
            // ═══════════════════════════════════════════

            public static function listPages(string $category = 'blogs'): array
            {
                $inst = self::getInstance();
                $dir = self::$pagesDir . '/' . $category;

                if(!is_dir($dir))
                    return [];

                $pages = [];
                foreach(glob("{$dir}/*.php") as $file) {
                    $data = include $file;
                    $pages[] = [
                        'slug' => $data['slug'] ?? basename($file, '.php'),
                        'title' => $data['title'] ?? 'Untitled',
                        'status' => $data['status'] ?? 'draft',
                        'created' => $data['created'] ?? 'unknown',
                    ];
                }

                return $pages;
            }

            public static function listRoutes(): array
            {
                $inst = self::getInstance();
                $dir = self::$pagesDir;
                
                if (!is_dir($dir)) {
                    return [];
                }
                
                $routes = [];
                foreach (glob($dir . '/*', GLOB_ONLYDIR) as $routeDir) {
                    $routeName = basename($routeDir);
                    $pageCount = count(glob($routeDir . '/*.php'));
                    $routes[$routeName] = [
                        'name' => $routeName,
                        'count' => $pageCount,
                    ];
                }
                
                ksort($routes);
                return $routes;
            }

            // ═══════════════════════════════════════════
            // SHORTCODE PARSER
            // ═══════════════════════════════════════════

            private static function parseShortcodes(string $html, array $shortcodes): string
            {
                return preg_replace_callback('/\[\[(\S+?)\]\]/', function($matches) use ($shortcodes) {
                    $token = $matches[1];
                    
                    if (str_contains($token, '.')) {
                        [$namespace, $key] = explode('.', $token, 2);
                        if (isset($shortcodes[$namespace]) && is_callable($shortcodes[$namespace])) {
                            $result = $shortcodes[$namespace]($key);
                            return is_string($result) || is_numeric($result) ? (string) $result : $matches[0];
                        }
                    }
                    
                    if (isset($shortcodes[$token]) && is_callable($shortcodes[$token])) {
                        $result = $shortcodes[$token]();
                        return is_string($result) || is_numeric($result) ? (string) $result : $matches[0];
                    }
                    
                    return $matches[0];
                }, $html);
            }
        }
    }

?>