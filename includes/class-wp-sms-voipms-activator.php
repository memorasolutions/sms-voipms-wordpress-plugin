<?php
/**
 * Le code qui s'exécute pendant l'activation du plugin.
 */
class Wp_Sms_Voipms_Activator {

    /**
     * Activation du plugin.
     *
     * Crée les tables personnalisées nécessaires
     * et initialise les options par défaut.
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::add_capabilities();
    }

    /**
     * Création des tables personnalisées.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table pour stocker les messages SMS
        $table_messages = $wpdb->prefix . 'voipms_sms_messages';
        
        $sql = "CREATE TABLE $table_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            from_number varchar(20) NOT NULL,
            to_number varchar(20) NOT NULL,
            message text NOT NULL,
            direction varchar(10) NOT NULL,
            status varchar(20) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            delivery_status varchar(50),
            message_id varchar(100),
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Table pour stocker les contacts
        $table_contacts = $wpdb->prefix . 'voipms_sms_contacts';
        
        $sql .= "CREATE TABLE $table_contacts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            phone_number varchar(20) NOT NULL,
            name varchar(100) NOT NULL,
            email varchar(100),
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY phone_user (phone_number, user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Configuration des options par défaut.
     */
    private static function set_default_options() {
        // Options de configuration VoIPms
        add_option('wp_sms_voipms_api_username', '');
        add_option('wp_sms_voipms_api_password', '');
        add_option('wp_sms_voipms_did', ''); // Numéro DID par défaut
        
        // Options de personnalisation
        add_option('wp_sms_voipms_platform_name', 'WP SMS VoIPms');
        add_option('wp_sms_voipms_primary_color', '#007bff');
        add_option('wp_sms_voipms_secondary_color', '#6c757d');
        add_option('wp_sms_voipms_custom_logo', '');
        
        // Options de configuration
        add_option('wp_sms_voipms_message_limit_enabled', false);
        add_option('wp_sms_voipms_message_limit_count', 100);
        add_option('wp_sms_voipms_webhook_url', site_url('wp-json/wp-sms-voipms/v1/receive'));
    }

    /**
     * Ajouter les capacités aux rôles.
     */
    private static function add_capabilities() {
        // Administrateur
        $admin = get_role('administrator');
        $admin->add_cap('manage_voipms_sms');
        $admin->add_cap('send_voipms_sms');
        $admin->add_cap('read_voipms_sms');
        
        // Éditeur
        $editor = get_role('editor');
        $editor->add_cap('send_voipms_sms');
        $editor->add_cap('read_voipms_sms');
        
        // Auteur
        $author = get_role('author');
        $author->add_cap('read_voipms_sms');
    }
}