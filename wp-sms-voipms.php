<?php
/**
 * Plugin Name: WP SMS VoIPms
 * Plugin URI: https://example.com/wp-sms-voipms
 * Description: Plugin permettant d'envoyer et recevoir des SMS via l'API VoIP.ms directement depuis WordPress.
 * Version: 1.0.0
 * Author: Votre Nom
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-sms-voipms
 * Domain Path: /languages
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('WPINC')) {
    die;
}

// Définition des constantes
define('WP_SMS_VOIPMS_VERSION', '1.0.0');
define('WP_SMS_VOIPMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_SMS_VOIPMS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Le code qui s'exécute pendant l'activation du plugin.
 */
function activate_wp_sms_voipms() {
    require_once WP_SMS_VOIPMS_PLUGIN_DIR . 'includes/class-wp-sms-voipms-activator.php';
    Wp_Sms_Voipms_Activator::activate();
}

/**
 * Le code qui s'exécute pendant la désactivation du plugin.
 */
function deactivate_wp_sms_voipms() {
    require_once WP_SMS_VOIPMS_PLUGIN_DIR . 'includes/class-wp-sms-voipms-deactivator.php';
    Wp_Sms_Voipms_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_sms_voipms');
register_deactivation_hook(__FILE__, 'deactivate_wp_sms_voipms');

/**
 * Le noyau du plugin.
 */
require_once WP_SMS_VOIPMS_PLUGIN_DIR . 'includes/class-wp-sms-voipms.php';

/**
 * Commence l'exécution du plugin.
 */
function run_wp_sms_voipms() {
    $plugin = new Wp_Sms_Voipms();
    $plugin->run();
}

run_wp_sms_voipms();