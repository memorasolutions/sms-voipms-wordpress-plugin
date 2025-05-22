<?php
// Si accès direct, sortir
if (!defined('WPINC')) {
    die;
}

// Récupérer les options de personnalisation
$platform_name = get_option('wp_sms_voipms_platform_name', 'WP SMS VoIPms');
$custom_logo_id = get_option('wp_sms_voipms_custom_logo');
$custom_logo_url = $custom_logo_id ? wp_get_attachment_url($custom_logo_id) : '';
?>

<div class="wrap">
    <h1>
        <?php if ($custom_logo_url) : ?>
            <img src="<?php echo esc_url($custom_logo_url); ?>" alt="<?php echo esc_attr($platform_name); ?>" style="max-height: 50px; vertical-align: middle; margin-right: 10px;" />
        <?php endif; ?>
        <?php echo esc_html($platform_name); ?> - <?php _e('Contacts', 'wp-sms-voipms'); ?>
    </h1>
    
    <div class="add-contact-button">
        <button id="add-contact-btn" class="button button-primary">
            <span class="dashicons dashicons-plus-alt"></span> <?php _e('Ajouter un contact', 'wp-sms-voipms'); ?>
        </button>
    </div>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <input type="search" id="contact-search-input" placeholder="<?php _e('Rechercher...', 'wp-sms-voipms'); ?>" style="margin-right: 6px;" />
            <button type="button" id="search-contacts-btn" class="button">
                <?php _e('Rechercher', 'wp-sms-voipms'); ?>
            </button>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped contacts-table">
        <thead>
            <tr>
                <th scope="col"><?php _e('Nom', 'wp-sms-voipms'); ?></th>
                <th scope="col"><?php _e('Numéro', 'wp-sms-voipms'); ?></th>
                <th scope="col"><?php _e('Email', 'wp-sms-voipms'); ?></th>
                <th scope="col"><?php _e('Dernier message', 'wp-sms-voipms'); ?></th>
                <th scope="col"><?php _e('Actions', 'wp-sms-voipms'); ?></th>
            </tr>
        </thead>
        <tbody id="contacts-table-body">
            <tr>
                <td colspan="5"><div class="loader"></div></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Modale pour ajouter/modifier un contact -->
<div id="contact-form-modal" class="wp-sms-voipms-modal">
    <div class="wp-sms-modal-content">
        <span class="modal-close">&times;</span>
        <h3 id="contact-modal-title"><?php _e('Ajouter un contact', 'wp-sms-voipms'); ?></h3>
        
        <form id="contact-form">
            <input type="hidden" id="contact-id" value="" />
            
            <div class="form-group" style="margin-top: 20px;">
                <label for="contact-name"><?php _e('Nom', 'wp-sms-voipms'); ?></label>
                <input type="text" id="contact-name" required style="width: 100%; margin-top: 5px;" />
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label for="contact-phone"><?php _e('Numéro de téléphone', 'wp-sms-voipms'); ?></label>
                <input type="text" id="contact-phone" required style="width: 100%; margin-top: 5px;" />
                <p class="description"><?php _e('Format: 10 chiffres, sans espaces ni tirets', 'wp-sms-voipms'); ?></p>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label for="contact-email"><?php _e('Email (optionnel)', 'wp-sms-voipms'); ?></label>
                <input type="email" id="contact-email" style="width: 100%; margin-top: 5px;" />
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label for="contact-notes"><?php _e('Notes (optionnel)', 'wp-sms-voipms'); ?></label>
                <textarea id="contact-notes" style="width: 100%; margin-top: 5px; min-height: 100px;"></textarea>
            </div>
            
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" id="cancel-contact-btn" class="button">
                    <?php _e('Annuler', 'wp-sms-voipms'); ?>
                </button>
                <button type="submit" id="save-contact-btn" class="button button-primary">
                    <?php _e('Enregistrer', 'wp-sms-voipms'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modale de confirmation de suppression -->
<div id="delete-confirm-modal" class="wp-sms-voipms-modal">
    <div class="wp-sms-modal-content" style="max-width: 400px;">
        <span class="modal-close">&times;</span>
        <h3><?php _e('Confirmer la suppression', 'wp-sms-voipms'); ?></h3>
        
        <p><?php _e('Êtes-vous sûr de vouloir supprimer ce contact ? Cette action ne peut pas être annulée.', 'wp-sms-voipms'); ?></p>
        
        <input type="hidden" id="delete-contact-id" value="" />
        
        <div style="margin-top: 20px; text-align: right;">
            <button type="button" id="cancel-delete-btn" class="button">
                <?php _e('Annuler', 'wp-sms-voipms'); ?>
            </button>
            <button type="button" id="confirm-delete-btn" class="button button-danger" style="background-color: #dc3545; color: white; border-color: #dc3545;">
                <?php _e('Supprimer', 'wp-sms-voipms'); ?>
            </button>
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
    error_loading_messages: '<?php _e('Erreur lors du chargement des messages.', 'wp-sms-voipms'); ?>'
};
</script>

<script type="text/javascript">
jQuery(document).ready(function($) {
    function escapeHtml(str) {
        return $('<div>').text(str).html();
    }
    // Recherche de contacts
    $('#search-contacts-btn').on('click', function() {
        var searchTerm = $('#contact-search-input').val().trim();
        loadContacts(searchTerm);
    });
    
    $('#contact-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#search-contacts-btn').trigger('click');
        }
    });
    
    // Ajouter un contact
    $(document).on('click', '#add-contact-btn', function() {
        // Réinitialiser le formulaire
        $('#contact-form')[0].reset();
        $('#contact-id').val('');
        $('#contact-modal-title').text('<?php _e('Ajouter un contact', 'wp-sms-voipms'); ?>');
        
        // Afficher la modale
        $('#contact-form-modal').show();
    });
    
    // Fermer les modales
    $(document).on('click', '.modal-close', function() {
        $(this).closest('.wp-sms-voipms-modal').hide();
    });
    
    // Cliquer en dehors pour fermer
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('wp-sms-voipms-modal')) {
            $('.wp-sms-voipms-modal').hide();
        }
    });
    
    // Annuler le formulaire
    $('#cancel-contact-btn').on('click', function() {
        $('#contact-form-modal').hide();
    });
    
    // Annuler la suppression
    $('#cancel-delete-btn').on('click', function() {
        $('#delete-confirm-modal').hide();
    });
    
    // Enregistrer un contact
    $('#contact-form').on('submit', function(e) {
        e.preventDefault();
        
        var contactId = $('#contact-id').val();
        var name = $('#contact-name').val().trim();
        var phone = $('#contact-phone').val().trim().replace(/[^0-9]/g, '');
        var email = $('#contact-email').val().trim();
        var notes = $('#contact-notes').val().trim();
        
        if (!name || !phone) {
            alert('<?php _e('Le nom et le numéro sont obligatoires.', 'wp-sms-voipms'); ?>');
            return;
        }
        
        // Vérifier le format du numéro de téléphone
        if (phone.length < 10) {
            alert('<?php _e('Le numéro de téléphone doit contenir au moins 10 chiffres.', 'wp-sms-voipms'); ?>');
            return;
        }
        
        var method = contactId ? 'PUT' : 'POST';
        var url = wp_sms_voipms.rest_url + 'contacts';
        
        if (contactId) {
            url += '/' + contactId;
        }
        
        // Enregistrer le contact
        $.ajax({
            url: url,
            method: method,
            data: JSON.stringify({
                name: name,
                phone_number: phone,
                email: email,
                notes: notes
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
            },
            success: function(response) {
                // Fermer la modale
                $('#contact-form-modal').hide();
                
                // Recharger les contacts
                loadContacts();
            },
            error: function(xhr) {
                var errorMsg = '';

                if (xhr.status === 403) {
                    errorMsg = '<?php _e('Vous n\'avez pas la permission d\'ajouter des contacts.', 'wp-sms-voipms'); ?>';
                } else {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMsg = response.message || '<?php _e('Erreur lors de l\'enregistrement du contact.', 'wp-sms-voipms'); ?>';
                    } catch (e) {
                        errorMsg = '<?php _e('Erreur lors de l\'enregistrement du contact.', 'wp-sms-voipms'); ?>';
                    }
                }

                alert(errorMsg);
            }
        });
    });
    
    // Confirmer la suppression
    $('#confirm-delete-btn').on('click', function() {
        var contactId = $('#delete-contact-id').val();
        
        if (!contactId) return;
        
        // Supprimer le contact
        $.ajax({
            url: wp_sms_voipms.rest_url + 'contacts/' + contactId,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
            },
            success: function(response) {
                // Fermer la modale
                $('#delete-confirm-modal').hide();
                
                // Recharger les contacts
                loadContacts();
            },
            error: function(xhr) {
                var errorMsg = '';
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || '<?php _e('Erreur lors de la suppression du contact.', 'wp-sms-voipms'); ?>';
                } catch (e) {
                    errorMsg = '<?php _e('Erreur lors de la suppression du contact.', 'wp-sms-voipms'); ?>';
                }
                
                alert(errorMsg);
                $('#delete-confirm-modal').hide();
            }
        });
    });
    
    // Charger les contacts
    function loadContacts(searchTerm) {
        var url = wp_sms_voipms.rest_url + 'contacts';
        
        if (searchTerm) {
            url += '?search=' + encodeURIComponent(searchTerm);
        }
        
        $('#contacts-table-body').html('<tr><td colspan="5"><div class="loader"></div></td></tr>');
        
        $.ajax({
            url: url,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
            },
            success: function(response) {
                displayContacts(response);
            },
            error: function(xhr) {
                $('#contacts-table-body').html('<tr><td colspan="5"><?php _e('Erreur lors du chargement des contacts.', 'wp-sms-voipms'); ?></td></tr>');
            }
        });
    }
    
    // Afficher les contacts
    function displayContacts(contacts) {
        if (contacts.length === 0) {
            $('#contacts-table-body').html('<tr><td colspan="5"><?php _e('Aucun contact trouvé.', 'wp-sms-voipms'); ?></td></tr>');
            return;
        }
        
        var html = '';
        
        for (var i = 0; i < contacts.length; i++) {
            var contact = contacts[i];
            var lastMessage = contact.last_message ? formatTimestamp(contact.last_message) : '<?php _e('Aucun', 'wp-sms-voipms'); ?>';
            
            html += '<tr>';
            html += '<td>' + escapeHtml(contact.name) + '</td>';
            html += '<td>' + formatPhoneNumber(contact.phone_number) + '</td>';
            html += '<td>' + (contact.email || '-') + '</td>';
            html += '<td>' + lastMessage + '</td>';
            html += '<td>';
            html += '<button type="button" class="button button-small edit-contact" data-id="' + contact.id + '">';
            html += '<span class="dashicons dashicons-edit"></span> <?php _e('Modifier', 'wp-sms-voipms'); ?>';
            html += '</button> ';
            html += '<button type="button" class="button button-small button-link-delete delete-contact" data-id="' + contact.id + '">';
            html += '<span class="dashicons dashicons-trash"></span> <?php _e('Supprimer', 'wp-sms-voipms'); ?>';
            html += '</button> ';
            html += '<a href="admin.php?page=wp-sms-voipms&contact=' + contact.phone_number + '" class="button button-small">';
            html += '<span class="dashicons dashicons-email"></span> <?php _e('Messages', 'wp-sms-voipms'); ?>';
            html += '</a>';
            html += '</td>';
            html += '</tr>';
        }
        
        $('#contacts-table-body').html(html);
        
        // Ajouter les gestionnaires d'événements
        $('.edit-contact').on('click', function() {
            var contactId = $(this).data('id');
            editContact(contactId);
        });
        
        $('.delete-contact').on('click', function() {
            var contactId = $(this).data('id');
            $('#delete-contact-id').val(contactId);
            $('#delete-confirm-modal').show();
        });
    }
    
    // Modifier un contact
    function editContact(contactId) {
        $.ajax({
            url: wp_sms_voipms.rest_url + 'contacts/' + contactId,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
            },
            success: function(response) {
                // Remplir le formulaire
                $('#contact-id').val(response.id);
                $('#contact-name').val(response.name);
                $('#contact-phone').val(response.phone_number);
                $('#contact-email').val(response.email || '');
                $('#contact-notes').val(response.notes || '');
                
                // Changer le titre
                $('#contact-modal-title').text('<?php _e('Modifier le contact', 'wp-sms-voipms'); ?>');
                
                // Afficher la modale
                $('#contact-form-modal').show();
            },
            error: function(xhr) {
                alert('<?php _e('Erreur lors du chargement du contact.', 'wp-sms-voipms'); ?>');
            }
        });
    }
    
    // Formater un numéro de téléphone
    function formatPhoneNumber(phoneNumber) {
        if (!phoneNumber) return '';
        
        // Nettoyer le numéro
        phoneNumber = phoneNumber.replace(/[^0-9]/g, '');
        
        // Format américain/canadien (XXX) XXX-XXXX pour 10 chiffres
        if (phoneNumber.length === 10) {
            return '(' + phoneNumber.substring(0, 3) + ') ' + 
                   phoneNumber.substring(3, 6) + '-' + 
                   phoneNumber.substring(6, 10);
        }
        
        // Format américain/canadien +1 (XXX) XXX-XXXX pour 11 chiffres commençant par 1
        if (phoneNumber.length === 11 && phoneNumber.charAt(0) === '1') {
            return '+1 (' + phoneNumber.substring(1, 4) + ') ' + 
                   phoneNumber.substring(4, 7) + '-' + 
                   phoneNumber.substring(7, 11);
        }
        
        // Format par défaut pour les autres longueurs
        return phoneNumber;
    }
    
    // Formater un timestamp
    function formatTimestamp(timestamp) {
        var date = new Date(timestamp);
        var now = new Date();
        var yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        
        var options = { hour: '2-digit', minute: '2-digit' };
        var timeString = date.toLocaleTimeString(undefined, options);
        
        // Si c'est aujourd'hui
        if (date.toDateString() === now.toDateString()) {
            return '<?php _e('Aujourd\'hui', 'wp-sms-voipms'); ?>, ' + timeString;
        }
        
        // Si c'est hier
        if (date.toDateString() === yesterday.toDateString()) {
            return '<?php _e('Hier', 'wp-sms-voipms'); ?>, ' + timeString;
        }
        
        // Sinon, afficher la date complète
        return date.toLocaleDateString() + ', ' + timeString;
    }
});
</script>