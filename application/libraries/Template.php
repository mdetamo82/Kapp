<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application Template Library
 *
 * Centralized application layout renderer.
 *
 * Responsibilities:
 * - Compose the application layout
 * - Provide common view context
 * - Manage page metadata
 * - Manage page-specific CSS/JavaScript
 * - Manage layout component visibility
 *
 * This library does NOT:
 * - Perform authorization
 * - Perform business logic
 * - Perform database mutations
 * - Decide permissions
 */
class Template
{
    /**
     * CodeIgniter instance.
     *
     * @var CI_Controller
     */
    protected $CI;

    /**
     * Layout components.
     *
     * @var array
     */
    protected $layout_parts = [
        'header'  => true,
        'navbar'  => true,
        'sidebar' => true,
        'footer'  => true,
        'scripts' => true,
    ];

    /**
     * Current authenticated user.
     *
     * @var object|null
     */
    protected $current_user = null;

    /**
     * Whether current user has been resolved.
     *
     * @var bool
     */
    protected $user_resolved = false;

    /**
     * Page title.
     *
     * @var string
     */
    protected $title = 'Nebat Import Export';

    /**
     * Default application title.
     *
     * @var string
     */
    protected $app_title = 'Nebat Import Export';

    /**
     * Page-specific CSS files.
     *
     * @var array
     */
    protected $css = [];

    /**
     * Page-specific JavaScript files.
     *
     * @var array
     */
    protected $js = [];

    /**
     * Inline JavaScript.
     *
     * @var array
     */
    protected $inline_js = [];

    /**
     * Body classes.
     *
     * @var array
     */
    protected $body_classes = [
        'hold-transition',
        'sidebar-mini',
        'layout-fixed',
    ];

    /**
     * Meta tags.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Breadcrumbs.
     *
     * @var array
     */
    protected $breadcrumbs = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->library('ion_auth');

        /*
         * Application configuration.
         */
        $this->CI->config->load('app', true);

        $configured_title = $this->CI->config->item(
            'app_title',
            'app'
        );

        if (is_string($configured_title) && trim($configured_title) !== '') {
            $this->app_title = trim($configured_title);
            $this->title = $this->app_title;
        }
    }

    /**
     * Set page title.
     *
     * @param string $title
     * @return $this
     */
    public function set_title($title)
    {
        $title = trim((string) $title);

        if ($title !== '') {
            $this->title = $title;
        }

        return $this;
    }

    /**
     * Get current page title.
     *
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * Set application title.
     *
     * @param string $title
     * @return $this
     */
    public function set_app_title($title)
    {
        $title = trim((string) $title);

        if ($title !== '') {
            $this->app_title = $title;
        }

        return $this;
    }

    /**
     * Add CSS asset.
     *
     * Accepts either:
     *
     * assets/css/customers.css
     *
     * or:
     *
     * https://example.com/customers.css
     *
     * @param string $path
     * @return $this
     */
    public function add_css($path)
    {
        $path = trim((string) $path);

        if ($path !== '' && !in_array($path, $this->css, true)) {
            $this->css[] = $path;
        }

        return $this;
    }

    /**
     * Add JavaScript asset.
     *
     * @param string $path
     * @return $this
     */
    public function add_js($path)
    {
        $path = trim((string) $path);

        if ($path !== '' && !in_array($path, $this->js, true)) {
            $this->js[] = $path;
        }

        return $this;
    }

    /**
     * Add inline JavaScript.
     *
     * @param string $script
     * @return $this
     */
    public function add_inline_js($script)
    {
        $script = trim((string) $script);

        if ($script !== '') {
            $this->inline_js[] = $script;
        }

        return $this;
    }

    /**
     * Add body class.
     *
     * @param string $class
     * @return $this
     */
    public function add_body_class($class)
    {
        $class = trim((string) $class);

        if ($class !== '' && !in_array($class, $this->body_classes, true)) {
            $this->body_classes[] = $class;
        }

        return $this;
    }

    /**
     * Remove body class.
     *
     * @param string $class
     * @return $this
     */
    public function remove_body_class($class)
    {
        $class = trim((string) $class);

        if ($class === '') {
            return $this;
        }

        $this->body_classes = array_values(
            array_diff($this->body_classes, [$class])
        );

        return $this;
    }

    /**
     * Set arbitrary meta tag.
     *
     * Example:
     *
     * set_meta('description', 'Dashboard')
     *
     * @param string $name
     * @param string $content
     * @return $this
     */
    public function set_meta($name, $content)
    {
        $name = trim((string) $name);

        if ($name !== '') {
            $this->meta[$name] = (string) $content;
        }

        return $this;
    }

    /**
     * Add breadcrumb.
     *
     * Example:
     *
     * add_breadcrumb('Customers', 'customers')
     *
     * @param string $label
     * @param string|null $url
     * @return $this
     */
    public function add_breadcrumb($label, $url = null)
    {
        $label = trim((string) $label);

        if ($label === '') {
            return $this;
        }

        $this->breadcrumbs[] = [
            'label' => $label,
            'url'   => $url !== null
                ? trim((string) $url)
                : null,
        ];

        return $this;
    }

    /**
     * Clear breadcrumbs.
     *
     * @return $this
     */
    public function clear_breadcrumbs()
    {
        $this->breadcrumbs = [];

        return $this;
    }

    /**
     * Disable layout component.
     *
     * @param string $component
     * @return $this
     */
    public function disable($component)
    {
        if (array_key_exists($component, $this->layout_parts)) {
            $this->layout_parts[$component] = false;
        }

        return $this;
    }

    /**
     * Enable layout component.
     *
     * @param string $component
     * @return $this
     */
    public function enable($component)
    {
        if (array_key_exists($component, $this->layout_parts)) {
            $this->layout_parts[$component] = true;
        }

        return $this;
    }

    /**
     * Render application view.
     *
     * @param string $view
     * @param array $data
     * @param bool $return
     * @return string|void
     */
    public function render($view, $data = [], $return = false)
    {
        if (!is_string($view) || trim($view) === '') {
            throw new InvalidArgumentException(
                'View parameter must be a non-empty string.'
            );
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException(
                'Data parameter must be an array.'
            );
        }

        $view = trim($view);

        /*
         * Resolve common application context.
         */
        $context = $this->build_context();

        /*
         * Page data overrides defaults intentionally.
         */
        $data = array_merge($context, $data);

        /*
         * Render into a string when requested.
         */
        if ($return) {
            ob_start();

            try {
                $this->render_views($view, $data);

                return ob_get_clean();
            } catch (Throwable $e) {
                ob_end_clean();

                throw $e;
            }
        }

        $this->render_views($view, $data);
    }

    /**
     * Build common template context.
     *
     * @return array
     */
    protected function build_context()
    {
        $user = $this->get_current_user();

        $dark_mode = false;

        /*
         * User-specific settings are only resolved
         * when an authenticated user exists.
         */
        if ($user) {
            $this->CI->load->model('User_model');

            if (
                method_exists(
                    $this->CI->User_model,
                    'get_dark_mode'
                )
            ) {
                $dark_mode = (bool)
                    $this->CI->User_model->get_dark_mode(
                        $user->id
                    );
            }
        }

        $body_classes = $this->body_classes;

        if ($dark_mode) {
            $body_classes[] = 'dark-mode';
        }

        return [
            'app_title'        => $this->app_title,
            'title'            => $this->title,
            'current_user'     => $user,
            'dark_mode'        => $dark_mode,
            'body_classes'     => array_values(
                array_unique($body_classes)
            ),
            'page_css'         => $this->css,
            'page_js'          => $this->js,
            'inline_js'        => $this->inline_js,
            'meta'             => $this->meta,
            'breadcrumbs'      => $this->breadcrumbs,
        ];
    }

    /**
     * Render layout components.
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    protected function render_views($view, $data)
    {
        if ($this->layout_parts['header']) {
            $this->CI->load->view(
                'templates/header',
                $data
            );
        }

        if ($this->layout_parts['navbar']) {
            $this->CI->load->view(
                'templates/navbar',
                $data
            );
        }

        if ($this->layout_parts['sidebar']) {
            $this->CI->load->view(
                'templates/sidebar',
                $data
            );
        }

        $this->CI->load->view(
            $view,
            $data
        );

        if ($this->layout_parts['footer']) {
            $this->CI->load->view(
                'templates/footer',
                $data
            );
        }

        if ($this->layout_parts['scripts']) {
            $this->CI->load->view(
                'templates/scripts',
                $data
            );
        }
    }

    /**
     * Resolve authenticated user once per request.
     *
     * @return object|null
     */
    protected function get_current_user()
    {
        if ($this->user_resolved) {
            return $this->current_user;
        }

        $this->user_resolved = true;
        $this->current_user = null;

        if (!$this->CI->ion_auth->logged_in()) {
            return null;
        }

        $user = $this->CI->ion_auth
            ->user()
            ->row();

        if (is_object($user)) {
            $this->current_user = $user;
        }

        return $this->current_user;
    }
}