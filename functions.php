<?php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
});

/* =============================================
   Performance Optimizations
   ============================================= */

/**
 * Remove WordPress emoji scripts and styles.
 * Saves ~10KB and DNS lookup.
 */
add_action('init', function() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

    // Remove TinyMCE emoji plugin
    add_filter('tiny_mce_plugins', function($plugins) {
        return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
    });

    // Remove emoji DNS prefetch
    add_filter('wp_resource_hints', function($urls, $relation_type) {
        if ($relation_type === 'dns-prefetch') {
            $urls = array_filter($urls, function($url) {
                return strpos($url, 'https://s.w.org/images/core/emoji/') === false;
            });
        }
        return $urls;
    }, 10, 2);
});

/**
 * Remove other unnecessary WordPress default scripts.
 */
add_action('wp_enqueue_scripts', function() {
    // Remove WordPress embed script (if not using embeds)
    wp_deregister_script('wp-embed');

    // Remove dashicons on frontend for non-logged-in users
    if (!is_user_logged_in()) {
        wp_deregister_style('dashicons');
    }
}, 100);

/**
 * Remove jQuery migrate (not needed for modern jQuery).
 */
add_action('wp_default_scripts', function($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        if ($script->deps) {
            $script->deps = array_diff($script->deps, ['jquery-migrate']);
        }
    }
});

/**
 * Add defer attribute to non-critical JavaScript.
 * Excludes jQuery and critical scripts from deferring.
 */
add_filter('script_loader_tag', function($tag, $handle, $src) {
    // Don't defer in admin or for logged-in users editing
    if (is_admin()) {
        return $tag;
    }

    // Scripts that should NOT be deferred (critical for page render)
    $no_defer = [
        'jquery-core',
        'jquery',
        'et-builder-modules-global-functions-script',
        'divi-custom-script',
    ];

    if (in_array($handle, $no_defer)) {
        return $tag;
    }

    // Don't double-add defer
    if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
        return $tag;
    }

    return str_replace(' src=', ' defer src=', $tag);
}, 10, 3);

/**
 * Preconnect to Google Fonts and optimize font loading.
 */
add_action('wp_head', function() {
    ?>
    <!-- Preconnect to Google Fonts for faster font loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
}, 1);

/**
 * Add font-display: swap to Google Fonts to prevent FOIT.
 */
add_filter('style_loader_tag', function($tag, $handle, $src) {
    // Add font-display parameter to Google Fonts URLs
    if (strpos($src, 'fonts.googleapis.com') !== false) {
        if (strpos($src, 'display=') === false) {
            $src_new = add_query_arg('display', 'swap', $src);
            $tag = str_replace($src, $src_new, $tag);
        }
    }
    return $tag;
}, 10, 3);

/**
 * Remove WordPress version from scripts/styles (minor security).
 */
add_filter('style_loader_src', function($src) {
    return $src ? remove_query_arg('ver', $src) : $src;
}, 10, 1);

add_filter('script_loader_src', function($src) {
    return $src ? remove_query_arg('ver', $src) : $src;
}, 10, 1);

/* =============================================
   SEO: Noindex Non-Hawaiian Category Posts
   ============================================= */

/**
 * Add noindex meta tag to posts NOT in main Hawaiian categories.
 * This helps focus search engines on your core content.
 */
add_action('wp_head', function() {
    // Only apply to single posts
    if (!is_singular('post')) {
        return;
    }

    // Categories that SHOULD be indexed (no noindex)
    $indexed_categories = [
        'island-comfort',
        'island-drinks',
        'poke-seafood',
        'tropical-treats',
        'top-articles',
    ];

    // Check if current post is in any of the indexed categories
    $in_indexed_category = false;
    foreach ($indexed_categories as $category_slug) {
        if (in_category($category_slug)) {
            $in_indexed_category = true;
            break;
        }
    }

    // If NOT in an indexed category, add noindex
    if (!$in_indexed_category) {
        echo '<meta name="robots" content="noindex, follow">' . "\n";
    }
}, 1);

/**
 * Hide duplicate posts on the homepage when multiple Divi blog modules
 * display overlapping content.
 */
add_action('wp_footer', function() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            const seenPosts = new Set();

            // Find all Divi blog post articles (they have class like "post-123")
            const posts = document.querySelectorAll('.et_pb_post, .et_pb_blog_grid .post, article[class*="post-"]');

            posts.forEach(function(post) {
                // Extract post ID from class list (e.g., "post-123")
                const postId = Array.from(post.classList)
                    .find(cls => /^post-\d+$/.test(cls));

                if (postId) {
                    if (seenPosts.has(postId)) {
                        // Hide duplicate post
                        post.style.display = 'none';
                    } else {
                        seenPosts.add(postId);
                    }
                }
            });
        });
    })();
    </script>
    <?php
}, 99);

/**
 * Hide WP Tasty recipe total time field when set to 0.
 */
add_action('wp_footer', function() {
    if (!is_singular('post')) {
        return;
    }
    ?>
    <script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            // WP Tasty time selectors
            const timeSelectors = [
                '.tasty-recipes-total-time',
                '.tasty-recipes-details .total-time',
                '[class*="total-time"]'
            ];

            timeSelectors.forEach(function(selector) {
                document.querySelectorAll(selector).forEach(function(el) {
                    const text = el.textContent.trim();
                    // Hide if time is 0, "0 minutes", "0 mins", etc.
                    if (/^(total\s*time[:\s]*)?0(\s*(minutes?|mins?|hours?|hrs?))?$/i.test(text) ||
                        text === '0' ||
                        /:\s*0\s*(minutes?|mins?)?$/i.test(text)) {
                        el.style.display = 'none';
                    }
                });
            });
        });
    })();
    </script>
    <?php
}, 99);

/**
 * Recipe Search Shortcode
 * Usage: [recipe_search] or [recipe_search placeholder="Find a recipe..."]
 * Searches only posts in the 'recipes' category.
 */
add_shortcode('recipe_search', function($atts) {
    $atts = shortcode_atts([
        'placeholder' => 'Search recipes...',
        'button_text' => 'Search',
    ], $atts);

    $placeholder = esc_attr($atts['placeholder']);
    $button_text = esc_html($atts['button_text']);
    $home_url = esc_url(home_url('/'));

    return <<<HTML
    <div class="recipe-search-widget">
        <form role="search" method="get" action="{$home_url}" class="recipe-search-form">
            <input type="hidden" name="category_name" value="recipes">
            <div class="recipe-search-input-wrap">
                <span class="recipe-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </span>
                <input type="search" name="s" placeholder="{$placeholder}" class="recipe-search-input" required>
                <button type="submit" class="recipe-search-button">{$button_text}</button>
            </div>
        </form>
    </div>
HTML;
});

/**
 * Sticky "Jump to Recipe" button for recipe posts.
 * Appears on scroll when a WP Tasty recipe card is present.
 */
add_action('wp_footer', function() {
    if (!is_singular('post')) {
        return;
    }
    ?>
    <button id="jump-to-recipe" class="jump-to-recipe-btn" aria-label="Jump to Recipe" style="display: none;">
        <span class="jump-to-recipe-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 8l4 4-4 4"/>
                <path d="M3 12h18"/>
            </svg>
        </span>
        <span class="jump-to-recipe-text">Jump to Recipe</span>
    </button>
    <script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('jump-to-recipe');
            if (!btn) return;

            // Find WP Tasty recipe card
            const recipeCard = document.querySelector('.tasty-recipes, .tasty-recipes-entry, [class*="tasty-recipes"]');
            if (!recipeCard) {
                btn.remove();
                return;
            }

            let scrollTimeout;
            const scrollThreshold = 200;

            // Show/hide button based on scroll position
            function handleScroll() {
                const scrollY = window.scrollY || window.pageYOffset;
                const recipeTop = recipeCard.getBoundingClientRect().top + scrollY;
                const viewportBottom = scrollY + window.innerHeight;

                // Show button after scrolling down, hide when recipe is in view
                if (scrollY > scrollThreshold && viewportBottom < recipeTop + 100) {
                    btn.classList.add('visible');
                    btn.style.display = 'flex';
                } else {
                    btn.classList.remove('visible');
                }
            }

            // Smooth scroll to recipe
            btn.addEventListener('click', function() {
                recipeCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            // Throttled scroll listener
            window.addEventListener('scroll', function() {
                if (scrollTimeout) return;
                scrollTimeout = setTimeout(function() {
                    handleScroll();
                    scrollTimeout = null;
                }, 100);
            }, { passive: true });

            // Initial check
            handleScroll();
        });
    })();
    </script>
    <?php
}, 99);

/* =============================================
   Pillar Page Template Shortcodes
   ============================================= */

/**
 * Pillar Hero Section
 * Usage: [pillar_hero title="Your Pillar Title" subtitle="Optional subtitle"]
 */
add_shortcode('pillar_hero', function($atts) {
    $atts = shortcode_atts([
        'title' => get_the_title(),
        'subtitle' => '',
        'background' => '',
    ], $atts);

    $title = esc_html($atts['title']);
    $subtitle = esc_html($atts['subtitle']);
    $bg_style = $atts['background'] ? 'background-image: url(' . esc_url($atts['background']) . ');' : '';

    $subtitle_html = $subtitle ? '<p class="pillar-hero-subtitle">' . $subtitle . '</p>' : '';

    return <<<HTML
    <div class="pillar-hero" style="{$bg_style}">
        <div class="pillar-hero-overlay"></div>
        <div class="pillar-hero-content">
            <h1 class="pillar-hero-title">{$title}</h1>
            {$subtitle_html}
        </div>
    </div>
HTML;
});

/**
 * Auto-generating Table of Contents
 * Usage: [pillar_toc title="In This Guide"]
 * Automatically finds H2 headings on the page via JavaScript.
 */
add_shortcode('pillar_toc', function($atts) {
    $atts = shortcode_atts([
        'title' => 'In This Guide',
    ], $atts);

    $title = esc_html($atts['title']);

    return <<<HTML
    <div class="pillar-toc" id="pillar-toc">
        <h3 class="pillar-toc-title">{$title}</h3>
        <nav class="pillar-toc-nav">
            <ul class="pillar-toc-list" id="pillar-toc-list">
                <!-- Generated by JavaScript -->
            </ul>
        </nav>
    </div>
    <script>
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            const tocList = document.getElementById('pillar-toc-list');
            const toc = document.getElementById('pillar-toc');
            if (!tocList) return;

            const headings = document.querySelectorAll('.entry-content h2, .et_pb_text h2, article h2');

            if (headings.length === 0) {
                toc.style.display = 'none';
                return;
            }

            headings.forEach(function(heading, index) {
                // Add ID to heading if it doesn't have one
                if (!heading.id) {
                    heading.id = 'section-' + (index + 1);
                }

                const li = document.createElement('li');
                li.className = 'pillar-toc-item';

                const link = document.createElement('a');
                link.href = '#' + heading.id;
                link.className = 'pillar-toc-link';
                link.textContent = heading.textContent;

                // Smooth scroll on click
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    history.pushState(null, null, '#' + heading.id);
                });

                li.appendChild(link);
                tocList.appendChild(li);
            });
        });
    })();
    </script>
HTML;
});

/**
 * Recipe Card Grid
 * Usage: [recipe_grid category="island-comfort" count="6" columns="3"]
 */
add_shortcode('recipe_grid', function($atts) {
    $atts = shortcode_atts([
        'category' => '',
        'count' => 6,
        'columns' => 3,
        'orderby' => 'date',
        'order' => 'DESC',
    ], $atts);

    if (empty($atts['category'])) {
        return '<p class="recipe-grid-error">Please specify a category: [recipe_grid category="your-category"]</p>';
    }

    $args = [
        'post_type' => 'post',
        'posts_per_page' => intval($atts['count']),
        'category_name' => sanitize_text_field($atts['category']),
        'orderby' => sanitize_text_field($atts['orderby']),
        'order' => sanitize_text_field($atts['order']),
    ];

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p class="recipe-grid-empty">No recipes found in this category.</p>';
    }

    $columns = intval($atts['columns']);
    $output = '<div class="recipe-grid recipe-grid-cols-' . $columns . '">';

    while ($query->have_posts()) {
        $query->the_post();

        $thumbnail = get_the_post_thumbnail(get_the_ID(), 'medium', ['class' => 'recipe-card-image']);
        if (!$thumbnail) {
            $thumbnail = '<div class="recipe-card-no-image">No Image</div>';
        }

        $title = esc_html(get_the_title());
        $permalink = esc_url(get_permalink());
        $excerpt = wp_trim_words(get_the_excerpt(), 15, '...');

        $output .= <<<HTML
        <article class="recipe-card">
            <a href="{$permalink}" class="recipe-card-link">
                <div class="recipe-card-thumbnail">
                    {$thumbnail}
                </div>
                <div class="recipe-card-content">
                    <h3 class="recipe-card-title">{$title}</h3>
                    <p class="recipe-card-excerpt">{$excerpt}</p>
                </div>
            </a>
        </article>
HTML;
    }

    wp_reset_postdata();

    $output .= '</div>';

    return $output;
});

/**
 * Internal Links Section
 * Usage: [internal_links title="Related Guides"]
 * Add links manually in content, or use links attribute
 * [internal_links links="Page One|/page-one,Page Two|/page-two"]
 */
add_shortcode('internal_links', function($atts, $content = null) {
    $atts = shortcode_atts([
        'title' => 'Continue Exploring',
        'links' => '',
    ], $atts);

    $title = esc_html($atts['title']);

    $links_html = '';

    // If links provided via attribute
    if (!empty($atts['links'])) {
        $links_array = explode(',', $atts['links']);
        foreach ($links_array as $link) {
            $parts = explode('|', trim($link));
            if (count($parts) === 2) {
                $link_text = esc_html(trim($parts[0]));
                $link_url = esc_url(trim($parts[1]));
                $links_html .= '<a href="' . $link_url . '" class="internal-link-item">';
                $links_html .= '<span class="internal-link-text">' . $link_text . '</span>';
                $links_html .= '<span class="internal-link-arrow">→</span>';
                $links_html .= '</a>';
            }
        }
    }

    // If content provided between tags
    if ($content) {
        $links_html .= '<div class="internal-links-content">' . do_shortcode($content) . '</div>';
    }

    if (empty($links_html)) {
        return '';
    }

    return <<<HTML
    <div class="internal-links-section">
        <h3 class="internal-links-title">{$title}</h3>
        <div class="internal-links-grid">
            {$links_html}
        </div>
    </div>
HTML;
});

/* =============================================
   Related Recipes Section
   ============================================= */

/**
 * Display 3 related recipes at the bottom of each post.
 * Pulls only from Hawaiian recipe categories.
 */
add_filter('the_content', function($content) {
    // Only on single posts, not in admin, not in feeds
    if (!is_singular('post') || is_admin() || is_feed()) {
        return $content;
    }

    // Don't add if we're in a REST API request
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return $content;
    }

    // Hawaiian recipe categories
    $hawaiian_categories = [
        'island-comfort',
        'island-drinks',
        'poke-seafood',
        'tropical-treats',
        'top-articles',
    ];

    $current_post_id = get_the_ID();
    $current_categories = wp_get_post_categories($current_post_id, ['fields' => 'slugs']);

    // Find matching Hawaiian categories for current post
    $matching_cats = array_intersect($current_categories, $hawaiian_categories);

    // Build query args
    $args = [
        'post_type' => 'post',
        'posts_per_page' => 3,
        'post__not_in' => [$current_post_id],
        'orderby' => 'rand',
    ];

    // If current post is in Hawaiian categories, prioritize those
    if (!empty($matching_cats)) {
        $args['category_name'] = implode(',', $matching_cats);
    } else {
        // Otherwise, pull from all Hawaiian categories
        $args['category_name'] = implode(',', $hawaiian_categories);
    }

    $related_query = new WP_Query($args);

    // If not enough posts found, try all Hawaiian categories
    if ($related_query->post_count < 3 && !empty($matching_cats)) {
        $args['category_name'] = implode(',', $hawaiian_categories);
        $related_query = new WP_Query($args);
    }

    if (!$related_query->have_posts()) {
        return $content;
    }

    $related_html = '<div class="related-recipes-section">';
    $related_html .= '<h3 class="related-recipes-title">More Hawaiian Recipes</h3>';
    $related_html .= '<div class="related-recipes-grid">';

    while ($related_query->have_posts()) {
        $related_query->the_post();

        $thumbnail = get_the_post_thumbnail(get_the_ID(), 'medium', ['class' => 'related-recipe-image']);
        if (!$thumbnail) {
            $thumbnail = '<div class="related-recipe-no-image"></div>';
        }

        $title = esc_html(get_the_title());
        $permalink = esc_url(get_permalink());
        $excerpt = wp_trim_words(get_the_excerpt(), 12, '...');

        $related_html .= <<<HTML
        <article class="related-recipe-card">
            <a href="{$permalink}" class="related-recipe-link">
                <div class="related-recipe-thumbnail">
                    {$thumbnail}
                </div>
                <div class="related-recipe-content">
                    <h4 class="related-recipe-title">{$title}</h4>
                    <p class="related-recipe-excerpt">{$excerpt}</p>
                    <span class="related-recipe-cta">View Recipe →</span>
                </div>
            </a>
        </article>
HTML;
    }

    wp_reset_postdata();

    $related_html .= '</div></div>';

    return $content . $related_html;
}, 20);
