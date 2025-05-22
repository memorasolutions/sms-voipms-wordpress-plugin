<?php
// Si accès direct, sortir
if (!defined('WPINC')) {
    die;
}

// Récupérer les options de personnalisation
$platform_name = get_option('wp_sms_voipms_platform_name', 'WP SMS VoIPms');
$primary_color = get_option('wp_sms_voipms_primary_color', '#007bff');
$secondary_color = get_option('wp_sms_voipms_secondary_color', '#6c757d');
$custom_logo_id = get_option('wp_sms_voipms_custom_logo');
$custom_logo_url = $custom_logo_id ? wp_get_attachment_url($custom_logo_id) : '';

// Récupérer le DID de l'utilisateur
$api = new Wp_Sms_Voipms_Api();
$user_did = $api->get_user_did(get_current_user_id());
?>

<div class="wrap">
    <h1>
        <?php if ($custom_logo_url) : ?>
            <img src="<?php echo esc_url($custom_logo_url); ?>" alt="<?php echo esc_attr($platform_name); ?>" style="max-height: 50px; vertical-align: middle; margin-right: 10px;" />
        <?php endif; ?>
        <?php echo esc_html($platform_name); ?> - <?php _e('Interface SMS', 'wp-sms-voipms'); ?>
    </h1>
    
    <div class="sms-interface-container">
        <!-- Sidebar des contacts -->
        <div class="contacts-sidebar">
            <div class="contact-search">
                <input type="text" id="contact-search" placeholder="<?php _e('Rechercher un contact...', 'wp-sms-voipms'); ?>" />
            </div>
            
            <div id="contacts-list">
                <div class="loader"></div>
            </div>
            
            <div style="padding: 10px;">
                <button id="new-conversation-btn" class="button button-primary" style="width: 100%;">
                    <?php _e('Nouvelle conversation', 'wp-sms-voipms'); ?>
                </button>
            </div>
        </div>
        
        <!-- Zone de conversation -->
        <div class="conversation-area">
            <div class="conversation-header" id="conversation-header">
                <div class="contact-info">
                    <span class="contact-name"><?php _e('Sélectionnez un contact', 'wp-sms-voipms'); ?></span>
                    <span class="contact-number"></span>
                </div>
            </div>
            
            <div class="messages-container" id="messages-container">
                <div style="text-align: center; padding: 20px; color: #666;">
                    <?php _e('Sélectionnez un contact pour voir la conversation ou commencez une nouvelle conversation.', 'wp-sms-voipms'); ?>
                </div>
            </div>
            
            <div class="message-form" id="message-form">
                <input type="hidden" id="current-contact" value="" />
                <textarea id="message-text" placeholder="<?php _e('Tapez votre message...', 'wp-sms-voipms'); ?>" disabled></textarea>
                <button id="send-message-btn" class="button button-primary" disabled>
                    <?php _e('Envoyer', 'wp-sms-voipms'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modale pour nouvelle conversation -->
<div id="new-conversation-modal" class="wp-sms-voipms-modal">
    <div class="wp-sms-modal-content">
        <span class="modal-close">&times;</span>
        <h3><?php _e('Nouvelle conversation', 'wp-sms-voipms'); ?></h3>
        
        <div style="margin-top: 20px;">
            <div>
                <label for="new-contact-number"><?php _e('Numéro de téléphone', 'wp-sms-voipms'); ?></label>
                <input type="text" id="new-contact-number" placeholder="<?php _e('Ex: 5141234567', 'wp-sms-voipms'); ?>" style="width: 100%; margin-top: 5px;" />
                <p class="description"><?php _e('Format: 10 chiffres, sans espaces ni tirets', 'wp-sms-voipms'); ?></p>
            </div>
            
            <div style="margin-top: 15px;">
                <label for="new-contact-name"><?php _e('Nom du contact (optionnel)', 'wp-sms-voipms'); ?></label>
                <input type="text" id="new-contact-name" placeholder="<?php _e('Ex: Jean Dupont', 'wp-sms-voipms'); ?>" style="width: 100%; margin-top: 5px;" />
            </div>
            
            <div style="margin-top: 20px; text-align: right;">
                <button id="start-conversation-btn" class="button button-primary">
                    <?php _e('Démarrer la conversation', 'wp-sms-voipms'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
/* Localization for JS */
  var wp_sms_voipms_i18n = {
    select_logo: '<?php _e('Sélectionner un logo', 'wp-sms-voipms'); ?>',
    use_this_logo: '<?php _e('Utiliser ce logo', 'wp-sms-voipms'); ?>',
    change_logo: '<?php _e('Changer le logo', 'wp-sms-voipms'); ?>',
    add_logo: '<?php _e('Ajouter un logo', 'wp-sms-voipms'); ?>',
    remove_logo: '<?php _e('Supprimer le logo', 'wp-sms-voipms'); ?>',
    enter_phone_number: '<?php _e('Veuillez entrer un numéro de téléphone.', 'wp-sms-voipms'); ?>',
    loading_messages: '<?php _e('Chargement des messages...', 'wp-sms-voipms'); ?>',
    error_loading_contacts: '<?php _e('Erreur lors du chargement des contacts.', 'wp-sms-voipms'); ?>',
    error_loading_messages: '<?php _e('Erreur lors du chargement des messages.', 'wp-sms-voipms'); ?>',
    no_contacts: '<?php _e('Aucun contact trouvé. Cliquez sur "Nouvelle conversation" pour commencer.', 'wp-sms-voipms'); ?>',
    no_messages: '<?php _e('Aucun message dans cette conversation. Envoyez un message pour commencer.', 'wp-sms-voipms'); ?>',
    sending: '<?php _e('Envoi...', 'wp-sms-voipms'); ?>',
    error_sending: '<?php _e('Erreur lors de l\'envoi du message.', 'wp-sms-voipms'); ?>',
    send: '<?php _e('Envoyer', 'wp-sms-voipms'); ?>'
  };
</script>

<div class="wp-sms-voipms-footer">Créé avec ❤️ par <a href="https://memora.solutions" target="_blank">MEMORA solutions</a></div>
