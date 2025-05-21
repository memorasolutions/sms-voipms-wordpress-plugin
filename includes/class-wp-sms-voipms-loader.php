<?php
/**
 * Registre tous les hooks du plugin.
 *
 * Maintient une liste de tous les hooks qui sont enregistrés dans le plugin.
 */
class Wp_Sms_Voipms_Loader {

    /**
     * Les actions enregistrées avec WordPress.
     */
    protected $actions;

    /**
     * Les filtres enregistrés avec WordPress.
     */
    protected $filters;

    /**
     * Initialiser les collections utilisées pour maintenir les actions et filtres.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Ajouter une nouvelle action à la collection.
     *
     * @param string $hook Le nom du hook WordPress auquel l'action est attachée.
     * @param object $component Une référence à l'instance de l'objet sur lequel l'action est définie.
     * @param string $callback Le nom de la fonction définie sur $component.
     * @param int $priority La priorité à laquelle l'action doit être exécutée.
     * @param int $accepted_args Le nombre d'arguments que l'action accepte.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Ajouter un nouveau filtre à la collection.
     *
     * @param string $hook Le nom du hook WordPress auquel le filtre est attaché.
     * @param object $component Une référence à l'instance de l'objet sur lequel le filtre est défini.
     * @param string $callback Le nom de la fonction définie sur $component.
     * @param int $priority La priorité à laquelle le filtre doit être exécuté.
     * @param int $accepted_args Le nombre d'arguments que le filtre accepte.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Utilitaire pour enregistrer les hooks dans la collection.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Enregistrer les filtres et actions avec WordPress.
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}