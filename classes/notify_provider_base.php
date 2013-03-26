<?

/**
 * Represents the generic payment type.
 * All other payment types must be derived from this class
 */

class Notify_Provider_Base extends Phpr_Driver_Base
{
	public static $driver_folder = 'notify_providers';
	public static $driver_suffix = '_provider';

	public $config;

	// Returns information about the provider
	public function get_info()
	{
		return array(
			'code'        => 'unknown',
			'name'        => 'Unknown',
			'description' => 'Unknown notification provider'
		);
	}

	/*
	 * Handles login for the provider callback URL
	 * @return array($user_details) on success, false on failure
	 * $user_details should contain a field called 'token'
	 */
	public function login() { }

	/**
	 * Returns the URL used to log in with this provider
	 * @return string $url
	 */
	public function get_login_url() { }

	public function get_name() 
	{
		$info = $this->get_info();
		return (isset($info['name'])) ? $info['name'] : false;
	}

	public function get_code() 
	{
		$info = $this->get_info();
		return (isset($info['code'])) ? $info['code'] : false;
	}

	public function get_description() 
	{
		$info = $this->get_info();
		return (isset($info['description'])) ? $info['description'] : false;
	}

	// Config UI
	// 

	/**
	 * Builds the payment type administration user interface
	 * For drop-down and radio fields you should also add methods returning
	 * options. For example, of you want to have Sizes drop-down:
	 * public function get_sizes_options();
	 * This method should return array with keys corresponding your option identifiers
	 * and values corresponding its titles.
	 *
	 * @param $host ActiveRecord object to add fields to
	 * @param string $context Form context. In preview mode its value is 'preview'
	 */
	public function build_config_ui($host, $context = null) { }    

	/**
	 * Initializes configuration data when the social provider is first created
	 * Use host object to access and set fields previously added with build_config_ui method.
	 * @param $host ActiveRecord object containing configuration fields values
	 */
	public function init_config_data($host) { }

	// Event hook before the config page renders
	public function before_config_ui_render($host, $controller) { }

	// Template UI
	// 

	public function build_template_ui($host, $context = null) { }

	public function init_template_data($host)  { }

	// Send Notification
	// 
	
	public function send_notification($template) { }
	public function send_test_message($recipient) { }    
}
