<?php
/**
 * Contains the main functions for jigoshop, stores variables, and handles error messages
 *
 *
 * @package		JigoShop
 * @category	Core
 * @author		Jigowatt
 * @since		1.0
 */
class jigoshop {
	
	private static $_instance;
	
	public static $errors = array();
	public static $messages = array();
	public static $attribute_taxonomies;
	
	public static $plugin_url;
	public static $plugin_path;
	
	const SHOP_SMALL_W = '150';
	const SHOP_SMALL_H = '150';
	const SHOP_TINY_W = '36';
	const SHOP_TINY_H = '36';
	const SHOP_THUMBNAIL_W = '90';
	const SHOP_THUMBNAIL_H = '90';
	const SHOP_LARGE_W = '300';
	const SHOP_LARGE_H = '300';
	
	/** constructor */
	function __construct () {
		global $wpdb;
		
		// Vars
		self::$attribute_taxonomies = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."jigoshop_attribute_taxonomies;");
		if (isset($_SESSION['errors'])) self::$errors = $_SESSION['errors'];
		if (isset($_SESSION['messages'])) self::$messages = $_SESSION['messages'];
		
		unset($_SESSION['messages']);
		unset($_SESSION['errors']);
		
		// Hooks
		add_filter('wp_redirect', array(&$this, 'redirect'), 1, 2);
	}
	
	/** get */
	public static function get() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
	
	/**
	 * Get the plugin url
	 *
	 * @return  string	url
	 */
	public static function plugin_url() { 
		if(self::$plugin_url) return self::$plugin_url;
		return self::$plugin_url = WP_PLUGIN_URL . "/" . plugin_basename( dirname(dirname(__FILE__))); 
	}
	
	/**
	 * Get the plugin path
	 *
	 * @return  string	url
	 */
	public static function plugin_path() { 	
		if(self::$plugin_path) return self::$plugin_path;
		return self::$plugin_path = WP_PLUGIN_DIR . "/" . plugin_basename( dirname(dirname(__FILE__))); 
	 }
	
	/**
	 * Get a var
	 *
	 * Variable is filtered by jigoshop_get_var_{var name}
	 *
	 * @param   string	var
	 * @return  string	variable
	 */
	public static function get_var($var) {
		$return = '';
		switch ($var) :
			case "version" : $return = JIGOSHOP_VERSION; break;
			case "shop_small_w" : $return = self::SHOP_SMALL_W; break;
			case "shop_small_h" : $return = self::SHOP_SMALL_H; break;
			case "shop_tiny_w" : $return = self::SHOP_TINY_W; break;
			case "shop_tiny_h" : $return = self::SHOP_TINY_H; break;
			case "shop_thumbnail_w" : $return = self::SHOP_THUMBNAIL_W; break;
			case "shop_thumbnail_h" : $return = self::SHOP_THUMBNAIL_H; break;
			case "shop_large_w" : $return = self::SHOP_LARGE_W; break;
			case "shop_large_h" : $return = self::SHOP_LARGE_H; break;
		endswitch;
		return apply_filters( 'jigoshop_get_var_'.$var, $return );
	}
	
	/**
	 * Add an error
	 *
	 * @param   string	error
	 */
	function add_error( $error ) { self::$errors[] = $error; }
	
	/**
	 * Add a message
	 *
	 * @param   string	message
	 */
	function add_message( $message ) { self::$messages[] = $message; }
	
	/** Clear messages and errors from the session data */
	function clear_messages() {
		self::$errors = self::$messages = array();
		unset($_SESSION['messages']);
		unset($_SESSION['errors']);
	}
	
	/**
	 * Get error count
	 *
	 * @return   int
	 */
	function error_count() { return sizeof(self::$errors); }
	
	/**
	 * Get message count
	 *
	 * @return   int
	 */
	function message_count() { return sizeof(self::$messages); }
	
	/**
	 * Output the errors and messages
	 *
	 * @return   bool
	 */
	public static function show_messages() {
	
		if (isset(self::$errors) && sizeof(self::$errors)>0) :
			echo '<div class="jigoshop_error">'.self::$errors[0].'</div>';
			self::clear_messages();
			return true;
		elseif (isset(self::$messages) && sizeof(self::$messages)>0) :
			echo '<div class="jigoshop_message">'.self::$messages[0].'</div>';
			self::clear_messages();
			return true;
		else :
			return false;
		endif;
	}
	
	public static function nonce_field ($action, $referer = true , $echo = true) {
		
		$name = '_n';
		$action = 'jigoshop-' . $action;
		
		return wp_nonce_field($action, $name, $referer, $echo);
		
	}
	
	public static function nonce_url ($action, $url = '') {
		
		$name = '_n';
		$action = 'jigoshop-' . $action;
		
		$url = add_query_arg( $name, wp_create_nonce( $action ), $url);
		
		return $url;
	}
	/**
	 * Check a nonce and sets jigoshop error in case it is invalid
	 * To fail silently, set the error_message to an empty string
	 * 
	 * @param 	string $name the nonce name
	 * @param	string $action then nonce action
	 * @param   string $method the http request method _POST, _GET or _REQUEST
	 * @param   string $error_message custom error message, or false for default message, or an empty string to fail silently
	 * 
	 * @return   bool
	 */
	public static function verify_nonce($action, $method='_POST', $error_message = false) {
		
		$name = '_n';
		$action = 'jigoshop-' . $action;
		
		if( $error_message === false ) $error_message = __('Action failed. Please refresh the page and retry.', 'jigoshop'); 
		
		if(!in_array($method, array('_GET', '_POST', '_REQUEST'))) $method = '_POST';
		
		/*
		$request = $GLOBALS[$method];
		
		if ( isset($request[$name]) && wp_verify_nonce($request[$name], $action) ) return true;
		*/
		
		if ( isset($_REQUEST[$name]) && wp_verify_nonce($_REQUEST[$name], $action) ) return true;
		
		if( $error_message ) jigoshop::add_error( $error_message );
		
		return false;
		
	}
	
	/**
	 * Redirection hook which stores messages into session data
	 *
	 * @param   location
	 * @param   status
	 * @return  location
	 */
	function redirect( $location, $status ) {
		$_SESSION['errors'] = self::$errors;
		$_SESSION['messages'] = self::$messages;
		return $location;
	}
	
}