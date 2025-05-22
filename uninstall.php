<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

global $wpdb;

// Delete options
$options = array(
    'wp_sms_voipms_api_username',
    'wp_sms_voipms_api_password',
    'wp_sms_voipms_did',
    'wp_sms_voipms_platform_name',
    'wp_sms_voipms_primary_color',
    'wp_sms_voipms_secondary_color',
    'wp_sms_voipms_custom_logo',
    'wp_sms_voipms_message_limit_enabled',
    'wp_sms_voipms_message_limit_count',
    'wp_sms_voipms_message_limit_period',
    'wp_sms_voipms_message_limit_period_value',
    'wp_sms_voipms_webhook_url'
);

foreach ($options as $option) {
    delete_option($option);
}

// Drop custom tables
$messages_table = $wpdb->prefix . 'voipms_sms_messages';
$contacts_table = $wpdb->prefix . 'voipms_sms_contacts';

$wpdb->query("DROP TABLE IF EXISTS $messages_table");
$wpdb->query("DROP TABLE IF EXISTS $contacts_table");

