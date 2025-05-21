/**
 * Script public pour WP SMS VoIPms
 */
(function($) {
    'use strict';

    // Variables globales
    var currentContact = '';
    var messagesContainer = $('#messages-container');
    var contactsList = $('#contacts-list');
    var messageForm = $('#message-form');
    var messageText = $('#message-text');
    var sendButton = $('#send-message-btn');
    var contactSearchInput = $('#sms-contact-search');
    var contactsData = [];
    var isLoadingMessages = false;
    var isLoadingContacts = false;
    var messagesOffset = 0;
    var messagesLimit = 50;
    var messagesRefreshInterval = null;
    
    /**
     * Initialisation à la fin du chargement du DOM
     */
    $(document).ready(function() {
        // Initialiser l'interface
        initializeContactsList();
        initializeMessageForm();
        initializeModals();
        
        // Rafraîchir la liste des contacts périodiquement
        setInterval(refreshContactsList, 30000);
    });
    
    /**
     * Initialiser la liste des contacts
     */
    function initializeContactsList() {
        if (contactsList.length > 0) {
            loadContacts();
            
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
        
        // Fermer les modales
        $('.wp-sms-voipms-modal-close').on('click', function() {
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
                alert(wp_sms_voipms.enter_phone_number);
            }
        });
    }
    
    /**
     * Charger la liste des contacts
     */
    function loadContacts() {
        if (isLoadingContacts) return;
        
        isLoadingContacts = true;
        contactsList.html('<div class="wp-sms-voipms-loading">' + wp_sms_voipms.loading_contacts + '</div>');
        
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
                contactsList.html('<div class="wp-sms-voipms-error">' + wp_sms_voipms.error_loading_contacts + '</div>');
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
            contactsList.html('<div class="wp-sms-voipms-empty-list">' + wp_sms_voipms.no_contacts + '</div>');
            return;
        }
        
        var html = '';
        
        for (var i = 0; i < contacts.length; i++) {
            var contact = contacts[i];
            var activeClass = contact.phone_number === currentContact ? ' active' : '';
            
            html += '<div class="wp-sms-voipms-contact-item' + activeClass + '" data-phone="' + contact.phone_number + '" data-name="' + contact.name + '">';
            html += '<span class="wp-sms-voipms-contact-name">' + contact.name + '</span>';
            html += '<span class="wp-sms-voipms-contact-number">' + formatPhoneNumber(contact.phone_number) + '</span>';
            html += '</div>';
        }
        
        contactsList.html(html);
        
        // Ajouter les gestionnaires d'événements
        $('.wp-sms-voipms-contact-item').on('click', function() {
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
        // Arrêter l'intervalle de rafraîchissement précédent
        if (messagesRefreshInterval) {
            clearInterval(messagesRefreshInterval);
        }
        
        currentContact = phoneNumber;
        
        // Mettre à jour l'interface
        $('#current-contact').val(phoneNumber);
        $('.wp-sms-voipms-contact-item').removeClass('active');
        $('.wp-sms-voipms-contact-item[data-phone="' + phoneNumber + '"]').addClass('active');
        
        $('#conversation-header .contact-name').text(name);
        $('#conversation-header .contact-number').text(formatPhoneNumber(phoneNumber));
        
        // Activer le formulaire d'envoi
        messageText.prop('disabled', false).focus();
        sendButton.prop('disabled', false);
        
        // Charger les messages
        loadMessages(phoneNumber);
        
        // Configurer le rafraîchissement automatique des messages
        messagesRefreshInterval = setInterval(function() {
            refreshMessages(phoneNumber);
        }, 15000);
    }
    
    /**
     * Charger les messages d'une conversation
     */
    function loadMessages(phoneNumber) {
        if (isLoadingMessages) return;
        
        isLoadingMessages = true;
        messagesOffset = 0;
        messagesContainer.html('<div class="wp-sms-voipms-loading">' + wp_sms_voipms.loading_messages + '</div>');
        
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
                messagesContainer.html('<div class="wp-sms-voipms-error">' + wp_sms_voipms.error_loading_messages + '</div>');
                console.error('Erreur lors du chargement des messages:', xhr.responseText);
            },
            complete: function() {
                isLoadingMessages = false;
            }
        });
    }
    
    /**
     * Rafraîchir les messages d'une conversation
     */
    function refreshMessages(phoneNumber) {
        if (isLoadingMessages || phoneNumber !== currentContact) return;
        
        $.ajax({
            url: wp_sms_voipms.rest_url + 'messages',
            method: 'GET',
            data: {
                contact: phoneNumber,
                limit: 10,
                offset: 0
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wp_sms_voipms.nonce);
            },
            success: function(response) {
                // Si de nouveaux messages sont disponibles, recharger la conversation
                if (response.length > 0 && messagesContainer.find('.wp-sms-voipms-message').length < response.length) {
                    loadMessages(phoneNumber);
                }
            },
            error: function(xhr) {
                console.error('Erreur lors du rafraîchissement des messages:', xhr.responseText);
            }
        });
    }
    
    /**
     * Afficher les messages d'une conversation
     */
    function displayMessages(messages) {
        if (messages.length === 0) {
            messagesContainer.html('<div class="wp-sms-voipms-empty-list">' + wp_sms_voipms.no_messages + '</div>');
            return;
        }
        
        var html = '';
        
        for (var i = messages.length - 1; i >= 0; i--) {
            var message = messages[i];
            var direction = message.direction;
            var messageClass = direction === 'outgoing' ? 'wp-sms-voipms-message-outgoing' : 'wp-sms-voipms-message-incoming';
            
            html += '<div class="wp-sms-voipms-message ' + messageClass + '">';
            html += '<div class="wp-sms-voipms-message-content">' + formatMessageText(message.message) + '</div>';
            html += '<div class="wp-sms-voipms-message-time">' + formatTimestamp(message.timestamp) + '</div>';
            
            if (direction === 'outgoing') {
                html += '<div class="wp-sms-voipms-message-status">' + message.status + '</div>';
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
        sendButton.prop('disabled', true).html('<span class="wp-sms-voipms-sending">' + wp_sms_voipms.sending + '</span>');
        
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
                    errorMsg = response.message || wp_sms_voipms.error_sending;
                } catch (e) {
                    errorMsg = wp_sms_voipms.error_sending;
                }
                
                alert(errorMsg);
                console.error('Erreur lors de l\'envoi du message:', xhr.responseText);
            },
            complete: function() {
                sendButton.prop('disabled', false).text(wp_sms_voipms.send);
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
            return wp_sms_voipms.today + ', ' + timeString;
        }
        
        // Si c'est hier
        if (date.toDateString() === yesterday.toDateString()) {
            return wp_sms_voipms.yesterday + ', ' + timeString;
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