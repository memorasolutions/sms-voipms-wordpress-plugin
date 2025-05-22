<?php
/**
 * Gestion des shortcodes du plugin.
 */
class Wp_Sms_Voipms_Shortcodes {
    
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
     * Shortcode [voipms_sms_interface] pour afficher l'interface d'envoi/réception.
     */
    public function sms_interface_shortcode($atts) {
        // Si l'utilisateur n'est pas connecté, afficher un message
        if (!is_user_logged_in()) {
            return '<p>' . __('Vous devez être connecté pour accéder à l\'interface SMS.', 'wp-sms-voipms') . '</p>';
        }
        
        // Si l'utilisateur n'a pas la permission, afficher un message
        if (!current_user_can('read_voipms_sms')) {
            return '<p>' . __('Vous n\'avez pas la permission d\'accéder à l\'interface SMS.', 'wp-sms-voipms') . '</p>';
        }
        
        // S'assurer que les styles et scripts sont chargés
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wp-sms-voipms-public.css', array(), $this->version, 'all');
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-sms-voipms-public.js', array('jquery'), $this->version, false);
        
        // Ajouter les variables locales pour le script
        wp_localize_script($this->plugin_name, 'wp_sms_voipms', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            // Utiliser le nonce WP REST pour éviter les erreurs d'authentification
            // lors des appels à la REST API depuis le shortcode.
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url' => rest_url('wp-sms-voipms/v1/'),
            'current_user_id' => get_current_user_id(),
            'platform_name' => get_option('wp_sms_voipms_platform_name', 'WP SMS VoIPms'),
            'primary_color' => get_option('wp_sms_voipms_primary_color', '#007bff'),
            'secondary_color' => get_option('wp_sms_voipms_secondary_color', '#6c757d')
        ));
        
        // Démarrer la mise en mémoire tampon
        ob_start();
        
        // Inclure le template
        include_once plugin_dir_path(__FILE__) . 'partials/wp-sms-voipms-public-interface.php';
        
        // Récupérer et renvoyer le contenu
        return ob_get_clean();
    }
}