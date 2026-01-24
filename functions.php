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
