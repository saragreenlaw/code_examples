<?php
/*
Plugin Name: Clean Up SalesKing Meta with Logging (On-Demand)
Description: Removes SalesKing agent data from non-agents who are not assigned to real agents. Triggered manually by URL.
Version: 1.3
*/

add_action('admin_init', 'run_salesking_meta_cleanup_on_demand');

function run_salesking_meta_cleanup_on_demand() {
    if (!current_user_can('administrator')) return;
    if (!isset($_GET['run_salesking_cleanup'])) return;

    $real_agents = array(
        2, 3, 5, 6, 7, 247, 390, 391, 392, 393, 394, 395, 396, 397, 405, 406, 407, 417
    );

    $args = array(
        'number' => -1,
        'fields' => array('ID', 'user_login', 'user_email'),
    );

    $users = get_users($args);
    $log_entries = [];
    $timestamp = date('Y-m-d H:i:s');

    foreach ($users as $user) {
        $user_id = $user->ID;

        if (in_array($user_id, $real_agents)) continue;

        $assigned_agent = get_user_meta($user_id, 'salesking_assigned_agent', true);

        if (in_array((int) $assigned_agent, $real_agents)) continue;

        delete_user_meta($user_id, 'salesking_group');
        delete_user_meta($user_id, 'salesking_assigned_agent');
        delete_user_meta($user_id, 'salesking_additional_agents');

        $log_entries[] = "[{$timestamp}] Cleaned user ID: {$user->ID}, Username: {$user->user_login}, Email: {$user->user_email}";
    }

    if (!empty($log_entries)) {
        $log_file_path = plugin_dir_path(__FILE__) . 'cleaned_users.log';
        $log_file = fopen($log_file_path, 'a');
        foreach ($log_entries as $entry) {
            fwrite($log_file, $entry . PHP_EOL);
        }
        fclose($log_file);
    }

    add_action('admin_notices', function () {
        echo "<div class='notice notice-success is-dismissible'><p>✅ SalesKing cleanup ran successfully. Log saved to <code>cleaned_users.log</code>.</p></div>";
    });
}
