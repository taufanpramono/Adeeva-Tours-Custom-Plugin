<?php 

//==================================
// Version : 1.1
// Build : Php Enggine & WP Hook
// Author : Taufan Pramono
// usage : Tag Cloud
//==================================



function tagTours() {
    $args = array(
        'post_type' => 'tours', 
        'posts_per_page' => -1, 
    );

    $query = new WP_Query($args);
    $output = '';

    if ($query->have_posts()) {
        $output .= '<div class="tag-cloud-uhui">';
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $terms = wp_get_object_terms($post_id, 'tour-tag'); // Mengambil semua tag dari post
            
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $term_link = get_term_link($term); // Mendapatkan link dari term
                    if (!is_wp_error($term_link)) {
                        
                        $output .= '<a href="' . esc_url($term_link) . '"><input type="checkbox"> ' . esc_html($term->name) . ' </a>';
                        
                    }
                }
            }
        }
        $output .= '</div>';
    }

    wp_reset_postdata();
    return $output;
}
add_shortcode('tour_tag', 'tagTours');


function display_tour_tags_as_links() {
    $post_id = get_the_ID();
    $terms = wp_get_object_terms($post_id, 'tour-tag');
    $output = '';

    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $term_link = get_term_link($term);
            if (!is_wp_error($term_link)) {
                $output .= '<a href="' . esc_url($term_link) . '">#' . esc_html($term->name) . '</a>';
            }
        }
    }

    return $output;
}

add_shortcode('tour_tags_links', 'display_tour_tags_as_links');




 ?>