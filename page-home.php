<?php
/**
 * Template Name: CurtisJCooks Homepage
 *
 * Enhanced homepage template with animated sections.
 * To use: Edit your Home page → Page Attributes → Template → CurtisJCooks Homepage
 */

get_header();
?>

<div id="main-content" class="cjc-homepage">

    <?php
    // Floating particles background
    echo do_shortcode('[cjc_floating_particles]');

    // Enhanced Hero Section
    echo do_shortcode('[cjc_hero_enhanced]');

    // Stats Section with animated counters
    echo do_shortcode('[cjc_stats]');

    // Category Pills Section
    echo do_shortcode('[cjc_category_pills]');

    // Featured Recipes with enhanced cards
    echo do_shortcode('[cjc_recipes_enhanced]');

    // Newsletter Section
    echo do_shortcode('[cjc_newsletter_enhanced]');

    // Enhanced Footer
    echo do_shortcode('[cjc_footer_enhanced]');
    ?>

</div>

<?php get_footer(); ?>
