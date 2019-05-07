<?php
if (!class_exists('Yatra_Custom_Post_Type_Tour')) {

    class Yatra_Custom_Post_Type_Tour
    {
        private $slug = 'tour';

        public function __construct()
        {
            add_action('init', array($this, 'register'));

        }

        public function register()
        {
            $labels = array(
                'name' => __('Tours', 'yatra'),
                'singular_name' => __('Tour', 'yatra'),
                'add_new' => __('Add New', 'yatra'),
                'add_new_item' => __('Add New Tour', 'yatra'),
                'edit_item' => __('Edit Tour', 'yatra'),
                'new_item' => __('New Tour', 'yatra'),
                'all_items' => __('All Tours', 'yatra'),
                'view_item' => __('View Tour', 'yatra'),
                'search_items' => __('Search Tour', 'yatra'),
                'not_found' => __('No Tours found', 'yatra'),
                'not_found_in_trash' => __('No Tours found in the Trash', 'yatra'),
                'parent_item_colon' => '',
                'menu_name' => __('Tours', 'yatra'),
            );
            $args = array(
                'labels' => $labels,
                'public' => true,
                'supports' => array('title', 'editor', 'excerpt', 'thumbnail',),
                'has_archive' => true,
//                'rewrite' => array('slug' => "project_item", 'with_front' => TRUE)
            );
            register_post_type($this->slug, $args);

        }


    }
}
return new Yatra_Custom_Post_Type_Tour();