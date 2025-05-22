<?php
/**
 * Classe pour interagir avec l'API VoIP.ms.
 */
class Wp_Sms_Voipms_Api {

    /**
     * URL de base de l'API VoIP.ms.
     */
    private $api_url = 'https://voip.ms/api/v1/rest.php';
    
    /**
     * Identifiants API.
     */
    private $api_username;
    private $api_password;
    
    /**
     * Initialiser la classe et définir ses propriétés.
     */
    public function __construct() {
        $this->api_username = get_option('wp_sms_voipms_api_username');
        $stored             = get_option('wp_sms_voipms_api_password');
        $this->api_password = $this->decrypt_api_password($stored);
    }

    private function decrypt_api_password($value) {
        if (strpos($value, 'enc::') === 0) {
            $key = defined('AUTH_KEY') ? AUTH_KEY : wp_salt('auth');

            $data = substr($value, 5);
            $parts = explode('::', $data);

            if (count($parts) === 2) {
                $iv        = base64_decode($parts[0]);
                $encrypted = base64_decode($parts[1]);
            } else {
                // Ancien format sans IV
                $encrypted = base64_decode($data);
                $iv        = substr(hash('sha256', $key), 0, 16);
            }

            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
            if (false !== $decrypted) {
                return $decrypted;
            }
        }

        return $value;
    }
    
    /**
     * Envoyer un SMS via l'API VoIP.ms.
     *
     * @param string $from Le numéro de téléphone expéditeur (DID).
     * @param string $to Le numéro de téléphone destinataire.
     * @param string $message Le contenu du message.
     * @return array Résultat de l'opération.
     */
    public function send_sms($from, $to, $message) {
        // Nettoyer les numéros de téléphone
        $from = $this->clean_phone_number($from);
        $to = $this->clean_phone_number($to);
        
        // Vérifier si les identifiants API sont configurés
        if (empty($this->api_username) || empty($this->api_password)) {
            return array(
                'status' => 'error',
                'error' => 'API VoIP.ms non configurée'
            );
        }
        
        // Préparer les paramètres de la requête
        $params = array(
            'api_username' => $this->api_username,
            'api_password' => $this->api_password,
            'method' => 'sendSMS',
            'did' => $from,
            'dst' => $to,
            'message' => $message
        );
        
        // Effectuer la requête vers l'API
        $response = $this->api_request($params);
        
        // Vérifier si la requête a réussi
        if (isset($response['status']) && $response['status'] === 'success') {
            // Enregistrer le message dans la base de données
            $message_id = isset($response['sms']) ? $response['sms'] : uniqid();
            $user_id = get_current_user_id();
            
            $this->save_outgoing_message($user_id, $from, $to, $message, $message_id);
            
            return array(
                'status' => 'success',
                'message_id' => $message_id
            );
        } else {
            $error = isset($response['error']) ? $response['error'] : 'Erreur inconnue';
            
            return array(
                'status' => 'error',
                'error' => $error
            );
        }
    }
    
    /**
     * Récupérer les messages SMS pour un utilisateur.
     *
     * @param int $user_id ID de l'utilisateur.
     * @param string|null $contact Filtrer par contact/numéro.
     * @param int $limit Nombre maximum de messages à récupérer.
     * @param int $offset Décalage pour la pagination.
     * @return array Liste des messages.
     */
    public function get_user_messages($user_id, $contact = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'voipms_sms_messages';
        
        // Construire la requête SQL
        $sql = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        // Ajouter le filtre de contact si spécifié
        if ($contact) {
            $contact = $this->clean_phone_number($contact);
            $sql .= " AND (from_number = %s OR to_number = %s)";
            $params[] = $contact;
            $params[] = $contact;
        }
        
        // Ajouter l'ordre et la limite
        $sql .= " ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        // Exécuter la requête préparée
        $messages = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        
        return $messages ?: array();
    }
    
    /**
     * Enregistrer un message SMS entrant.
     *
     * @param string $from Numéro expéditeur.
     * @param string $to Numéro destinataire.
     * @param string $message Contenu du message.
     * @param string $message_id ID du message (optionnel).
     * @return bool Succès ou échec.
     */
    public function save_incoming_message($from, $to, $message, $message_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'voipms_sms_messages';
        
        // Déterminer l'utilisateur associé au numéro DID
        $user_id = $this->get_user_id_by_did($to);
        
        // Si aucun utilisateur trouvé, attribuer à l'admin
        if (!$user_id) {
            $admins = get_users(array('role' => 'administrator', 'number' => 1));
            $user_id = !empty($admins) ? $admins[0]->ID : 1;
        }
        
        $data = array(
            'user_id' => $user_id,
            'from_number' => $from,
            'to_number' => $to,
            'message' => $message,
            'direction' => 'incoming',
            'status' => 'received',
            'timestamp' => current_time('mysql'),
            'message_id' => $message_id
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        // Déclencher un hook pour permettre aux autres plugins de réagir
        if ($result) {
            do_action('wp_sms_voipms_incoming_message', $data);
        }
        
        return $result !== false;
    }
    
    /**
     * Récupérer l'ID utilisateur associé à un numéro DID.
     *
     * @param string $did Numéro DID.
     * @return int|null ID utilisateur ou null si non trouvé.
     */
    public function get_user_id_by_did($did) {
        $did = $this->clean_phone_number($did);
        
        // Récupérer tous les utilisateurs qui ont un méta avec le DID
        $users = get_users(array(
            'meta_key' => 'wp_sms_voipms_assigned_did',
            'meta_value' => $did
        ));
        
        return !empty($users) ? $users[0]->ID : null;
    }
    
    /**
     * Récupérer le numéro DID associé à un utilisateur.
     *
     * @param int $user_id ID utilisateur.
     * @return string Numéro DID ou numéro par défaut.
     */
    public function get_user_did($user_id) {
        $did = get_user_meta($user_id, 'wp_sms_voipms_assigned_did', true);
        
        // Si l'utilisateur n'a pas de DID assigné, utiliser le DID par défaut
        if (empty($did)) {
            $did = get_option('wp_sms_voipms_did');
        }
        
        return $did;
    }
    
    /**
     * Effectuer une requête vers l'API VoIP.ms.
     *
     * @param array $params Paramètres de la requête.
     * @return array Réponse de l'API.
     */
    private function api_request($params) {
        // Construire l'URL avec les paramètres
        $url = add_query_arg($params, $this->api_url);
        
        // Effectuer la requête HTTP
        $response = wp_remote_get($url, array(
            'timeout' => 15
        ));
        
        // Vérifier s'il y a eu une erreur
        if (is_wp_error($response)) {
            return array(
                'status' => 'error',
                'error' => $response->get_error_message()
            );
        }
        
        // Récupérer le corps de la réponse
        $body = wp_remote_retrieve_body($response);
        
        // Décoder la réponse JSON
        $result = json_decode($body, true);
        
        // Vérifier si le décodage a réussi
        if (!$result) {
            return array(
                'status' => 'error',
                'error' => 'Erreur de décodage de la réponse API'
            );
        }
        
        return $result;
    }
    
    /**
     * Enregistrer un message SMS sortant.
     *
     * @param int $user_id ID de l'utilisateur.
     * @param string $from Numéro expéditeur.
     * @param string $to Numéro destinataire.
     * @param string $message Contenu du message.
     * @param string $message_id ID du message.
     * @return bool Succès ou échec.
     */
    private function save_outgoing_message($user_id, $from, $to, $message, $message_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'voipms_sms_messages';
        
        $data = array(
            'user_id' => $user_id,
            'from_number' => $from,
            'to_number' => $to,
            'message' => $message,
            'direction' => 'outgoing',
            'status' => 'sent',
            'timestamp' => current_time('mysql'),
            'message_id' => $message_id
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        // Déclencher un hook pour permettre aux autres plugins de réagir
        if ($result) {
            do_action('wp_sms_voipms_outgoing_message', $data);
        }
        
        return $result !== false;
    }
    
    /**
     * Nettoyer un numéro de téléphone (supprimer les caractères non numériques).
     *
     * @param string $number Numéro à nettoyer.
     * @return string Numéro nettoyé.
     */
    private function clean_phone_number($number) {
        return preg_replace('/[^0-9]/', '', $number);
    }
    
    /**
     * Vérifier le solde du compte VoIP.ms.
     *
     * @return array Résultat avec le solde ou une erreur.
     */
    public function check_balance() {
        // Vérifier si les identifiants API sont configurés
        if (empty($this->api_username) || empty($this->api_password)) {
            return array(
                'status' => 'error',
                'error' => 'API VoIP.ms non configurée'
            );
        }
        
        // Préparer les paramètres de la requête
        $params = array(
            'api_username' => $this->api_username,
            'api_password' => $this->api_password,
            'method' => 'getBalance'
        );
        
        // Effectuer la requête vers l'API
        return $this->api_request($params);
    }
    
    /**
     * Récupérer l'historique des SMS via l'API VoIP.ms.
     *
     * @param string $did Numéro DID.
     * @param string $date_from Date de début (YYYY-MM-DD).
     * @param string $date_to Date de fin (YYYY-MM-DD).
     * @return array Liste des messages ou erreur.
     */
    public function get_sms_history($did, $date_from, $date_to) {
        // Vérifier si les identifiants API sont configurés
        if (empty($this->api_username) || empty($this->api_password)) {
            return array(
                'status' => 'error',
                'error' => 'API VoIP.ms non configurée'
            );
        }
        
        // Préparer les paramètres de la requête
        $params = array(
            'api_username' => $this->api_username,
            'api_password' => $this->api_password,
            'method' => 'getSMS',
            'did' => $did,
            'date_from' => $date_from,
            'date_to' => $date_to
        );
        
        // Effectuer la requête vers l'API
        return $this->api_request($params);
    }
}