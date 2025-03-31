<?php
/**
 * Plugin Name: Extreme Text Replacer
 * Description: Replace raw text in WordPress posts including Gutenberg blocks and HTML comments
 * Version: 1.0
 * Author: Flajakay
 * Text Domain: extreme-text-replacer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Main plugin class
class Extreme_Text_Replacer {
    
    // Plugin initialization
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_extreme_text_replace', array($this, 'perform_text_replacement'));
    }
    
    // Add admin menu page
    public function add_admin_menu() {
        add_management_page(
            'Extreme Text Replacer',
            'Extreme Text Replacer',
            'manage_options',
            'extreme-text-replacer',
            array($this, 'admin_page_display')
        );
    }
    
    // Enqueue admin scripts and styles
    public function enqueue_admin_scripts($hook) {
        if ('tools_page_extreme-text-replacer' !== $hook) {
            return;
        }
        
        wp_enqueue_style('extreme-text-replacer-admin', plugins_url('css/admin.css', __FILE__));
        wp_enqueue_script('extreme-text-replacer-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), '1.0', true);
        
        // Add localization for JavaScript
        wp_localize_script('extreme-text-replacer-admin', 'extremeTextReplacer', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('extreme-text-replacer-nonce'),
            'dangerous_patterns' => $this->get_dangerous_patterns()
        ));
    }
    
    // Get potentially dangerous replacement patterns
    private function get_dangerous_patterns() {
        return array(
            '<!-- wp:',
            '<!-- /wp:',
            '<p>',
            '</p>',
            '<div',
            '</div>',
            '<!--',
            '-->'
        );
    }
    
    // Admin page display
    public function admin_page_display() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('This plugin allows you to replace raw text in WordPress posts, including Gutenberg block comments. Use with caution and always backup your database before performing large replacements.', 'extreme-text-replacer'); ?></p>
            </div>
            
            <form id="extreme-text-replacer-form" method="post">
                <?php wp_nonce_field('extreme_text_replacer_action', 'extreme_text_replacer_nonce'); ?>
                
                <div class="etr-field-row">
                    <label for="etr-search-text"><?php _e('Search for:', 'extreme-text-replacer'); ?></label>
                    <textarea id="etr-search-text" name="etr_search_text" rows="4" cols="50" required></textarea>
                    <p class="description"><?php _e('Enter the exact text to search for, including WordPress block comments or HTML. For example: <!-- wp:block {"ref":66} /-->', 'extreme-text-replacer'); ?></p>
                </div>
                
                <div class="etr-field-row">
                    <label for="etr-replace-text"><?php _e('Replace with:', 'extreme-text-replacer'); ?></label>
                    <textarea id="etr-replace-text" name="etr_replace_text" rows="4" cols="50"></textarea>
                    <p class="description"><?php _e('Enter the replacement text. Leave empty to remove the searched text.', 'extreme-text-replacer'); ?></p>
                </div>
                
                <div class="etr-field-row">
                    <label for="etr-category"><?php _e('Category:', 'extreme-text-replacer'); ?></label>
                    <?php wp_dropdown_categories(array(
                        'show_option_all' => __('All Categories', 'extreme-text-replacer'),
                        'name' => 'etr_category',
                        'id' => 'etr-category',
                        'hierarchical' => true,
                        'show_count' => true
                    )); ?>
                    <p class="description"><?php _e('Select a specific category or "All Categories" to perform replacement across all posts.', 'extreme-text-replacer'); ?></p>
                </div>
                
                <div id="etr-warning" class="etr-warning" style="display: none;">
                    <p><strong><?php _e('Warning!', 'extreme-text-replacer'); ?></strong> <?php _e('You are attempting to replace WordPress structural elements. This might break your website layout or functionality. Make sure you have a backup before proceeding.', 'extreme-text-replacer'); ?></p>
                </div>
                
                <div class="etr-field-row">
                    <label for="etr-dry-run">
                        <input type="checkbox" id="etr-dry-run" name="etr_dry_run" value="1" checked>
                        <?php _e('Dry Run (preview changes without applying them)', 'extreme-text-replacer'); ?>
                    </label>
                </div>
                
                <div class="etr-field-row">
                    <button type="submit" id="etr-submit" class="button button-primary"><?php _e('Replace Text', 'extreme-text-replacer'); ?></button>
                    <span class="spinner"></span>
                </div>
                
                <div id="etr-results" class="etr-results"></div>
            </form>
        </div>
        <?php
    }
    
    // AJAX handler for text replacement
    public function perform_text_replacement() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'extreme-text-replacer-nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            exit;
        }
        
        // Check if user has proper permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
            exit;
        }
        
        // Get and sanitize inputs - using wp_unslash to preserve special characters
        $search_text = isset($_POST['search_text']) ? wp_unslash($_POST['search_text']) : '';
        $replace_text = isset($_POST['replace_text']) ? wp_unslash($_POST['replace_text']) : '';
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] === 'true';
        
        if (empty($search_text)) {
            wp_send_json_error(array('message' => 'Search text cannot be empty.'));
            exit;
        }
        
        // Set up query args for post retrieval
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        // Add category constraint if specified
        if ($category_id > 0) {
            $args['cat'] = $category_id;
        }
        
        // Get posts
        $query = new WP_Query($args);
        $posts = $query->posts;
        
        $replaced_count = 0;
        $affected_posts = 0;
        $preview_data = array();
        
        // Process posts
        foreach ($posts as $post) {
            $original_content = $post->post_content;
            
            // Use str_replace for exact string matching
            $new_content = str_replace($search_text, $replace_text, $original_content, $count);
            
            // If content changed, update the post or add to preview
            if ($new_content !== $original_content) {
                $replaced_count += $count;
                $affected_posts++;
                
                if ($dry_run) {
                    // Add to preview data for dry run
                    $preview_data[] = array(
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'occurrences' => $count
                    );
                } else {
                    // Update post if not dry run
                    wp_update_post(array(
                        'ID' => $post->ID,
                        'post_content' => $new_content
                    ));
                }
            }
        }
        
        // Prepare success message
        if ($dry_run) {
            $message = sprintf(
                __('Dry run complete. Found %d occurrences in %d posts that would be replaced.', 'extreme-text-replacer'),
                $replaced_count,
                $affected_posts
            );
        } else {
            $message = sprintf(
                __('Replaced %d occurrences in %d posts.', 'extreme-text-replacer'),
                $replaced_count,
                $affected_posts
            );
        }
        
        // Send response
        wp_send_json_success(array(
            'message' => $message,
            'replaced_count' => $replaced_count,
            'affected_posts' => $affected_posts,
            'is_dry_run' => $dry_run,
            'preview_data' => $preview_data
        ));
        
        exit;
    }
}

// Initialize plugin
$extreme_text_replacer = new Extreme_Text_Replacer();
