<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application Base Controller
 *
 * Extends Base_Controller and adds exactly one thing:
 * authentication is required for every action, enforced in the
 * constructor before any controller method runs.
 *
 * Every authenticated application controller (Customer,
 * Supplier, Invoice, ...) should extend THIS class.
 *
 * Genuinely public controllers (Auth — login, logout,
 * forgot-password) must NOT extend this class. They should
 * extend Base_Controller directly instead, which gives them
 * every shared helper here without ever inheriting the
 * authentication requirement.
 *
 * This is deliberately a structural choice (which class you
 * extend) rather than a boolean flag on the class, so "does
 * this controller require login" can't be silently left
 * on/off by a copy-paste mistake — a controller either extends
 * a class that enforces auth, or it doesn't.
 */
class MY_Controller extends Base_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->require_authentication();
    }
}
