<?php
/**
 * Plugin Name: Radio Station – RDS Extension
 * Plugin URI:  https://github.com/HansVanEijsden/radio-station-rds-extension
 * Description: Extends the Radio Station plugin with FM-RDS-PTY and FM-RDS-PTYN taxonomies and adds them to the REST API output.
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
define('RSRDS_METADATA_CACHE_KEY', 'rsrds_metadata_current');

// --- Single source of truth for term lists ---
// PTY terms map term name => numeric PTY code. PTYN terms are a plain list of names.
// Everything else (meta boxes, save allowlists, activation seeds, REST output) derives from these.
function rsrds_get_pty_terms() {
    return [
        'Nieuws'           => '1',
        'Actualiteit'      => '2',
        'Sport'            => '4',
        'Cultuur'          => '7',
        'Pop'              => '10',
        'Rock'             => '11',
        'Ontspanning'      => '12',
        'Nationale muziek' => '26',
        'Volksmuziek'      => '28',
        'Gouwe Ouwe'       => '27',
        'Overige muziek'   => '15',
    ];
}

function rsrds_get_ptyn_terms() {
    return [
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
}

// --- Register FM-RDS-PTY taxonomy ---
function rsrds_register_pty_taxonomy() {
    $labels = [
        'name'          => _x('FM-RDS-PTY', 'taxonomy general name', 'radio-station-rds-extension'),
        'singular_name' => _x('FM-RDS-PTY', 'taxonomy singular name', 'radio-station-rds-extension'),
        'menu_name'     => _x('FM-RDS-PTY', 'admin menu', 'radio-station-rds-extension'),
        'all_items'     => __('All PTY', 'radio-station-rds-extension'),
        'edit_item'     => __('Edit PTY', 'radio-station-rds-extension'),
        'update_item'   => __('Update PTY', 'radio-station-rds-extension'),
        'add_new_item'  => __('Add New PTY', 'radio-station-rds-extension'),
        'new_item_name' => __('New PTY Name', 'radio-station-rds-extension'),
        'search_items'  => __('Search PTY', 'radio-station-rds-extension'),
        'popular_items' => __('Popular PTY', 'radio-station-rds-extension'),
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

// --- Register FM-RDS-PTYN taxonomy ---
function rsrds_register_ptyn_taxonomy() {
    $labels = [
        'name'          => _x('FM-RDS-PTYN', 'taxonomy general name', 'radio-station-rds-extension'),
        'singular_name' => _x('FM-RDS-PTYN', 'taxonomy singular name', 'radio-station-rds-extension'),
        'menu_name'     => _x('FM-RDS-PTYN', 'admin menu', 'radio-station-rds-extension'),
        'all_items'     => __('All PTYN', 'radio-station-rds-extension'),
        'edit_item'     => __('Edit PTYN', 'radio-station-rds-extension'),
        'update_item'   => __('Update PTYN', 'radio-station-rds-extension'),
        'add_new_item'  => __('Add New PTYN', 'radio-station-rds-extension'),
        'new_item_name' => __('New PTYN Name', 'radio-station-rds-extension'),
        'search_items'  => __('Search PTYN', 'radio-station-rds-extension'),
        'popular_items' => __('Popular PTYN', 'radio-station-rds-extension'),
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
function rsrds_pty_meta_box($post) {
    $terms = array_keys(rsrds_get_pty_terms());

    $current = wp_get_post_terms($post->ID, 'fm_rds_pty', ['fields' => 'names']);
    $current = $current ? $current[0] : '';
    
    // Nonce for security
    wp_nonce_field('rsrds_taxonomy_meta_box', 'rsrds_taxonomy_nonce');
    
    ?>
    <select name="tax_input[fm_rds_pty]" id="fm_rds_pty">
        <option value="">— <?php esc_html_e('Select PTY', 'radio-station-rds-extension'); ?> —</option>
        <?php foreach ($terms as $term) : ?>
            <option value="<?php echo esc_attr($term); ?>" <?php selected($term, $current); ?>>
                <?php echo esc_html($term); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

// --- Meta box for FM-RDS-PTYN ---
function rsrds_ptyn_meta_box($post) {
    $terms = rsrds_get_ptyn_terms();

    $current = wp_get_post_terms($post->ID, 'fm_rds_ptyn', ['fields' => 'names']);
    $current = $current ? $current[0] : '';
    
    // Nonce for security
    wp_nonce_field('rsrds_taxonomy_meta_box', 'rsrds_taxonomy_nonce');
    
    ?>
    <select name="tax_input[fm_rds_ptyn]" id="fm_rds_ptyn">
        <option value="">— <?php esc_html_e('Select PTYN', 'radio-station-rds-extension'); ?> —</option>
        <?php foreach ($terms as $term) : ?>
            <option value="<?php echo esc_attr($term); ?>" <?php selected($term, $current); ?>>
                <?php echo esc_html($term); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

// --- Save single-term enforcement ---
function rsrds_save_single_term($post_id, $post, $update) {
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
    if (!isset($_POST['rsrds_taxonomy_nonce']) ||
        !wp_verify_nonce($_POST['rsrds_taxonomy_nonce'], 'rsrds_taxonomy_meta_box')) {
        return;
    }

    // Check user capabilities
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Allowed term names, derived from the single source of truth
    $allowed_pty  = array_keys(rsrds_get_pty_terms());
    $allowed_ptyn = rsrds_get_ptyn_terms();

    // Save PTY (an explicitly empty submitted value clears the selection)
    $pty_input = $_POST['tax_input']['fm_rds_pty'] ?? '';
    if (is_string($pty_input)) {
        $pty_value = sanitize_text_field($pty_input);
        if ('' !== $pty_value) {
            if (in_array($pty_value, $allowed_pty, true)) {
                wp_set_post_terms($post_id, [$pty_value], 'fm_rds_pty', false);
            }
        } else {
            wp_set_post_terms($post_id, [], 'fm_rds_pty');
        }
    }

    // Save PTYN (an explicitly empty submitted value clears the selection)
    $ptyn_input = $_POST['tax_input']['fm_rds_ptyn'] ?? '';
    if (is_string($ptyn_input)) {
        $ptyn_value = sanitize_text_field($ptyn_input);
        if ('' !== $ptyn_value) {
            if (in_array($ptyn_value, $allowed_ptyn, true)) {
                wp_set_post_terms($post_id, [$ptyn_value], 'fm_rds_ptyn', false);
            }
        } else {
            wp_set_post_terms($post_id, [], 'fm_rds_ptyn');
        }
    }
}
add_action('save_post', 'rsrds_save_single_term', 10, 3);

// --- REST endpoint: metadata/v1/current ---
function rsrds_rest_current() {
    // Serve a short-lived cache of the merged broadcast payload when available.
    $cached = get_transient(RSRDS_METADATA_CACHE_KEY);
    if (false !== $cached) {
        return $cached;
    }

    $response = wp_remote_get(site_url('/wp-json/radio/broadcast'), [
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        return [
            'status'  => 'error',
            'message' => __('Failed to get broadcast', 'radio-station-rds-extension'),
        ];
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!is_array($data) || empty($data['broadcast']['current_show'])) {
        return ['status' => 'off-air'];
    }

    // Consistent envelope: always expose a machine-readable status on success.
    $data['status'] = isset($data['status']) ? $data['status'] : 'on-air';

    $tz      = wp_timezone();
    $pty_map = rsrds_get_pty_terms();

    foreach (['current_show', 'next_show'] as $key) {
        if (empty($data['broadcast'][$key]) || !is_array($data['broadcast'][$key])) {
            continue;
        }

        $show_data = &$data['broadcast'][$key];

        // Defensive: Radio Station may omit show details for a time slot.
        if (empty($show_data['show']) || !is_array($show_data['show']) || empty($show_data['show']['id'])) {
            continue;
        }

        $show_id   = $show_data['show']['id'];
        $post_type = get_post_type($show_id);

        $fm_rds_pty  = wp_get_post_terms($show_id, 'fm_rds_pty', ['fields' => 'names']);
        $fm_rds_ptyn = wp_get_post_terms($show_id, 'fm_rds_ptyn', ['fields' => 'names']);

        $pty_value = $fm_rds_pty && isset($pty_map[$fm_rds_pty[0]]) ? $pty_map[$fm_rds_pty[0]] : '';
        $show_data['fm_rds_pty']  = $pty_value;
        $show_data['fm_rds_ptyn'] = $fm_rds_ptyn ? $fm_rds_ptyn[0] : '';

        // Convert times to RFC3339 only when all parts are present.
        if (!empty($show_data['date']) && !empty($show_data['start']) && !empty($show_data['end'])) {
            $date     = $show_data['date'];
            $start_dt = new DateTime("$date {$show_data['start']}", $tz);
            $end_dt   = new DateTime("$date {$show_data['end']}", $tz);
            $show_data['start'] = $start_dt->format(DateTime::RFC3339);
            $show_data['end']   = $end_dt->format(DateTime::RFC3339);

            if ($key === 'current_show') {
                $show_data['expiry'] = $end_dt->format(DateTime::RFC3339);
            }
        }

        if ($post_type === 'override') {
            $override = get_post_meta($show_id, 'temporary_override', true);
            if (is_array($override) && !empty($override['active']) && !empty($override['start']) && !empty($override['end'])) {
                $now   = new DateTime('now', $tz);
                $start = new DateTime($override['start'], $tz);
                $end   = new DateTime($override['end'], $tz);
                if ($now >= $start && $now <= $end) {
                    $show_data['show_name']   = $override['name'] ?? $show_data['show_name'];
                    $show_data['hosts']       = $override['hosts'] ?? $show_data['hosts'];
                    $show_data['fm_rds_pty']  = isset($pty_map[$override['fm_rds_pty']]) ? $pty_map[$override['fm_rds_pty']] : $show_data['fm_rds_pty'];
                    $show_data['fm_rds_ptyn'] = $override['fm_rds_ptyn'] ?? $show_data['fm_rds_ptyn'];
                    $show_data['start']       = $start->format(DateTime::RFC3339);
                    $show_data['end']         = $end->format(DateTime::RFC3339);
                    if ($key === 'current_show') {
                        $show_data['expiry'] = $end->format(DateTime::RFC3339);
                    }
                }
            }
        }
    }

    // Only cache successful payloads; a short TTL keeps term/override changes fresh.
    set_transient(RSRDS_METADATA_CACHE_KEY, $data, 30);

    return $data;
}

// --- Clear the cached metadata payload when a show/override is saved ---
function rsrds_clear_metadata_cache($post_id) {
    if (in_array(get_post_type($post_id), ['show', 'override'])) {
        delete_transient(RSRDS_METADATA_CACHE_KEY);
    }
}
add_action('save_post', 'rsrds_clear_metadata_cache', 20);

// --- Register REST endpoint ---
add_action('rest_api_init', function() {
    register_rest_route('metadata/v1', '/current', [
        'methods'             => 'GET',
        'callback'            => 'rsrds_rest_current',
        'permission_callback' => '__return_true',
        'schema'              => 'rsrds_rest_current_schema',
    ]);
});

function rsrds_rest_current_schema() {
    return [
        '$schema'    => 'http://json-schema.org/draft-04/schema#',
        'title'      => 'metadata-current',
        'type'       => 'object',
        'properties' => [
            'status' => [
                'description' => __('Broadcast status', 'radio-station-rds-extension'),
                'type'        => 'string',
                'enum'        => ['on-air', 'off-air', 'error'],
            ],
            'message' => [
                'description' => __('Error message when status is error', 'radio-station-rds-extension'),
                'type'        => 'string',
            ],
            'broadcast' => [
                'description' => __('Broadcast payload with current and next show, enriched with fm_rds_pty and fm_rds_ptyn', 'radio-station-rds-extension'),
                'type'        => 'object',
            ],
        ],
    ];
}

// --- Activation hook to create default terms ---
function rsrds_activate() {
    // Create PTYN terms from the single source of truth
    foreach (rsrds_get_ptyn_terms() as $term_name) {
        $term = term_exists($term_name, 'fm_rds_ptyn');
        if (!$term) {
            wp_insert_term($term_name, 'fm_rds_ptyn');
        }
    }

    // Create PTY terms from the single source of truth
    foreach (array_keys(rsrds_get_pty_terms()) as $term_name) {
        $term = term_exists($term_name, 'fm_rds_pty');
        if (!$term) {
            wp_insert_term($term_name, 'fm_rds_pty');
        }
    }
}
register_activation_hook(__FILE__, 'rsrds_activate');