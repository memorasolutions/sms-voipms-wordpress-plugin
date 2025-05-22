/**
 * Script d'administration pour WP SMS VoIPms
 */
(function($) {
    'use strict';

    function escapeHtml(str) {
        return $('<div>').text(str).html();
    }

    // Variables globales
    var currentContact = '';
    var messagesContainer;
    var contactsList;
    var messageForm;
    var messageText;
    var sendButton;
    var contactSearchInput;
    var contactsData = [];
    var isLoadingMessages = false;
    var isLoadingContacts = false;
    var messagesOffset = 0;
    var messagesLimit = 50;
    
    /**
     * Initialisation à la fin du chargement du DOM
     */
    $(document).ready(function() {
        // Récupérer les éléments de l'interface après le chargement du DOM
        messagesContainer = $('#messages-container');
        contactsList = $('#contacts-list');
        messageForm = $('#message-form');
        messageText = $('#message-text');
        sendButton = $('#send-message-btn');
        contactSearchInput = $('#contact-search');

        // Initialiser l'interface
        initializeTabs();
        initializeContactsList();
        initializeMessageForm();
        initializeModals();
        initializeMediaUploader();
        
        // Vérifier le hash URL pour activer l'onglet approprié
        checkUrlHash();
        
        // Rafraîchir la liste des contacts uniquement sur demande
        // setInterval(refreshContactsList, 30000);
    });
    
    /**
     * Initialiser le système d'onglets
     */
    function initializeTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Mettre à jour le hash dans l'URL
            window.location.hash = $(this).attr('href');
            
            // Activer l'onglet
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Afficher le contenu de l'onglet
            $('.tab-pane').removeClass('active');
            $($(this).attr('href')).addClass('active');
        });
    }
    
    /**
     * Vérifier le hash dans l'URL pour activer l'onglet approprié
     */
    function checkUrlHash() {
        var hash = window.location.hash;
        
        if (hash) {
            $('.nav-tab[href="' + hash + '"]').trigger('click');
        }
    }
    
    /**
     * Initialiser la liste des contacts
     */
    function initializeContactsList() {
        // Si nous sommes sur la page d'interface SMS
        if (contactsList.length > 0) {

            // Recherche de contacts
            contactSearchInput.on('input', function() {
                filterContacts($(this).val().toLowerCase());
            });
        }
    }
    
    /**
     * Initialiser le formulaire d'envoi de message
     */
    function initializeMessageForm() {
        if (messageForm.length > 0) {
            // Envoyer un message
            sendButton.on('click', function() {
                sendMessage();
            });
            
            // Envoyer avec Entrée (mais Shift+Entrée pour nouvelle ligne)
            messageText.on('keydown', function(e) {
                if (e.keyCode === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
    }
    
    /**
     * Initialiser les modales
     */
    function initializeModals() {
        // Ouvrir la modale de nouvelle conversation
        $('#new-conversation-btn').on('click', function() {
            $('#new-conversation-modal').show();
        });
        
        // Fermer les modales (icône "X")
        $(document).on('click', '.modal-close, .wp-sms-voipms-modal-close', function() {
            $(this).closest('.wp-sms-voipms-modal').hide();
        });
        
        // Cliquer en dehors pour fermer
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('wp-sms-voipms-modal')) {
                $('.wp-sms-voipms-modal').hide();
            }
        });
        
        // Démarrer une nouvelle conversation
        $('#start-conversation-btn').on('click', function() {
            var phoneNumber = $('#new-contact-number').val().trim();
            var contactName = $('#new-contact-name').val().trim() || phoneNumber;
            
            if (phoneNumber) {
                // Nettoyer le numéro de téléphone
                phoneNumber = phoneNumber.replace(/[^0-9]/g, '');
                
                // Vérifier si le contact existe déjà
                var contactExists = false;
                for (var i = 0; i < contactsData.length; i++) {
                    if (contactsData[i].phone_number === phoneNumber) {
                        contactExists = true;
                        break;
                    }
                }
                
                // Si le contact n'existe pas, l'ajouter
                if (!contactExists) {
                    saveContact(phoneNumber, contactName);
                }
                
                // Sélectionner la conversation
                selectConversation(phoneNumber, contactName);
                
                // Fermer la modale
                $('#new-conversation-modal').hide();
                
                // Réinitialiser les champs
                $('#new-contact-number').val('');
                $('#new-contact-name').val('');
            } else {
                alert(wp_sms_voipms_i18n.enter_phone_number);
            }
        });
    }
    
    /**
     * Initialiser l'outil de sélection de médias pour le logo
     */
    function initializeMediaUploader() {
        // Télécharger un logo
        $('.upload-logo-button').on('click', function(e) {
            e.preventDefault();
            
            var $logoContainer = $('.custom-logo-container');
            var $logoIdInput = $('#custom_logo_id');
            
            var mediaUploader = wp.media({
                title: wp_sms_voipms_i18n.select_logo,
                button: {
                    text: wp_sms_voipms_i18n.use_this_logo
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                $logoContainer.html('<img src="' + attachment.url + '" alt="Logo" style="max-width: 200px; height: auto; margin-bottom: 10px;" />');
                $logoIdInput.val(attachment.id);
                
                $('.upload-logo-button').text(wp_sms_voipms_i18n.change_logo);
                
                if ($('.remove-logo-button').length === 0) {
                    $('.upload-logo-button').after('<button type="button" class="button remove-logo-button">' + wp_sms_voipms_i18n.remove_logo + '</button>');
                }
            });
            
            mediaUploader.open();
        });
        
        // Supprimer un logo
        $(document).on('click', '.remove-logo-button', function(e) {
            e.preventDefault();
            
            $('.custom-logo-container').empty();
            $('#custom_logo_id').val('');
            $('.upload-logo-button').text(wp_sms_voipms_i18n.add_logo);
            $(this).remove();
        });
    }
    
    /**
     * Charger la liste des contacts
     */
    function loadContacts() {
        if (isLoadingContacts) return;
        
        isLoadingContacts = true;
        contactsList.html('<div class="loader"></div>');
        
        $.ajax({
            url: wp_sms_voipms.rest_url + 'contacts',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
            },
            success: function(response) {
                contactsData = response;
                displayContacts(response);
            },
            error: function(xhr) {
                contactsList.html('<div class="error">' + wp_sms_voipms_i18n.error_loading_contacts + '</div>');
                console.error('Erreur lors du chargement des contacts:', xhr.responseText);
            },
            complete: function() {
                isLoadingContacts = false;
            }
        });
    }
    
    /**
     * Afficher la liste des contacts
     */
    function displayContacts(contacts) {
        if (contacts.length === 0) {
            contactsList.html('<div class="empty-list">' + wp_sms_voipms_i18n.no_contacts + '</div>');
            return;
        }
        
        var html = '<ul class="contact-list">';
        
        for (var i = 0; i < contacts.length; i++) {
            var contact = contacts[i];
            var activeClass = contact.phone_number === currentContact ? ' active' : '';
            
            html += '<li class="contact-item' + activeClass + '" data-phone="' + contact.phone_number + '" data-name="' + escapeHtml(contact.name) + '">';
            html += '<span class="contact-name">' + escapeHtml(contact.name) + '</span>';
            html += '<span class="contact-number">' + formatPhoneNumber(contact.phone_number) + '</span>';
            html += '</li>';
        }
        
        html += '</ul>';
        
        contactsList.html(html);
        
        // Ajouter les gestionnaires d'événements
        $('.contact-item').on('click', function() {
            var phoneNumber = $(this).data('phone');
            var name = $(this).data('name');
            
            selectConversation(phoneNumber, name);
        });
    }
    
    /**
     * Filtrer les contacts par terme de recherche
     */
    function filterContacts(searchTerm) {
        if (!searchTerm) {
            displayContacts(contactsData);
            return;
        }
        
        var filteredContacts = contactsData.filter(function(contact) {
            return contact.name.toLowerCase().includes(searchTerm) || 
                   contact.phone_number.includes(searchTerm);
        });
        
        displayContacts(filteredContacts);
    }
    
    /**
     * Rafraîchir la liste des contacts
     */
    function refreshContactsList() {
        if (contactsList.length > 0 && !isLoadingContacts) {
            // Conserver le terme de recherche actuel
            var searchTerm = contactSearchInput.val().toLowerCase();
            
            $.ajax({
                url: wp_sms_voipms.rest_url + 'contacts',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
                },
                success: function(response) {
                    contactsData = response;
                    
                    // Appliquer le filtre si un terme de recherche est présent
                    if (searchTerm) {
                        filterContacts(searchTerm);
                    } else {
                        displayContacts(response);
                    }
                },
                error: function(xhr) {
                    console.error('Erreur lors du rafraîchissement des contacts:', xhr.responseText);
                }
            });
        }
    }
    
    /**
     * Sélectionner une conversation
     */
    function selectConversation(phoneNumber, name) {
        currentContact = phoneNumber;
        
        // Mettre à jour l'interface
        $('#current-contact').val(phoneNumber);
        $('.contact-item').removeClass('active');
        $('.contact-item[data-phone="' + phoneNumber + '"]').addClass('active');
        
        $('#conversation-header .contact-name').text(name);
        $('#conversation-header .contact-number').text(formatPhoneNumber(phoneNumber));
        
        // Activer le formulaire d'envoi
        messageText.prop('disabled', false).focus();
        sendButton.prop('disabled', false);
        
        // Charger les messages
        loadMessages(phoneNumber);
    }
    
    /**
     * Charger les messages d'une conversation
     */
    function loadMessages(phoneNumber) {
        if (isLoadingMessages) return;
        
        isLoadingMessages = true;
        messagesOffset = 0;
        messagesContainer.html('<div class="loader"></div> ' + wp_sms_voipms_i18n.loading_messages);
        
        $.ajax({
            url: wp_sms_voipms.rest_url + 'messages',
            method: 'GET',
            data: {
                contact: phoneNumber,
                limit: messagesLimit,
                offset: messagesOffset
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
            },
            success: function(response) {
                displayMessages(response);
                
                // Mettre à jour l'offset pour le chargement suivant
                messagesOffset += response.length;
                
                // Faire défiler jusqu'en bas
                messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
            },
            error: function(xhr) {
                messagesContainer.html('<div class="error">' + wp_sms_voipms_i18n.error_loading_messages + '</div>');
                console.error('Erreur lors du chargement des messages:', xhr.responseText);
            },
            complete: function() {
                isLoadingMessages = false;
            }
        });
    }
    
    /**
     * Afficher les messages d'une conversation
     */
    function displayMessages(messages) {
        if (messages.length === 0) {
            messagesContainer.html('<div class="empty-list">' + wp_sms_voipms_i18n.no_messages + '</div>');
            return;
        }
        
        var html = '';
        
        for (var i = messages.length - 1; i >= 0; i--) {
            var message = messages[i];
            var direction = message.direction;
            var messageClass = direction === 'outgoing' ? 'message-outgoing' : 'message-incoming';
            
            html += '<div class="message ' + messageClass + '">';
            html += '<div class="message-content">' + formatMessageText(message.message) + '</div>';
            html += '<div class="message-timestamp">' + formatTimestamp(message.timestamp) + '</div>';
            
            if (direction === 'outgoing') {
                html += '<div class="message-status">' + message.status + '</div>';
            }
            
            html += '</div>';
        }
        
        messagesContainer.html(html);
    }
    
    /**
     * Envoyer un message
     */
    function sendMessage() {
        var message = messageText.val().trim();
        var to = currentContact;
        
        if (!message || !to) return;
        
        // Désactiver le bouton pendant l'envoi
        sendButton.prop('disabled', true).html('<div class="loader"></div> ' + wp_sms_voipms_i18n.sending);
        
        $.ajax({
            url: wp_sms_voipms.rest_url + 'send',
            method: 'POST',
            data: JSON.stringify({
                to: to,
                message: message
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
            },
            success: function(response) {
                // Effacer le champ de texte
                messageText.val('').focus();
                
                // Recharger les messages
                loadMessages(to);
            },
            error: function(xhr) {
                var errorMsg = '';
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || wp_sms_voipms_i18n.error_sending;
                } catch (e) {
                    errorMsg = wp_sms_voipms_i18n.error_sending;
                }
                
                alert(errorMsg);
                console.error('Erreur lors de l\'envoi du message:', xhr.responseText);
            },
            complete: function() {
                sendButton.prop('disabled', false).text(wp_sms_voipms_i18n.send);
            }
        });
    }
    
    /**
     * Enregistrer un nouveau contact
     */
    function saveContact(phoneNumber, name) {
        $.ajax({
            url: wp_sms_voipms.rest_url + 'contacts',
            method: 'POST',
            data: JSON.stringify({
                phone_number: phoneNumber,
                name: name
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
            },
            success: function(response) {
                // Rafraîchir la liste des contacts
                loadContacts();
            },
            error: function(xhr) {
                console.error('Erreur lors de l\'enregistrement du contact:', xhr.responseText);
            }
        });
    }
    
    /**
     * Formater un numéro de téléphone
     */
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
    
    /**
     * Formater un timestamp en date/heure lisible
     */
    function formatTimestamp(timestamp) {
        var date = new Date(timestamp);
        var now = new Date();
        var yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        
        var options = { hour: '2-digit', minute: '2-digit' };
        var timeString = date.toLocaleTimeString(undefined, options);
        
        // Si c'est aujourd'hui
        if (date.toDateString() === now.toDateString()) {
            return 'Aujourd\'hui, ' + timeString;
        }
        
        // Si c'est hier
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Hier, ' + timeString;
        }
        
        // Sinon, afficher la date complète
        return date.toLocaleDateString() + ', ' + timeString;
    }
    
    /**
     * Formater le texte du message (liens, emojis, etc.)
     */
    function formatMessageText(text) {
        if (!text) return '';
        
        // Échapper le HTML
        text = $('<div>').text(text).html();
        
        // Convertir les URLs en liens
        text = text.replace(
            /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig,
            '<a href="$1" target="_blank">$1</a>'
        );
        
        // Convertir les sauts de ligne en <br>
        text = text.replace(/\n/g, '<br>');
        
        return text;
    }
    
    // Rendre certaines fonctions accessibles globalement (pour les modales)
    window.wpSmsVoipms = {
        selectConversation: selectConversation
    };

})(jQuery);