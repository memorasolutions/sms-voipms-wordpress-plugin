<?php
/**
 * La fonctionnalité publique du plugin.
 */
class Wp_Sms_Voipms_Public {

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
     * Enregistrer les styles pour la partie publique.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wp-sms-voipms-public.css', array(), $this->version, 'all');
        
        // Définir les variables CSS personnalisées
        $primary_color = get_option('wp_sms_voipms_primary_color', '#007bff');
        $secondary_color = get_option('wp_sms_voipms_secondary_color', '#6c757d');
        
        // Convertir la couleur hexadécimale en RGB pour les transparences
        $primary_rgb = $this->hex2rgb($primary_color);
        $secondary_rgb = $this->hex2rgb($secondary_color);
        
        $custom_css = "
            :root {
                --primary-color: {$primary_color};
                --primary-color-rgb: {$primary_rgb};
                --secondary-color: {$secondary_color};
                --secondary-color-rgb: {$secondary_rgb};
            }
        ";
        
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    /**
     * Enregistrer les scripts pour la partie publique.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-sms-voipms-public.js', array('jquery'), $this->version, false);
        
        // Ajouter les variables locales pour le script
        wp_localize_script($this->plugin_name, 'wp_sms_voipms', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_sms_voipms_nonce'),
            'rest_url' => rest_url('wp-sms-voipms/v1/'),
            'current_user_id' => get_current_user_id(),
            'loading_contacts' => __('Chargement des contacts...', 'wp-sms-voipms'),
            'loading_messages' => __('Chargement des messages...', 'wp-sms-voipms'),
            'no_contacts' => __('Aucun contact trouvé. Cliquez sur "Nouvelle conversation" pour commencer.', 'wp-sms-voipms'),
            'no_messages' => __('Aucun message dans cette conversation. Envoyez un message pour commencer.', 'wp-sms-voipms'),
            'error_loading_contacts' => __('Erreur lors du chargement des contacts.', 'wp-sms-voipms'),
            'error_loading_messages' => __('Erreur lors du chargement des messages.', 'wp-sms-voipms'),
            'error_sending' => __('Erreur lors de l\'envoi du message.', 'wp-sms-voipms'),
            'enter_phone_number' => __('Veuillez entrer un numéro de téléphone.', 'wp-sms-voipms'),
            'sending' => __('Envoi...', 'wp-sms-voipms'),
            'send' => __('Envoyer', 'wp-sms-voipms'),
            'today' => __('Aujourd\'hui', 'wp-sms-voipms'),
            'yesterday' => __('Hier', 'wp-sms-voipms')
        ));
    }
    
    /**
     * Convertir une couleur hexadécimale en format RGB
     */
    private function hex2rgb($hex) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return "$r, $g, $b";
    }
}