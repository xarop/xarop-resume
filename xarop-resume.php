<?php
/*
Plugin Name: Xarop resume REST API
Description: Custom REST API endpoint to retrieve posts from specific categories.
Version: 1.0
Author: xarop.com
*/

// Register custom REST API endpoint
function custom_rest_api_init() {
    register_rest_route('custom/v1', '/posts/', array(
        'methods' => 'GET',
        'callback' => 'custom_get_posts',
    ));
}

// Callback function to retrieve posts from specific categories
function custom_get_posts($data) {
    $categories = isset($data['categories']) ? explode(',', $data['categories']) : array();
    $posts = get_posts(array(
        'category__in' => $categories,
        'numberposts' => -1,
    ));

    $formatted_posts = array();

    foreach ($posts as $post) {
        $permalink = get_permalink($post->ID);
        $featured_image = get_the_post_thumbnail_url($post->ID, 'full');
        $post_categories = get_the_category($post->ID);

        // Extract category IDs, names, and slugs
        $category_data = array();
        foreach ($post_categories as $category) {
            $category_data[] = array(
                'id' => $category->cat_ID,
                'name' => $category->name,
                'slug' => $category->slug,
            );
        }

        $post_tags = get_the_tags($post->ID);
        $content = apply_filters('the_content', $post->post_content);
        $excerpt = get_the_excerpt($post->ID);
        $date = $post->post_date;

        // Add post title to the formatted posts array
        $formatted_posts[] = array(
            'title' => $post->post_title,
            'permalink' => $permalink,
            'featured_image' => $featured_image,
            'categories' => $category_data,
            'tags' => $post_tags,
            'content' => $content,
            'excerpt' => $excerpt,
            'date' => $date,
        );
    }

    return rest_ensure_response($formatted_posts);
}

// Hook into the rest_api_init action
add_action('rest_api_init', 'custom_rest_api_init');
?>
