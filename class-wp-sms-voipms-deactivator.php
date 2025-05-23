<?php
/**
 * Le code qui s'exécute pendant la désactivation du plugin.
 */
class Wp_Sms_Voipms_Deactivator {

    /**
     * Désactivation du plugin.
     */
    public static function deactivate() {
        // Supprimer les capacités ajoutées
        self::remove_capabilities();
        
        // Nettoyage des options temporaires si nécessaire
        delete_option('wp_sms_voipms_flush_rewrite_rules');
    }
    
    /**
     * Supprimer les capacités des rôles.
     */
    private static function remove_capabilities() {
        // Administrateur
        $admin = get_role('administrator');
        if ($admin) {
            $admin->remove_cap('manage_voipms_sms');
            $admin->remove_cap('send_voipms_sms');
            $admin->remove_cap('read_voipms_sms');
        }
        
        // Éditeur
        $editor = get_role('editor');
        if ($editor) {
            $editor->remove_cap('send_voipms_sms');
            $editor->remove_cap('read_voipms_sms');
        }
        
        // Auteur
        $author = get_role('author');
        if ($author) {
            $author->remove_cap('read_voipms_sms');
        }
    }
}