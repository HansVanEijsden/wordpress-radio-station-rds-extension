<?php
/**
 * Plugin Name: Radio Station – RDS Extension
 * Plugin URI:  https://hansvaneijsden.nl
 * Description: Extends the Radio Station plugin with FM-RDS-PTY and FM-RDS-PTYN taxonomies and adds them to the metadata middleware output.
 * Version:     1.0.0
 * Author: Hans van Eijsden Consultancy
 * Author URI: https://www.hansvaneijsden.nl
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

// --- Register FM-RDS-PTY taxonomy ---
function hans_register_fm_rds_pty_taxonomy() {
    $labels = [
        'name'          => 'FM-RDS-PTY',
        'singular_name' => 'FM-RDS-PTY',
        'menu_name'     => 'FM-RDS-PTY',
        'all_items'     => 'All PTY',
        'edit_item'     => 'Edit PTY',
        'update_item'   => 'Update PTY',
        'add_new_item'  => 'Add New PTY',
        'new_item_name' => 'New PTY Name',
    ];

    register_taxonomy('fm_rds_pty', ['show','override'], [
        'labels'        => $labels,
        'public'        => true,
        'hierarchical'  => false,
        'show_ui'       => true,
        'show_in_rest'  => true,
        'rest_base'     => 'fm_rds_pty',
        'meta_box_cb'   => 'hans_fm_rds_pty_meta_box',
    ]);
}
add_action('init', 'hans_register_fm_rds_pty_taxonomy');


// --- Register FM-RDS-PTYN taxonomy ---
function hans_register_fm_rds_ptyn_taxonomy() {
    $labels = [
        'name'          => 'FM-RDS-PTYN',
        'singular_name' => 'FM-RDS-PTYN',
        'menu_name'     => 'FM-RDS-PTYN',
        'all_items'     => 'All PTYN',
        'edit_item'     => 'Edit PTYN',
        'update_item'   => 'Update PTYN',
        'add_new_item'  => 'Add New PTYN',
        'new_item_name' => 'New PTYN Name',
    ];

    register_taxonomy('fm_rds_ptyn', ['show','override'], [
        'labels'        => $labels,
        'public'        => true,
        'hierarchical'  => false,
        'show_ui'       => true,
        'show_in_rest'  => true,
        'rest_base'     => 'fm_rds_ptyn',
        'meta_box_cb'   => 'hans_fm_rds_ptyn_meta_box',
    ]);
}
add_action('init', 'hans_register_fm_rds_ptyn_taxonomy');


// --- Meta box for FM-RDS-PTY ---
function hans_fm_rds_pty_meta_box($post) {
    $terms = [
        'Nieuws',
        'Actualiteit',
        'Sport',
        'Cultuur',
        'Pop',
        'Rock',
        'Ontspanning',
        'Nationale muziek',
        'Volksmuziek',
        'Gouwe Ouwe',
        'Overige muziek',
    ];

    $current = wp_get_post_terms($post->ID, 'fm_rds_pty', ['fields' => 'names']);
    $current = $current ? $current[0] : '';
    echo '<select name="tax_input[fm_rds_pty]" id="fm_rds_pty">';
    echo '<option value="">— Select PTY —</option>';
    foreach ($terms as $term) {
        $selected = ($term === $current) ? 'selected' : '';
        echo '<option value="' . esc_attr($term) . '" ' . $selected . '>' . esc_html($term) . '</option>';
    }
    echo '</select>';
}


// --- Meta box for FM-RDS-PTYN ---
function hans_fm_rds_ptyn_meta_box($post) {
    $terms = [
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

    $current = wp_get_post_terms($post->ID, 'fm_rds_ptyn', ['fields' => 'names']);
    $current = $current ? $current[0] : '';
    echo '<select name="tax_input[fm_rds_ptyn]" id="fm_rds_ptyn">';
    echo '<option value="">— Select PTYN —</option>';
    foreach ($terms as $term) {
        $selected = ($term === $current) ? 'selected' : '';
        echo '<option value="' . esc_attr($term) . '" ' . $selected . '>' . esc_html($term) . '</option>';
    }
    echo '</select>';
}


// --- Save single-term enforcement ---
function hans_save_single_term($post_id, $post, $update) {
    if (!in_array($post->post_type, ['show','override'])) return;

    // Only save if the term exists in our allowed list
    $allowed_ptyn = [
        'Hot AC', 'Politiek', 'Voetbal', 'Blues', 'Ballads',
        'NL-Talig', 'Old Hits', 'SportMix', 'Dance', 'Variatie', 'Human'
    ];
    
    $allowed_pty = [
        'Nieuws', 'Actualiteit', 'Sport', 'Cultuur', 'Pop', 'Rock',
        'Ontspanning', 'Nationale muziek', 'Volksmuziek', 'Gouwe Ouwe', 'Overige muziek'
    ];

    if (!empty($_POST['tax_input']['fm_rds_pty'])) {
        $pty_value = sanitize_text_field($_POST['tax_input']['fm_rds_pty']);
        // Only save if it's in our allowed list
        if (in_array($pty_value, $allowed_pty)) {
            wp_set_post_terms($post_id, [$pty_value], 'fm_rds_pty', false);
        }
    }

    if (!empty($_POST['tax_input']['fm_rds_ptyn'])) {
        $ptyn_value = sanitize_text_field($_POST['tax_input']['fm_rds_ptyn']);
        // Only save if it's in our allowed list
        if (in_array($ptyn_value, $allowed_ptyn)) {
            wp_set_post_terms($post_id, [$ptyn_value], 'fm_rds_ptyn', false);
        }
    }
}
add_action('save_post', 'hans_save_single_term', 10, 3);


// --- REST endpoint: metadata/v1/current ---
add_action('rest_api_init', function() {
    register_rest_route('metadata/v1', '/current', [
        'methods'  => 'GET',
        'callback' => function() {

            $response = wp_remote_get(site_url('/wp-json/radio/broadcast'));
            if (is_wp_error($response)) {
                return ['status' => 'error', 'message' => 'Failed to get broadcast'];
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($data['broadcast']['current_show'])) {
                return ['status' => 'off-air'];
            }

            $tz = wp_timezone();

            $pty_map = [
                'Nieuws'                    => '1',
                'Actualiteit'               => '2',
                'Sport'                    => '4',
                'Cultuur'                  => '7',
                'Pop'                      => '10',
                'Rock'                     => '11',
                'Ontspanning'              => '12',
                'Nationale muziek'         => '26',
                'Volksmuziek'              => '28',
                'Gouwe Ouwe'               => '27',
                'Overige muziek'           => '15',
            ];

            foreach (['current_show', 'next_show'] as $key) {
                if (empty($data['broadcast'][$key])) continue;

                $show_data = &$data['broadcast'][$key];
                $show = $show_data['show'];
                $show_id = $show['id'];
                $post_type = get_post_type($show_id);

                $fm_rds_pty  = wp_get_post_terms($show_id, 'fm_rds_pty', ['fields'=>'names']);
                $fm_rds_ptyn = wp_get_post_terms($show_id, 'fm_rds_ptyn', ['fields'=>'names']);

                $pty_value = $fm_rds_pty && isset($pty_map[$fm_rds_pty[0]]) ? $pty_map[$fm_rds_pty[0]] : '';
                $show_data['fm_rds_pty']  = $pty_value;
                $show_data['fm_rds_ptyn'] = $fm_rds_ptyn ? $fm_rds_ptyn[0] : '';

                $date = $show_data['date'];
                $start_dt = new DateTime("$date {$show_data['start']}", $tz);
                $end_dt   = new DateTime("$date {$show_data['end']}", $tz);
                $show_data['start'] = $start_dt->format(DateTime::RFC3339);
                $show_data['end']   = $end_dt->format(DateTime::RFC3339);
                if ($key === 'current_show') {
                    $show_data['expiry'] = $end_dt->format(DateTime::RFC3339);
                }

                if ($post_type === 'override') {
                    $override = get_post_meta($show_id, 'temporary_override', true);
                    if (is_array($override) && !empty($override['active'])) {
                        $now = new DateTime('now', $tz);
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

            return $data;
        },
        'permission_callback' => '__return_true',
    ]);
});