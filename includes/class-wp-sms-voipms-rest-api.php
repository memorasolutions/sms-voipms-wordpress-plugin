<?php
/**
 * Gestion de la REST API du plugin.
 */
class Wp_Sms_Voipms_Rest_Api {
    
    /**
     * Espace de noms de l'API.
     */
    private $namespace = 'wp-sms-voipms/v1';
    
    /**
     * Instance de la classe API VoIP.ms.
     */
    private $api;
    
    /**
     * Initialiser la classe.
     */
    public function __construct() {
        $this->api = new Wp_Sms_Voipms_Api();
    }
    
    /**
     * Enregistrer les routes de l'API.
     */
    public function register_routes() {
        // Route pour envoyer un SMS
        register_rest_route($this->namespace, '/send', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_sms'),
            'permission_callback' => array($this, 'send_sms_permissions_check')
        ));
        
        // Route pour récupérer les messages
        register_rest_route($this->namespace, '/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_messages'),
            'permission_callback' => array($this, 'read_messages_permissions_check')
        ));
        
        // Route pour recevoir les messages entrants (webhook)
        register_rest_route($this->namespace, '/receive', array(
            'methods' => 'POST',
            'callback' => array($this, 'receive_sms'),
            'permission_callback' => '__return_true' // Pas d'authentification pour le webhook
        ));
        
        // Route pour vérifier le solde du compte
        register_rest_route($this->namespace, '/balance', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_balance'),
            'permission_callback' => array($this, 'manage_settings_permissions_check')
        ));
    }
    
    /**
     * Vérifier les permissions pour envoyer un SMS.
     */
    public function send_sms_permissions_check($request) {
        return current_user_can('send_voipms_sms');
    }
    
    /**
     * Vérifier les permissions pour lire les messages.
     */
    public function read_messages_permissions_check($request) {
        return current_user_can('read_voipms_sms');
    }
    
    /**
     * Vérifier les permissions pour gérer les réglages.
     */
    public function manage_settings_permissions_check($request) {
        return current_user_can('manage_voipms_sms');
    }
    
    /**
     * Gérer l'envoi d'un SMS.
     */
    public function send_sms($request) {
        $from = $request->get_param('from');
        $to = $request->get_param('to');
        $message = $request->get_param('message');
        
        // Vérifier les paramètres obligatoires
        if (empty($to) || empty($message)) {
            return new WP_Error(
                'missing_parameters',
                __('Les paramètres "to" et "message" sont obligatoires.', 'wp-sms-voipms'),
                array('status' => 400)
            );
        }
        
        // Si le numéro d'expéditeur n'est pas fourni, utiliser le DID de l'utilisateur
        if (empty($from)) {
            $from = $this->api->get_user_did(get_current_user_id());
        }
        
        // Vérifier les limites de messages si activées
        if (get_option('wp_sms_voipms_message_limit_enabled', false)) {
            $user_id = get_current_user_id();
            $limit = get_option('wp_sms_voipms_message_limit_count', 100);
            
            // Récupérer le nombre de messages envoyés par cet utilisateur
            $sent_count = $this->get_user_sent_count($user_id);
            
            if ($sent_count >= $limit) {
                return new WP_Error(
                    'message_limit_reached',
                    __('Vous avez atteint votre limite de messages.', 'wp-sms-voipms'),
                    array('status' => 403)
                );
            }
        }
        
        // Envoyer le SMS via l'API
        $result = $this->api->send_sms($from, $to, $message);
        
        if (isset($result['status']) && $result['status'] === 'success') {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error(
                'send_failed',
                isset($result['error']) ? $result['error'] : __('Échec de l\'envoi du SMS.', 'wp-sms-voipms'),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Récupérer le nombre de messages envoyés par un utilisateur.
     */
    private function get_user_sent_count($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'voipms_sms_messages';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND direction = 'outgoing'",
            $user_id
        ));
        
        return (int) $count;
    }
    
    /**
     * Récupérer les messages.
     */
    public function get_messages($request) {
        $user_id = get_current_user_id();
        $contact = $request->get_param('contact');
        $limit = (int) $request->get_param('limit');
        $offset = (int) $request->get_param('offset');
        
        // Définir des valeurs par défaut
        if ($limit <= 0) {
            $limit = 50;
        }
        
        if ($offset < 0) {
            $offset = 0;
        }
        
        // Récupérer les messages
        $messages = $this->api->get_user_messages($user_id, $contact, $limit, $offset);
        
        return new WP_REST_Response($messages, 200);
    }
    
    /**
     * Recevoir un SMS entrant (webhook).
     */
    public function receive_sms($request) {
        // Paramètres attendus de VoIP.ms
        $from = $request->get_param('from');
        $to = $request->get_param('to');
        $message = $request->get_param('message');
        $message_id = $request->get_param('id');
        
        // Vérifier les paramètres obligatoires
        if (empty($from) || empty($to) || empty($message)) {
            return new WP_Error(
                'missing_parameters',
                __('Paramètres manquants.', 'wp-sms-voipms'),
                array('status' => 400)
            );
        }
        
        // Enregistrer le message entrant
        $result = $this->api->save_incoming_message($from, $to, $message, $message_id);
        
        if ($result) {
            return new WP_REST_Response(array('status' => 'success'), 200);
        } else {
            return new WP_Error(
                'save_failed',
                __('Échec de l\'enregistrement du message.', 'wp-sms-voipms'),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Vérifier le solde du compte VoIP.ms.
     */
    public function check_balance($request) {
        $result = $this->api->check_balance();
        
        if (isset($result['status']) && $result['status'] === 'success') {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error(
                'balance_check_failed',
                isset($result['error']) ? $result['error'] : __('Échec de la vérification du solde.', 'wp-sms-voipms'),
                array('status' => 500)
            );
        }
    }
}