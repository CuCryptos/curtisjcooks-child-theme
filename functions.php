<?php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
});

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
