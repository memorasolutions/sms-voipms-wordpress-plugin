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

        // Routes pour gérer les contacts
        register_rest_route($this->namespace, '/contacts', array(
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'get_contacts'),
                'permission_callback' => array($this, 'read_messages_permissions_check'),
            ),
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_contact'),
                'permission_callback' => array($this, 'send_sms_permissions_check'),
            ),
        ));

        register_rest_route($this->namespace, '/contacts/(?P<id>\d+)', array(
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'get_contact'),
                'permission_callback' => array($this, 'read_messages_permissions_check'),
            ),
            array(
                'methods'  => 'PUT',
                'callback' => array($this, 'update_contact'),
                'permission_callback' => array($this, 'send_sms_permissions_check'),
            ),
            array(
                'methods'  => 'DELETE',
                'callback' => array($this, 'delete_contact'),
                'permission_callback' => array($this, 'send_sms_permissions_check'),
            ),
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
            $limit  = get_option('wp_sms_voipms_message_limit_count', 100);
            $period = get_option('wp_sms_voipms_message_limit_period', 'day');
            $period_value = get_option('wp_sms_voipms_message_limit_period_value', 1);

            // Récupérer le nombre de messages envoyés par cet utilisateur pour la période
            $sent_count = $this->get_user_sent_count($user_id, $period, $period_value);
            
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
    private function get_user_sent_count($user_id, $period = 'day', $value = 1) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'voipms_sms_messages';

        $periods = array(
            'day'   => 'DAY',
            'week'  => 'WEEK',
            'month' => 'MONTH'
        );

        $unit = isset($periods[$period]) ? $periods[$period] : 'DAY';
        $value = absint($value) > 0 ? absint($value) : 1;
        $interval = $value . ' ' . $unit;

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND direction = 'outgoing' AND timestamp >= DATE_SUB(NOW(), INTERVAL $interval)",
            $user_id
        );

        $count = $wpdb->get_var($query);

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

    /**
     * Récupérer la liste des contacts de l'utilisateur courant.
     */
    public function get_contacts($request) {
        global $wpdb;

        $contacts_table  = $wpdb->prefix . 'voipms_sms_contacts';
        $messages_table  = $wpdb->prefix . 'voipms_sms_messages';
        $user_id         = get_current_user_id();

        $search   = $request->get_param('search');
        $where    = array('c.user_id = %d');
        $params   = array($user_id);

        if (!empty($search)) {
            $like      = '%' . $wpdb->esc_like($search) . '%';
            $where[]   = '(c.name LIKE %s OR c.phone_number LIKE %s)';
            $params[]  = $like;
            $params[]  = $like;
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT c.id, c.phone_number, c.name, c.email, c.notes, c.created_at, c.updated_at, " .
               "MAX(m.timestamp) AS last_message " .
               "FROM $contacts_table AS c " .
               "LEFT JOIN $messages_table AS m ON m.user_id = c.user_id AND (m.from_number = c.phone_number OR m.to_number = c.phone_number) " .
               "$where_sql GROUP BY c.id, c.phone_number, c.name, c.email, c.notes, c.created_at, c.updated_at ORDER BY c.name";

        $query    = $wpdb->prepare($sql, $params);
        $contacts = $wpdb->get_results($query, ARRAY_A);

        return new WP_REST_Response($contacts, 200);
    }

    /**
     * Récupérer un contact spécifique.
     */
    public function get_contact($request) {
        global $wpdb;

        $id = (int) $request['id'];
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'voipms_sms_contacts';

        $contact = $wpdb->get_row(
            $wpdb->prepare("SELECT id, phone_number, name, email, notes, created_at, updated_at FROM $table WHERE id = %d AND user_id = %d", $id, $user_id),
            ARRAY_A
        );

        if (!$contact) {
            return new WP_Error('not_found', __('Contact introuvable.', 'wp-sms-voipms'), array('status' => 404));
        }

        return new WP_REST_Response($contact, 200);
    }

    /**
     * Créer un contact.
     */
    public function create_contact($request) {
        global $wpdb;

        $user_id = get_current_user_id();
        $phone = preg_replace('/[^0-9]/', '', $request->get_param('phone_number'));
        $name  = sanitize_text_field($request->get_param('name'));
        $email = sanitize_email($request->get_param('email'));
        $notes = sanitize_textarea_field($request->get_param('notes'));

        if (empty($phone) || empty($name)) {
            return new WP_Error('missing_parameters', __('Numéro et nom requis.', 'wp-sms-voipms'), array('status' => 400));
        }

        if (strlen($phone) < 10) {
            return new WP_Error('invalid_phone', __('Numéro de téléphone invalide.', 'wp-sms-voipms'), array('status' => 400));
        }

        $table = $wpdb->prefix . 'voipms_sms_contacts';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE phone_number = %s AND user_id = %d", $phone, $user_id));
        if ($exists) {
            return new WP_Error('contact_exists', __('Ce contact existe déjà.', 'wp-sms-voipms'), array('status' => 409));
        }
        $result = $wpdb->insert(
            $table,
            array(
                'user_id'      => $user_id,
                'phone_number'  => $phone,
                'name'         => $name,
                'email'        => $email,
                'notes'        => $notes,
                'created_at'   => current_time('mysql'),
                'updated_at'   => current_time('mysql'),
            )
        );

        if (!$result) {
            return new WP_Error('db_error', __('Erreur lors de la création du contact.', 'wp-sms-voipms'), array('status' => 500));
        }

        $request['id'] = $wpdb->insert_id;
        return $this->get_contact($request);
    }

    /**
     * Mettre à jour un contact.
     */
    public function update_contact($request) {
        global $wpdb;

        $id      = (int) $request['id'];
        $user_id = get_current_user_id();
        $table   = $wpdb->prefix . 'voipms_sms_contacts';

        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE id = %d AND user_id = %d", $id, $user_id));
        if (!$exists) {
            return new WP_Error('not_found', __('Contact introuvable.', 'wp-sms-voipms'), array('status' => 404));
        }

        $data = array();
        if (null !== $request->get_param('phone_number')) {
            $data['phone_number'] = preg_replace('/[^0-9]/', '', $request->get_param('phone_number'));
        }
        if (null !== $request->get_param('name')) {
            $data['name'] = sanitize_text_field($request->get_param('name'));
        }
        if (null !== $request->get_param('email')) {
            $data['email'] = sanitize_email($request->get_param('email'));
        }
        if (null !== $request->get_param('notes')) {
            $data['notes'] = sanitize_textarea_field($request->get_param('notes'));
        }

        if (empty($data)) {
            return new WP_Error('nothing_to_update', __('Aucune donnée à mettre à jour.', 'wp-sms-voipms'), array('status' => 400));
        }

        $data['updated_at'] = current_time('mysql');

        $updated = $wpdb->update($table, $data, array('id' => $id, 'user_id' => $user_id));
        if (false === $updated) {
            return new WP_Error('db_error', __('Erreur lors de la mise à jour du contact.', 'wp-sms-voipms'), array('status' => 500));
        }

        return $this->get_contact($request);
    }

    /**
     * Supprimer un contact.
     */
    public function delete_contact($request) {
        global $wpdb;

        $id      = (int) $request['id'];
        $user_id = get_current_user_id();
        $table   = $wpdb->prefix . 'voipms_sms_contacts';

        $deleted = $wpdb->delete($table, array('id' => $id, 'user_id' => $user_id));
        if (!$deleted) {
            return new WP_Error('not_found', __('Contact introuvable.', 'wp-sms-voipms'), array('status' => 404));
        }

        return new WP_REST_Response(true, 200);
    }
}
