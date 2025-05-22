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
$custom_logo_url = '';

// Utiliser le logo personnalisé s'il existe, sinon le logo par défaut encodé
if ($custom_logo_id) {
    $custom_logo_url = wp_get_attachment_url($custom_logo_id);
} elseif (defined('WP_SMS_VOIPMS_DEFAULT_LOGO')) {
    $custom_logo_url = WP_SMS_VOIPMS_DEFAULT_LOGO;
}

// Récupérer le numéro DID de l'utilisateur
$api = new Wp_Sms_Voipms_Api();
$user_did = $api->get_user_did(get_current_user_id());
?>

<style>
:root {
    --primary-color: <?php echo esc_attr($primary_color); ?>;
    --secondary-color: <?php echo esc_attr($secondary_color); ?>;
}
</style>

<div class="wp-sms-voipms-container">
    <!-- En-tête -->
    <div class="wp-sms-voipms-header">
        <?php if ($custom_logo_url) : ?>
            <div class="wp-sms-voipms-logo">
                <img src="<?php echo esc_url($custom_logo_url); ?>" alt="<?php echo esc_attr($platform_name); ?>" />
            </div>
        <?php endif; ?>
        <h2><?php echo esc_html($platform_name); ?></h2>
    </div>
    
    <!-- Conteneur principal -->
    <div class="wp-sms-voipms-wrapper">
        <!-- Liste des contacts/conversations -->
        <div class="wp-sms-voipms-contacts">
            <div class="wp-sms-voipms-search-bar">
                <input type="text" placeholder="<?php _e('Rechercher un contact...', 'wp-sms-voipms'); ?>" id="sms-contact-search" />
            </div>
            
            <div class="wp-sms-voipms-contacts-list" id="contacts-list">
                
            </div>
            
            <div class="wp-sms-voipms-new-conversation">
                <button id="new-conversation-btn"><?php _e('Nouvelle conversation', 'wp-sms-voipms'); ?></button>
            </div>
        </div>
        
        <!-- Zone de conversation -->
        <div class="wp-sms-voipms-conversation">
            <div class="wp-sms-voipms-conversation-header" id="conversation-header">
                <div class="wp-sms-voipms-contact-info">
                    <span class="contact-name"><?php _e('Sélectionnez un contact', 'wp-sms-voipms'); ?></span>
                    <span class="contact-number"></span>
                </div>
            </div>
            
            <div class="wp-sms-voipms-messages" id="messages-container">
                <div class="wp-sms-voipms-welcome-message">
                    <p><?php _e('Sélectionnez un contact pour voir la conversation ou commencez une nouvelle conversation.', 'wp-sms-voipms'); ?></p>
                </div>
            </div>
            
            <div class="wp-sms-voipms-message-form" id="message-form">
                <input type="hidden" id="current-contact" value="" />
                <textarea id="message-text" placeholder="<?php _e('Tapez votre message...', 'wp-sms-voipms'); ?>" disabled></textarea>
                <button id="send-message-btn" disabled><?php _e('Envoyer', 'wp-sms-voipms'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Modale pour nouvelle conversation -->
    <div class="wp-sms-voipms-modal" id="new-conversation-modal">
        <div class="wp-sms-voipms-modal-content">
            <span class="wp-sms-voipms-modal-close">&times;</span>
            <h3><?php _e('Nouvelle conversation', 'wp-sms-voipms'); ?></h3>
            
            <div class="wp-sms-voipms-modal-body">
                <div class="form-group">
                    <label for="new-contact-number"><?php _e('Numéro de téléphone', 'wp-sms-voipms'); ?></label>
                    <input type="text" id="new-contact-number" placeholder="<?php _e('Ex: 5141234567', 'wp-sms-voipms'); ?>" />
                </div>
                
                <div class="form-group">
                    <label for="new-contact-name"><?php _e('Nom du contact (optionnel)', 'wp-sms-voipms'); ?></label>
                    <input type="text" id="new-contact-name" placeholder="<?php _e('Ex: Jean Dupont', 'wp-sms-voipms'); ?>" />
                </div>
                
                <div class="form-actions">
                    <button id="start-conversation-btn"><?php _e('Démarrer la conversation', 'wp-sms-voipms'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>