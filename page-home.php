<?php
/**
 * Template Name: CurtisJCooks Homepage
 *
 * React-powered homepage template.
 */

get_header();

// Prepare data for React
$recipes = [];
$recent_posts = new WP_Query([
    'posts_per_page' => 6,
    'post_status' => 'publish',
]);

while ($recent_posts->have_posts()) {
    $recent_posts->the_post();
    $categories = get_the_category();
    $recipes[] = [
        'title' => get_the_title(),
        'link' => get_permalink(),
        'image' => get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600',
        'category' => !empty($categories) ? $categories[0]->name : 'Recipe',
        'time' => '20 min',
    ];
}
wp_reset_postdata();

// Get site images
$hero_image = function_exists('curtisjcooks_get_site_image') ? curtisjcooks_get_site_image('homepage-hero') : '';

$react_data = [
    'recipes' => $recipes,
    'images' => [
        'hero' => $hero_image ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800',
    ],
    'stats' => [
        'recipes' => 50,
        'readers' => 12000,
        'rating' => 4.9,
    ],
    'siteUrl' => home_url(),
];
?>

<div id="main-content">
    <div id="cjc-react-root"></div>
</div>

<script>
    window.cjcData = <?php echo json_encode($react_data); ?>;
</script>

<?php get_footer(); ?>
