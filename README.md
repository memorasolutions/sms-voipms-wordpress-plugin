# WP SMS VoIPms

Ce plugin permet d'envoyer et de recevoir des SMS via l'API VoIP.ms directement depuis WordPress. Il est créé et maintenu par [MEMORA Solutions](https://memora.solutions).

## Installation
1. Téléversez le dossier du plugin dans `wp-content/plugins`.
2. Activez le plugin depuis l'administration WordPress.
3. Rendez-vous dans **SMS VoIPms → Réglages** pour renseigner vos identifiants API et le DID par défaut.
4. Le mot de passe API est chiffré avant d'être enregistré en base de données.

## Utilisation
- Accédez à l'interface SMS depuis le menu **SMS VoIPms**.
- Gérez vos contacts et discutez depuis cette interface ou à l'aide du shortcode :
  
  ```
  [voipms_sms_interface]
  ```

Un bouton permet également de tester la connexion à l'API depuis la page de réglages.

### Configuration du webhook
Pour recevoir les SMS entrants, configurez votre compte VoIP.ms afin d'envoyer les notifications HTTP POST vers l'URL suivante :

```
https://votre-site.com/wp-json/wp-sms-voipms/v1/receive
```

Remplacez `https://votre-site.com` par l'adresse de votre site WordPress.

