<?php


class nc_ui_controller {

    //--------------------------------------------------------------------------

    protected $view_path      = null;
    protected $current_action = 'index';
    protected $binds          = array();

    /** @var ui_config */
    protected $ui_config;
    /** @var nc_core */
    protected $nc_core;
    /** @var nc_db */
    protected $db;
    /** @var nc_input */
    protected $input;
    /** @var  int */
    protected $site_id;

    //--------------------------------------------------------------------------

    public function __construct($view_path = null)
    {
        if ($view_path) {
            $this->set_view_path($view_path);
        }

        $this->ui_config =& $GLOBALS['UI_CONFIG'];
        $this->nc_core   = nc_core();
        $this->db        = nc_core('db');
        $this->input     = nc_core('input');

        $this->site_id = $this->determine_site_id();

        $this->init();
    }

    //-------------------------------------------------------------------------

    protected function init() {}

    //-------------------------------------------------------------------------

    protected function init_view($view) {
        // $view->with(/* ... */);
        return $view;
    }

    //-------------------------------------------------------------------------
    /**
     * @return int
     */
    public function determine_site_id() {
        $new_id = (int)nc_core('input')->fetch_post_get('site_id');
        $cookie_name = 'nc_admin_site_id';

        $nc_catalogue = nc_core::get_object()->catalogue;

        if ($new_id) {
            $nc_core = nc_core::get_object();
            $nc_core->cookie->set($cookie_name, $new_id, 0);
            $site_id = $new_id;
        }
        else if ((int)$_COOKIE[$cookie_name]) {
            $site_id = $_COOKIE[$cookie_name];
        }
        else {
            $site_id = (int)$nc_catalogue->get_current('Catalogue_ID');
        }

        // Проверка сайта на существование
        try {
            $nc_catalogue->get_by_id($site_id);
        }
        catch (Exception $e) {
            $site_id = (int)$nc_catalogue->get_current('Catalogue_ID');
        }

        return $site_id;
    }

    //-------------------------------------------------------------------------

    protected function before_action() {
        return '';
    }

    //-------------------------------------------------------------------------

    protected function after_action($result) {
        return true;
    }


    //-------------------------------------------------------------------------

    /**
     * [bind description]
     * $this->bind('save', array('id', 'message'));
     * $this->bind('save', array('id', 'file'=>$input->fetch_files('file')) );
     * @param  [type] $action       [description]
     * @param  [type] $request_keys [description]
     * @return [type]               [description]
     */
    protected function bind($action, $request_keys)
    {
        $this->binds[$action] = $request_keys;
    }

    //-------------------------------------------------------------------------

    public function set_view_path($path)
    {
        $this->view_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    //-------------------------------------------------------------------------

    public function set_action($action)
    {
        $this->current_action = $action;
    }

    //-------------------------------------------------------------------------

    public function execute($action = null)
    {
        if ($action) {
            $this->set_action($action);
        }

        $action_method = 'action_' . $this->current_action;

        if ( ! method_exists($this, $action_method)) {
            return false;
        }

        $result = $this->before_action();

        if ($result === false) {
            return false;
        }

        $args = array();
        if (isset($this->binds[$action])) {
            foreach ($this->binds[$action] as $key => $value) {
                if (is_numeric($key)) {
                    $key   = $value;
                    $value = $this->input->fetch_post_get($key);
                    if (!$value) {
                        $value = $this->input->fetch_files($key);
                    }
                }
                $args[$key] = $value;
            }
        }

        try {
            $result = call_user_func_array(array($this, $action_method), $args);
        } catch (Exception $e) {
            return $this->nc_core->ui->alert->error($e->getMessage());
        }


        $after_result = $this->after_action($result);

        return is_null($after_result) || $after_result === true ? $result : $after_result;
    }

    //-------------------------------------------------------------------------

    /**
     * @param $view
     * @param array $data
     * @return nc_ui_view
     */
    protected function view($view, $data = array()) {
        $view   = nc_core('ui')->view($this->view_path . $view . '.view.php', $data);
        $result = $this->init_view($view);

        return $result === null ? $view : $result;
    }

    //-------------------------------------------------------------------------


}