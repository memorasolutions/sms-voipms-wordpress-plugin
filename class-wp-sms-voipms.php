<?php
/**
 * La classe principale du plugin.
 *
 * Cette classe définit les fonctionnalités de base du plugin :
 * - Chargement des dépendances
 * - Définition des hooks d'administration 
 * - Définition des hooks publics
 * - Chargement de la REST API
 */
class Wp_Sms_Voipms {

    /**
     * Le loader qui maintient tous les hooks du plugin.
     */
    protected $loader;

    /**
     * Identifiant unique du plugin.
     */
    protected $plugin_name;

    /**
     * Version actuelle du plugin.
     */
    protected $version;

    /**
     * Définir les fonctionnalités de base du plugin.
     */
    public function __construct() {
        $this->version = WP_SMS_VOIPMS_VERSION;
        $this->plugin_name = 'wp-sms-voipms';

        $this->load_dependencies();
        $this->loader->add_action('plugins_loaded', $this, 'load_textdomain');
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shortcodes();
        $this->load_rest_api();
    }

    /**
     * Charger les dépendances requises pour ce plugin.
     */
    private function load_dependencies() {
        /**
         * La classe responsable de l'orchestration des actions et filtres du plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-sms-voipms-loader.php';

        /**
         * La classe pour interagir avec l'API VoIP.ms.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-sms-voipms-api.php';
        
        /**
         * La classe responsable de la définition des fonctionnalités d'administration.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-sms-voipms-admin.php';

        /**
         * La classe responsable de la définition des fonctionnalités publiques.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-sms-voipms-public.php';
        
        /**
         * La classe responsable de la gestion des shortcodes.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-sms-voipms-shortcodes.php';
        
        /**
         * La classe responsable de l'implémentation de la REST API.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-sms-voipms-rest-api.php';

        $this->loader = new Wp_Sms_Voipms_Loader();
    }

    /**
     * Définir les hooks liés à la partie admin du plugin.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Wp_Sms_Voipms_Admin($this->get_plugin_name(), $this->get_version());

        // Ajouter les menus d'administration
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_menu');
        
        // Enregistrer les options
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // Ajouter les scripts et styles
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Ajouter des liens dans la page des plugins
        $this->loader->add_filter('plugin_action_links_wp-sms-voipms/wp-sms-voipms.php', $plugin_admin, 'add_plugin_action_links');

        // AJAX test de connexion API
        $this->loader->add_action('wp_ajax_wp_sms_voipms_test_api', $plugin_admin, 'ajax_test_api');
    }

    /**
     * Définir les hooks liés à la partie publique du plugin.
     */
    private function define_public_hooks() {
        $plugin_public = new Wp_Sms_Voipms_Public($this->get_plugin_name(), $this->get_version());

        // Ajouter les scripts et styles
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }
    
    /**
     * Définir les shortcodes.
     */
    private function define_shortcodes() {
        $plugin_shortcodes = new Wp_Sms_Voipms_Shortcodes($this->get_plugin_name(), $this->get_version());
        
        // Enregistrer les shortcodes
        add_shortcode('voipms_sms_interface', array($plugin_shortcodes, 'sms_interface_shortcode'));
    }
    
    /**
     * Initialiser la REST API.
     */
    private function load_rest_api() {
        $plugin_rest_api = new Wp_Sms_Voipms_Rest_Api();
        
        // Enregistrer les routes
        $this->loader->add_action('rest_api_init', $plugin_rest_api, 'register_routes');
    }

    /**
     * Démarrer le plugin.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Nom du plugin utilisé pour uniquement identifier le plugin dans le contexte de WP.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Le référence au chargeur qui coordonne les hooks du plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Récupérer le numéro de version du plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Charger les fichiers de traduction.
     */
    public function load_textdomain() {
        load_plugin_textdomain('wp-sms-voipms', false, dirname(plugin_basename(__FILE__)) . '/../languages');
    }
}