<?php
/**
 * Template Name: CurtisJCooks Homepage
 *
 * Custom homepage template with all site sections.
 * To use: Edit your Home page → Page Attributes → Template → CurtisJCooks Homepage
 */

get_header();
?>

<div id="main-content" class="cjc-homepage">

    <?php echo do_shortcode('[curtisjcooks_hero]'); ?>

    <?php echo do_shortcode('[curtisjcooks_about]'); ?>

    <?php echo do_shortcode('[curtisjcooks_features]'); ?>

    <?php echo do_shortcode('[curtisjcooks_gallery]'); ?>

    <?php
    // Recent Recipes Section
    $recent_recipes = new WP_Query([
        'posts_per_page' => 6,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    if ($recent_recipes->have_posts()): ?>
    <section class="cjc-recent-recipes">
        <h2>Latest from the Kitchen</h2>
        <div class="cjc-recipes-grid">
            <?php while ($recent_recipes->have_posts()): $recent_recipes->the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="cjc-recipe-card">
                <?php if (has_post_thumbnail()): ?>
                    <div class="cjc-recipe-image">
                        <?php the_post_thumbnail('medium_large'); ?>
                    </div>
                <?php endif; ?>
                <div class="cjc-recipe-info">
                    <h3><?php the_title(); ?></h3>
                    <?php
                    $categories = get_the_category();
                    if (!empty($categories)): ?>
                        <span class="cjc-recipe-category"><?php echo esc_html($categories[0]->name); ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <div class="cjc-recipes-cta">
            <a href="<?php echo get_permalink(get_option('page_for_posts')); ?>" class="cjc-view-all-button">View All Recipes</a>
        </div>
    </section>
    <?php endif; ?>

    <?php echo do_shortcode('[ohana_signup_hero]'); ?>

</div>

<?php get_footer(); ?>
