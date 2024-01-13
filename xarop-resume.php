<?php
/*
Plugin Name: Xarop resume REST API
Description: Custom REST API endpoint to retrieve posts from specific categories.
Version: 1.0
Author: xarop.com
*/


// Add custom metabox to post editor
function custom_metabox_setup() {
    add_meta_box(
        'custom_metabox_id',         // Metabox ID
        'Custom Metabox Title',      // Metabox title
        'custom_metabox_content',    // Callback function to display content
        'post',                      // Post type (can be 'post', 'page', or a custom post type)
        'normal',                    // Context (where the metabox appears: 'normal', 'advanced', or 'side')
        'high'                       // Priority (priority within the context: 'high', 'core', 'default', or 'low')
    );
}
add_action('add_meta_boxes', 'custom_metabox_setup');

// Callback function to display metabox content
function custom_metabox_content($post) {
    // Retrieve existing values from post meta
    $date_start = get_post_meta($post->ID, '_custom_date_start_key', true);
    $date_end   = get_post_meta($post->ID, '_custom_date_end_key', true);

    // Output the HTML for the metabox
    ?>
    <label for="custom_date_start">Start Date:</label>
    <input type="date" id="custom_date_start" name="custom_date_start" value="<?php echo esc_attr($date_start); ?>" />

    <br/>

    <label for="custom_date_end">End Date:</label>
    <input type="date" id="custom_date_end" name="custom_date_end" value="<?php echo esc_attr($date_end); ?>" />
    <?php
}

// Save the metabox data when the post is saved
function save_custom_metabox_data($post_id) {
    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Check if the user has permission to edit the post
    if (!current_user_can('edit_post', $post_id)) return;
    

    // Save custom metabox data
    if (isset($_POST['custom_date_start'])) {
        $date_start = sanitize_text_field($_POST['custom_date_start']);
        update_post_meta($post_id, '_custom_date_start_key', $date_start);
    }

    if (isset($_POST['custom_date_end'])) {
        $date_end = sanitize_text_field($_POST['custom_date_end']);
        update_post_meta($post_id, '_custom_date_end_key', $date_end);
    }
}
add_action('save_post', 'save_custom_metabox_data');





// Register custom REST API endpoint
function custom_rest_api_init() {
    register_rest_route('custom/v1', '/posts/', array(
        'methods' => 'GET',
        'callback' => 'custom_get_posts',
    ));
}

function custom_get_posts($data) {
    // Get categories from the request data
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

        // Retrieve custom field values
        $date_start = get_post_meta($post->ID, '_custom_date_start_key', true);
        $date_end = get_post_meta($post->ID, '_custom_date_end_key', true);

        // Add post title and custom fields to the formatted posts array
        $formatted_posts[] = array(
            'title' => $post->post_title,
            'permalink' => $permalink,
            'featured_image' => $featured_image,
            'categories' => $category_data,
            'tags' => $post_tags,
            'content' => $content,
            'excerpt' => $excerpt,
            'date' => $date,
            'date_start' => $date_start,
            'date_end' => $date_end,
        );
    }

    return rest_ensure_response($formatted_posts);
}

// Hook into the rest_api_init action
add_action('rest_api_init', 'custom_rest_api_init');

?>
