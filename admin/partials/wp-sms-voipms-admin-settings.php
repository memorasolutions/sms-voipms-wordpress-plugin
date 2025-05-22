<?php
// Si accès direct, sortir
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Afficher les messages de notification
    settings_errors('wp_sms_voipms_messages');
    ?>
    
    <div class="nav-tab-wrapper">
        <a href="#api-settings" class="nav-tab nav-tab-active"><?php _e('Paramètres API', 'wp-sms-voipms'); ?></a>
        <a href="#customization" class="nav-tab"><?php _e('Personnalisation', 'wp-sms-voipms'); ?></a>
        <a href="#limits" class="nav-tab"><?php _e('Limites', 'wp-sms-voipms'); ?></a>
    </div>
    
    <div class="tab-content">
        <!-- Onglet Paramètres API -->
        <div id="api-settings" class="tab-pane active">
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_sms_voipms_api_group');
                ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Courriel du compte VoIP.ms', 'wp-sms-voipms'); ?></th>
                        <td>
                            <input type="text" name="wp_sms_voipms_api_username" value="<?php echo esc_attr(get_option('wp_sms_voipms_api_username')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Le courriel associé à votre compte VoIP.ms', 'wp-sms-voipms'); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Mot de passe API', 'wp-sms-voipms'); ?></th>
                        <td>
                            <input type="password" name="wp_sms_voipms_api_password" value="<?php echo esc_attr(get_option('wp_sms_voipms_api_password')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Votre mot de passe VoIP.ms', 'wp-sms-voipms'); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Numéro DID par défaut', 'wp-sms-voipms'); ?></th>
                        <td>
                            <input type="text" name="wp_sms_voipms_did" value="<?php echo esc_attr(get_option('wp_sms_voipms_did')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Numéro de téléphone à utiliser pour envoyer des SMS (format: 5141234567)', 'wp-sms-voipms'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" id="test-api-connection" class="button button-secondary">
                        <?php _e('Tester la connexion API', 'wp-sms-voipms'); ?>
                    </button>
                    <span id="api-test-result"></span>
                </p>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <!-- Onglet Personnalisation -->
        <div id="customization" class="tab-pane">
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_sms_voipms_customization_group');
                ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Nom de la plateforme', 'wp-sms-voipms'); ?></th>
                        <td>
                            <input type="text" name="wp_sms_voipms_platform_name" value="<?php echo esc_attr(get_option('wp_sms_voipms_platform_name', 'WP SMS VoIPms')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Nom affiché dans l\'interface', 'wp-sms-voipms'); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Couleur principale', 'wp-sms-voipms'); ?></th>
                        <td>
                            <input type="color" name="wp_sms_voipms_primary_color" value="<?php echo esc_attr(get_option('wp_sms_voipms_primary_color', '#007bff')); ?>" />
                            <p class="description"><?php _e('Couleur principale de l\'interface', 'wp-sms-voipms'); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Couleur secondaire', 'wp-sms-voipms'); ?></th>
                        <td>
                            <input type="color" name="wp_sms_voipms_secondary_color" value="<?php echo esc_attr(get_option('wp_sms_voipms_secondary_color', '#6c757d')); ?>" />
                            <p class="description"><?php _e('Couleur secondaire de l\'interface', 'wp-sms-voipms'); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Logo personnalisé', 'wp-sms-voipms'); ?></th>
                        <td>
                            <?php
                            $logo_id = get_option('wp_sms_voipms_custom_logo');
                            $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                            ?>
                            <div class="custom-logo-container">
                                <?php if ($logo_url) : ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" style="max-width: 200px; height: auto; margin-bottom: 10px;" />
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="wp_sms_voipms_custom_logo" id="custom_logo_id" value="<?php echo esc_attr($logo_id); ?>" />
                            <button type="button" class="button upload-logo-button">
                                <?php echo $logo_id ? __('Changer le logo', 'wp-sms-voipms') : __('Ajouter un logo', 'wp-sms-voipms'); ?>
                            </button>
                            <?php if ($logo_id) : ?>
                                <button type="button" class="button remove-logo-button">
                                    <?php _e('Supprimer le logo', 'wp-sms-voipms'); ?>
                                </button>
                            <?php endif; ?>
                            <p class="description"><?php _e('Logo affiché dans l\'interface SMS', 'wp-sms-voipms'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <!-- Onglet Limites -->
        <div id="limits" class="tab-pane">
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_sms_voipms_limits_group');
                ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Activer les limites de messages', 'wp-sms-voipms'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wp_sms_voipms_message_limit_enabled" value="1" <?php checked(get_option('wp_sms_voipms_message_limit_enabled'), true); ?> />
                                <?php _e('Limiter le nombre de messages par utilisateur', 'wp-sms-voipms'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr valign="top" class="limit-count-row" <?php echo !get_option('wp_sms_voipms_message_limit_enabled') ? 'style="display:none;"' : ''; ?>>
                        <th scope="row"><?php _e('Nombre maximum de messages', 'wp-sms-voipms'); ?></th>
                        <td>
                            <input type="number" name="wp_sms_voipms_message_limit_count" value="<?php echo esc_attr(get_option('wp_sms_voipms_message_limit_count', 100)); ?>" min="1" class="small-text" />
                            <p class="description"><?php _e('Nombre maximum de messages qu\'un utilisateur peut envoyer', 'wp-sms-voipms'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top" class="limit-period-row" <?php echo !get_option('wp_sms_voipms_message_limit_enabled') ? 'style="display:none;"' : ''; ?>>
                        <th scope="row"><?php _e('Période de la limite', 'wp-sms-voipms'); ?></th>
                        <td>
                            <?php $current_period = get_option('wp_sms_voipms_message_limit_period', 'day'); ?>
                            <input type="number" name="wp_sms_voipms_message_limit_period_value" value="<?php echo esc_attr(get_option('wp_sms_voipms_message_limit_period_value', 1)); ?>" min="1" class="small-text" style="margin-right:5px;" />
                            <select name="wp_sms_voipms_message_limit_period">
                                <option value="day" <?php selected($current_period, 'day'); ?>><?php _e('Jour', 'wp-sms-voipms'); ?></option>
                                <option value="week" <?php selected($current_period, 'week'); ?>><?php _e('Semaine', 'wp-sms-voipms'); ?></option>
                                <option value="month" <?php selected($current_period, 'month'); ?>><?php _e('Mois', 'wp-sms-voipms'); ?></option>
                            </select>
                            <p class="description"><?php _e('Période durant laquelle le nombre maximum est comptabilisé', 'wp-sms-voipms'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Gestion des onglets
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Activer l'onglet
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Afficher le contenu de l'onglet
        $('.tab-pane').removeClass('active');
        $($(this).attr('href')).addClass('active');
    });
    
    // Gestion de la case à cocher des limites
    $('input[name="wp_sms_voipms_message_limit_enabled"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.limit-count-row, .limit-period-row').show();
        } else {
            $('.limit-count-row, .limit-period-row').hide();
        }
    });
    
    // Test de connexion API
    $('#test-api-connection').on('click', function() {
        var $button = $(this);
        var $result = $('#api-test-result');
        
        $button.prop('disabled', true);
        $result.html('<span style="color:#666;"><?php _e('Test en cours...', 'wp-sms-voipms'); ?></span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_sms_voipms_test_api',
                nonce: '<?php echo wp_create_nonce('wp_sms_voipms_test_api'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<span style="color:green;">' + response.data.message + '</span>');
                } else {
                    $result.html('<span style="color:red;">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $result.html('<span style="color:red;"><?php _e('Erreur de communication avec le serveur.', 'wp-sms-voipms'); ?></span>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
    
    // Les actions de téléchargement et de suppression du logo sont gérées dans
    // le script principal admin/js/wp-sms-voipms-admin.js
});
</script>

<script type="text/javascript">
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
