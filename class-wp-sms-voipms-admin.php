<?php
/**
 * La fonctionnalité d'administration du plugin.
 */
class Wp_Sms_Voipms_Admin {

    /**
     * Identifiant du plugin.
     */
    private $plugin_name;

    /**
     * Version du plugin.
     */
    private $version;

    /**
     * Initialiser la classe et définir ses propriétés.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Enregistrer les styles pour l'administration.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wp-sms-voipms-admin.css', array(), $this->version, 'all');
    }

    /**
     * Enregistrer les scripts pour l'administration.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-sms-voipms-admin.js', array('jquery'), $this->version, false);

        // Assurer que la médiathèque WordPress est disponible pour le téléversement de logo
        wp_enqueue_media();
        
        // Ajouter les variables locales pour le script
        wp_localize_script($this->plugin_name, 'wp_sms_voipms', array(
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('wp_rest'),
            'default_did' => get_option('wp_sms_voipms_did'),
            'rest_url'   => rest_url('wp-sms-voipms/v1/'),
            'current_user_id' => get_current_user_id(),
            // Messages localisés
            'loading_messages' => __('Chargement des messages...', 'wp-sms-voipms'),
            'no_contacts' => __('Aucun contact trouvé. Cliquez sur "Nouvelle conversation" pour commencer.', 'wp-sms-voipms'),
            'error_loading_contacts' => __('Erreur lors du chargement des contacts.', 'wp-sms-voipms'),
            'no_messages' => __('Aucun message dans cette conversation. Envoyez un message pour commencer.', 'wp-sms-voipms'),
            'error_loading_messages' => __('Erreur lors du chargement des messages.', 'wp-sms-voipms'),
            'error_sending' => __('Erreur lors de l\'envoi du message.', 'wp-sms-voipms'),
            'enter_phone_number' => __('Veuillez entrer un numéro de téléphone.', 'wp-sms-voipms'),
            'sending' => __('Envoi...', 'wp-sms-voipms'),
            'send' => __('Envoyer', 'wp-sms-voipms'),
            'today' => __('Aujourd\'hui', 'wp-sms-voipms'),
            'yesterday' => __('Hier', 'wp-sms-voipms'),
            'select_logo' => __('Sélectionner un logo', 'wp-sms-voipms'),
            'use_this_logo' => __('Utiliser ce logo', 'wp-sms-voipms'),
            'change_logo' => __('Changer le logo', 'wp-sms-voipms'),
            'add_logo' => __('Ajouter un logo', 'wp-sms-voipms'),
            'remove_logo' => __('Supprimer le logo', 'wp-sms-voipms')
        ));
    }

    /**
     * Ajouter les menus d'administration.
     */
    public function add_plugin_menu() {
        // Menu principal
        add_menu_page(
            __('WP SMS VoIPms', 'wp-sms-voipms'),
            __('SMS VoIPms', 'wp-sms-voipms'),
            'read_voipms_sms',
            'wp-sms-voipms',
            array($this, 'display_sms_interface_page'),
            'dashicons-phone',
            30
        );
        
        // Sous-menu pour l'interface SMS
        add_submenu_page(
            'wp-sms-voipms',
            __('Interface SMS', 'wp-sms-voipms'),
            __('Interface SMS', 'wp-sms-voipms'),
            'read_voipms_sms',
            'wp-sms-voipms',
            array($this, 'display_sms_interface_page')
        );
        
        // Sous-menu pour les contacts
        add_submenu_page(
            'wp-sms-voipms',
            __('Contacts', 'wp-sms-voipms'),
            __('Contacts', 'wp-sms-voipms'),
            'read_voipms_sms',
            'wp-sms-voipms-contacts',
            array($this, 'display_contacts_page')
        );
        
        // Sous-menu pour les réglages
        add_submenu_page(
            'wp-sms-voipms',
            __('Réglages', 'wp-sms-voipms'),
            __('Réglages', 'wp-sms-voipms'),
            'manage_voipms_sms',
            'wp-sms-voipms-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Afficher la page d'interface SMS.
     */
    public function display_sms_interface_page() {
        // Vérifier si l'utilisateur a la permission
        if (!current_user_can('read_voipms_sms')) {
            wp_die(__('Vous n\'avez pas la permission d\'accéder à cette page.', 'wp-sms-voipms'));
        }
        
        include_once plugin_dir_path(__FILE__) . 'partials/wp-sms-voipms-admin-interface.php';
    }

    /**
     * Afficher la page des contacts.
     */
    public function display_contacts_page() {
        // Vérifier si l'utilisateur a la permission
        if (!current_user_can('read_voipms_sms')) {
            wp_die(__('Vous n\'avez pas la permission d\'accéder à cette page.', 'wp-sms-voipms'));
        }
        
        include_once plugin_dir_path(__FILE__) . 'partials/wp-sms-voipms-admin-contacts.php';
    }

    /**
     * Afficher la page des réglages.
     */
    public function display_settings_page() {
        // Vérifier si l'utilisateur a la permission
        if (!current_user_can('manage_voipms_sms')) {
            wp_die(__('Vous n\'avez pas la permission d\'accéder à cette page.', 'wp-sms-voipms'));
        }
        
        include_once plugin_dir_path(__FILE__) . 'partials/wp-sms-voipms-admin-settings.php';
    }

    /**
     * Enregistrer les paramètres.
     */
    public function register_settings() {
        // Groupe VoIP.ms API
        register_setting(
            'wp_sms_voipms_api_group',
            'wp_sms_voipms_api_username',
            array('sanitize_callback' => 'sanitize_text_field')
        );
        
        register_setting(
            'wp_sms_voipms_api_group',
            'wp_sms_voipms_api_password',
            array('sanitize_callback' => array($this, 'encrypt_api_password'))
        );
        
        register_setting(
            'wp_sms_voipms_api_group',
            'wp_sms_voipms_did',
            array('sanitize_callback' => 'sanitize_text_field')
        );
        
        // Groupe de personnalisation
        register_setting(
            'wp_sms_voipms_customization_group',
            'wp_sms_voipms_platform_name',
            array('sanitize_callback' => 'sanitize_text_field')
        );
        
        register_setting(
            'wp_sms_voipms_customization_group',
            'wp_sms_voipms_primary_color',
            array('sanitize_callback' => 'sanitize_hex_color')
        );
        
        register_setting(
            'wp_sms_voipms_customization_group',
            'wp_sms_voipms_secondary_color',
            array('sanitize_callback' => 'sanitize_hex_color')
        );
        
        register_setting(
            'wp_sms_voipms_customization_group',
            'wp_sms_voipms_custom_logo',
            array('sanitize_callback' => 'absint')
        );
        
        // Groupe de configuration des limites
        register_setting(
            'wp_sms_voipms_limits_group',
            'wp_sms_voipms_message_limit_enabled',
            array('sanitize_callback' => array($this, 'sanitize_checkbox'))
        );
        
        register_setting(
            'wp_sms_voipms_limits_group',
            'wp_sms_voipms_message_limit_count',
            array('sanitize_callback' => 'absint')
        );

        register_setting(
            'wp_sms_voipms_limits_group',
            'wp_sms_voipms_message_limit_period',
            array('sanitize_callback' => 'sanitize_text_field')
        );

        register_setting(
            'wp_sms_voipms_limits_group',
            'wp_sms_voipms_message_limit_period_value',
            array('sanitize_callback' => 'absint')
        );
    }
    
    /**
     * Crypter le mot de passe API avant enregistrement.
     */
    public function encrypt_api_password($password) {
        if (empty($password)) {
            return get_option('wp_sms_voipms_api_password');
        }

        if (strpos($password, 'enc::') === 0) {
            return $password;
        }

        $key = defined('AUTH_KEY') ? AUTH_KEY : wp_salt('auth');
        $iv  = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);

        if ($encrypted) {
            return 'enc::' . base64_encode($iv) . '::' . base64_encode($encrypted);
        }

        return $password;
    }
    
    /**
     * Sanitize pour les champs checkbox.
     */
    public function sanitize_checkbox($input) {
        return (isset($input) && true == $input) ? true : false;
    }

    /**
     * Ajouter des liens dans la page des plugins.
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wp-sms-voipms-settings') . '">' . __('Réglages', 'wp-sms-voipms') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Gérer la requête AJAX de test de connexion API.
     */
    public function ajax_test_api() {
        check_ajax_referer('wp_sms_voipms_test_api', 'nonce');

        $api    = new Wp_Sms_Voipms_Api();
        $result = $api->check_balance();

        if (isset($result['status']) && $result['status'] === 'success') {
            wp_send_json_success(array('message' => __('Connexion réussie.', 'wp-sms-voipms')));
        }

        $error = isset($result['error']) ? $result['error'] : __('Échec de la connexion.', 'wp-sms-voipms');
        wp_send_json_error(array('message' => $error));
    }
}