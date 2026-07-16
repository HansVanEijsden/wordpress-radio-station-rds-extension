<?php

/**
 * Plugin Name: Radio Station – RDS Extension
 * Plugin URI:  https://github.com/HansVanEijsden/radio-station-rds-extension
 * Description: Extends the Radio Station plugin with FM-RDS-PTY and FM-RDS-PTYN taxonomies and adds them to the metadata middleware output.
 * Version:     1.0.1
 * Author:      Hans van Eijsden
 * Author URI:  https://www.hansvaneijsden.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: radio-station-rds-extension
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Tested up to: 6.9
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// --- Define constants ---
define('RSRDS_VERSION', '1.0.1');
define('RSRDS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RSRDS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RSRDS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// --- Check for dependencies ---
function rsrds_check_dependencies()
{
    $missing = [];

    // Check if Radio Station is active
    if (!class_exists('Radio_Station')) {
        $missing[] = 'Radio Station';
    }

    // Check if show post type exists
    if (!post_type_exists('show')) {
        $missing[] = 'Show post type (Radio Station)';
    }

    if (!empty($missing)) {
        add_action('admin_notices', function () use ($missing) {
?>
            <div class="notice notice-error">
                <p>
                    <strong>Radio Station – RDS Extension:</strong>
                    This plugin requires the following plugins to be installed and active:
                    <strong><?php echo implode(', ', $missing); ?></strong>.
                    Please install and activate them.
                </p>
            </div>
<?php
        });
        return false;
    }

    return true;
}
add_action('plugins_loaded', 'rsrds_check_dependencies');

// --- Load translations ---
function rsrds_load_textdomain()
{
    load_plugin_textdomain(
        'radio-station-rds-extension',
        false,
        dirname(RSRDS_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('init', 'rsrds_load_textdomain');

// --- Register taxonomies ---
function rsrds_register_pty_taxonomy()
{
    $labels = [
        'name'              => _x('FM-RDS-PTY', 'taxonomy general name', 'radio-station-rds-extension'),
        'singular_name'     => _x('FM-RDS-PTY', 'taxonomy singular name', 'radio-station-rds-extension'),
        'menu_name'         => _x('FM-RDS-PTY', 'admin menu', 'radio-station-rds-extension'),
        'all_items'         => __('All PTY', 'radio-station-rds-extension'),
        'edit_item'         => __('Edit PTY', 'radio-station-rds-extension'),
        'update_item'       => __('Update PTY', 'radio-station-rds-extension'),
        'add_new_item'      => __('Add New PTY', 'radio-station-rds-extension'),
        'new_item_name'     => __('New PTY Name', 'radio-station-rds-extension'),
        'search_items'      => __('Search PTY', 'radio-station-rds-extension'),
        'popular_items'     => __('Popular PTY', 'radio-station-rds-extension'),
    ];

    register_taxonomy('fm_rds_pty', ['show', 'override'], [
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'rest_base'         => 'fm_rds_pty',
        'show_admin_column' => true,
        'meta_box_cb'       => 'rsrds_pty_meta_box',
    ]);
}
add_action('init', 'rsrds_register_pty_taxonomy');

function rsrds_register_ptyn_taxonomy()
{
    $labels = [
        'name'              => _x('FM-RDS-PTYN', 'taxonomy general name', 'radio-station-rds-extension'),
        'singular_name'     => _x('FM-RDS-PTYN', 'taxonomy singular name', 'radio-station-rds-extension'),
        'menu_name'         => _x('FM-RDS-PTYN', 'admin menu', 'radio-station-rds-extension'),
        'all_items'         => __('All PTYN', 'radio-station-rds-extension'),
        'edit_item'         => __('Edit PTYN', 'radio-station-rds-extension'),
        'update_item'       => __('Update PTYN', 'radio-station-rds-extension'),
        'add_new_item'      => __('Add New PTYN', 'radio-station-rds-extension'),
        'new_item_name'     => __('New PTYN Name', 'radio-station-rds-extension'),
        'search_items'      => __('Search PTYN', 'radio-station-rds-extension'),
        'popular_items'     => __('Popular PTYN', 'radio-station-rds-extension'),
    ];

    register_taxonomy('fm_rds_ptyn', ['show', 'override'], [
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'rest_base'         => 'fm_rds_ptyn',
        'show_admin_column' => true,
        'meta_box_cb'       => 'rsrds_ptyn_meta_box',
    ]);
}
add_action('init', 'rsrds_register_ptyn_taxonomy');

// --- Meta box for FM-RDS-PTY ---
function rsrds_pty_meta_box($post)
{
    $terms = get_terms([
        'taxonomy' => 'fm_rds_pty',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    // Get current term
    $current_terms = wp_get_post_terms($post->ID, 'fm_rds_pty', ['fields' => 'ids']);
    $current_term_id = !empty($current_terms) ? $current_terms[0] : '';

    // Nonce for security
    wp_nonce_field('rsrds_taxonomy_meta_box', 'rsrds_taxonomy_nonce');

    echo '<select name="tax_input[fm_rds_pty]" id="fm_rds_pty">';
    echo '<option value="">— ' . esc_html__('Select PTY', 'radio-station-rds-extension') . ' —</option>';

    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            $selected = ($term->term_id == $current_term_id) ? 'selected' : '';
            echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' .
                esc_html($term->name) . '</option>';
        }
    }
    echo '</select>';
}

// --- Meta box for FM-RDS-PTYN ---
function rsrds_ptyn_meta_box($post)
{
    $terms = get_terms([
        'taxonomy' => 'fm_rds_ptyn',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    // Get current term
    $current_terms = wp_get_post_terms($post->ID, 'fm_rds_ptyn', ['fields' => 'ids']);
    $current_term_id = !empty($current_terms) ? $current_terms[0] : '';

    // Nonce for security
    wp_nonce_field('rsrds_taxonomy_meta_box', 'rsrds_taxonomy_nonce');

    echo '<select name="tax_input[fm_rds_ptyn]" id="fm_rds_ptyn">';
    echo '<option value="">— ' . esc_html__('Select PTYN', 'radio-station-rds-extension') . ' —</option>';

    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            $selected = ($term->term_id == $current_term_id) ? 'selected' : '';
            echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' .
                esc_html($term->name) . '</option>';
        }
    }
    echo '</select>';
}

// --- Save single-term enforcement ---
function rsrds_save_single_term($post_id, $post, $update)
{
    // Check autosave and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Verify post type
    if (!in_array($post->post_type, ['show', 'override'])) {
        return;
    }

    // Check nonce
    if (
        !isset($_POST['rsrds_taxonomy_nonce']) ||
        !wp_verify_nonce($_POST['rsrds_taxonomy_nonce'], 'rsrds_taxonomy_meta_box')
    ) {
        return;
    }

    // Check user capabilities
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Process PTY
    if (isset($_POST['tax_input']['fm_rds_pty']) && !empty($_POST['tax_input']['fm_rds_pty'])) {
        $term_id = intval($_POST['tax_input']['fm_rds_pty']);
        wp_set_post_terms($post_id, [$term_id], 'fm_rds_pty', false);
    } else {
        wp_delete_object_term_relationships($post_id, 'fm_rds_pty');
    }

    // Process PTYN
    if (isset($_POST['tax_input']['fm_rds_ptyn']) && !empty($_POST['tax_input']['fm_rds_ptyn'])) {
        $term_id = intval($_POST['tax_input']['fm_rds_ptyn']);
        wp_set_post_terms($post_id, [$term_id], 'fm_rds_ptyn', false);
    } else {
        wp_delete_object_term_relationships($post_id, 'fm_rds_ptyn');
    }
}
add_action('save_post', 'rsrds_save_single_term', 10, 3);

// --- Helper function to get PTY mapping from term meta ---
function rsrds_get_pty_mapping($term_id)
{
    $mapping = get_term_meta($term_id, 'rsrds_pty_code', true);
    return !empty($mapping) ? $mapping : '';
}

// --- Helper function to get PTY code from term name (for backward compatibility) ---
function rsrds_get_pty_code_from_name($term_name)
{
    // Default mapping for Dutch terms (can be overridden by term meta)
    static $default_mapping = [
        'Nieuws'            => '1',
        'Actualiteit'       => '2',
        'Sport'             => '4',
        'Cultuur'           => '7',
        'Pop'               => '10',
        'Rock'              => '11',
        'Ontspanning'       => '12',
        'Nationale muziek'  => '26',
        'Volksmuziek'       => '28',
        'Gouwe Ouwe'        => '27',
        'Overige muziek'    => '15',
    ];

    // Check if term exists and has meta
    $term = get_term_by('name', $term_name, 'fm_rds_pty');
    if ($term && !is_wp_error($term)) {
        $mapping = rsrds_get_pty_mapping($term->term_id);
        if (!empty($mapping)) {
            return $mapping;
        }
    }

    // Fallback to default mapping
    return isset($default_mapping[$term_name]) ? $default_mapping[$term_name] : '';
}

// --- REST endpoint: metadata/v1/current ---
function rsrds_rest_current()
{
    $response = wp_remote_get(site_url('/wp-json/radio/broadcast'), [
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        return [
            'status' => 'error',
            'message' => __('Failed to get broadcast', 'radio-station-rds-extension')
        ];
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($data['broadcast']['current_show'])) {
        return ['status' => 'off-air'];
    }

    $tz = wp_timezone();

    foreach (['current_show', 'next_show'] as $key) {
        if (empty($data['broadcast'][$key])) {
            continue;
        }

        $show_data = &$data['broadcast'][$key];
        $show = $show_data['show'];
        $show_id = $show['id'];
        $post_type = get_post_type($show_id);

        // Get PTY and PTYN terms
        $pty_terms = wp_get_post_terms($show_id, 'fm_rds_pty', ['fields' => 'ids']);
        $ptyn_terms = wp_get_post_terms($show_id, 'fm_rds_ptyn', ['fields' => 'names']);

        // Get PTY code from term meta
        $pty_code = '';
        if (!empty($pty_terms) && !is_wp_error($pty_terms)) {
            $term_id = $pty_terms[0];
            $pty_code = rsrds_get_pty_mapping($term_id);

            // Fallback to name-based mapping if no meta exists
            if (empty($pty_code)) {
                $term = get_term($term_id, 'fm_rds_pty');
                if ($term && !is_wp_error($term)) {
                    $pty_code = rsrds_get_pty_code_from_name($term->name);
                }
            }
        }

        $show_data['fm_rds_pty'] = $pty_code;
        $show_data['fm_rds_ptyn'] = !empty($ptyn_terms) ? $ptyn_terms[0] : '';

        // Format dates
        $date = $show_data['date'];
        $start_dt = new DateTime("$date {$show_data['start']}", $tz);
        $end_dt = new DateTime("$date {$show_data['end']}", $tz);
        $show_data['start'] = $start_dt->format(DateTime::RFC3339);
        $show_data['end'] = $end_dt->format(DateTime::RFC3339);

        if ($key === 'current_show') {
            $show_data['expiry'] = $end_dt->format(DateTime::RFC3339);
        }

        // Handle temporary overrides
        if ($post_type === 'override') {
            $override = get_post_meta($show_id, 'temporary_override', true);
            if (is_array($override) && !empty($override['active'])) {
                $now = new DateTime('now', $tz);
                $start = new DateTime($override['start'], $tz);
                $end = new DateTime($override['end'], $tz);

                if ($now >= $start && $now <= $end) {
                    $show_data['show_name'] = $override['name'] ?? $show_data['show_name'];
                    $show_data['hosts'] = $override['hosts'] ?? $show_data['hosts'];

                    // Handle override PTY
                    if (!empty($override['fm_rds_pty'])) {
                        $override_term = get_term_by('name', $override['fm_rds_pty'], 'fm_rds_pty');
                        if ($override_term && !is_wp_error($override_term)) {
                            $pty_code = rsrds_get_pty_mapping($override_term->term_id);
                            if (empty($pty_code)) {
                                $pty_code = rsrds_get_pty_code_from_name($override_term->name);
                            }
                            $show_data['fm_rds_pty'] = $pty_code;
                        }
                    }

                    $show_data['fm_rds_ptyn'] = $override['fm_rds_ptyn'] ?? $show_data['fm_rds_ptyn'];
                    $show_data['start'] = $start->format(DateTime::RFC3339);
                    $show_data['end'] = $end->format(DateTime::RFC3339);

                    if ($key === 'current_show') {
                        $show_data['expiry'] = $end->format(DateTime::RFC3339);
                    }
                }
            }
        }
    }

    return $data;
}

// --- Register REST endpoint ---
add_action('rest_api_init', function () {
    register_rest_route('metadata/v1', '/current', [
        'methods' => 'GET',
        'callback' => 'rsrds_rest_current',
        'permission_callback' => '__return_true',
    ]);
});

// --- Activation hook to create default terms ---
function rsrds_activate()
{
    $default_pty_terms = [
        'Nieuws' => '1',
        'Actualiteit' => '2',
        'Sport' => '4',
        'Cultuur' => '7',
        'Pop' => '10',
        'Rock' => '11',
        'Ontspanning' => '12',
        'Nationale muziek' => '26',
        'Volksmuziek' => '28',
        'Gouwe Ouwe' => '27',
        'Overige muziek' => '15',
    ];

    $default_ptyn_terms = [
        'Hot AC',
        'Politiek',
        'Voetbal',
        'Blues',
        'Ballads',
        'NL-Talig',
        'Old Hits',
        'SportMix',
        'Dance',
        'Variatie',
        'Human',
    ];

    // Create PTY terms with meta
    foreach ($default_pty_terms as $term_name => $pty_code) {
        $term = term_exists($term_name, 'fm_rds_pty');
        if (!$term) {
            $term = wp_insert_term($term_name, 'fm_rds_pty');
            if (!is_wp_error($term)) {
                add_term_meta($term['term_id'], 'rsrds_pty_code', $pty_code, true);
            }
        }
    }

    // Create PTYN terms
    foreach ($default_ptyn_terms as $term_name) {
        $term = term_exists($term_name, 'fm_rds_ptyn');
        if (!$term) {
            wp_insert_term($term_name, 'fm_rds_ptyn');
        }
    }
}
register_activation_hook(__FILE__, 'rsrds_activate');

// --- Deactivation hook ---
function rsrds_deactivate()
{
    // Cleanup if needed
}
register_deactivation_hook(__FILE__, 'rsrds_deactivate');
