<?php

class Notify_SmsGlobal_Provider extends Notify_Provider_Base
{
	const api_url = 'http://www.smsglobal.com/http-api.php';

	/**
	 * Returns information about the provider.
	 * @return array Returns array with two keys: name and description
	 * array('name' => 'User Emailer', 'code' => 'user_emailer', 'description' => 'Sends an email to the user visiting the site')
	 */
	public function get_info()
	{
		return array(
			'name' => 'SMSGlobal',
			'code' => 'smsglobal',
			'description' => 'Sends an SMS using SMSGlobal'
		);
	}
	
	/**
	 * Builds the provider configuration user interface.
	 * @param $host ActiveRecord object to add fields to
	 */
	public function build_config_ui($host, $context = null)
	{
		$host->add_field('username', 'User Name', 'left')->display_as(frm_text)->comment('Your SMSGlobal username', 'above')->validation()->fn('trim')->required('Please specify your user name');
		$host->add_field('password', 'Password', 'right')->display_as(frm_password)->comment('Your SMSGlobal password', 'above')->validation()->fn('trim')->required('Please specify your password');
		$host->add_field('from', 'Sender ID', 'left')->display_as(frm_text)->comment('MSIDSN or Sender ID that the message will appear from. Eg: 61409317436  (Do not use +before the country code)', 'above')->validation()->fn('trim')->required('Please specify a sender ID');
		$host->add_field('maxsplit', 'Split messages', 'right', db_bool)->display_as(frm_checkbox)->comment('If enabled, messages that exceed 160 characters will be split into multiple messages.', 'below');
	}
	
	/**
	 * Validates configuration data before it is saved to database
	 * Use host object field_error method to report about errors in data:
	 * $host->field_error('max_weight', 'Max weight should not be less than Min weight');
	 * @param $host ActiveRecord object containing configuration fields values
	 */
	public function validate_config_on_save($host)
	{
	}
	
	/**
	 * Initializes configuration data when the provider object is created for the first time
	 * Use host object to access and set fields previously added with build_config_ui method.
	 * @param $host ActiveRecord object containing configuration fields values
	 */
	public function init_config_data($host)
	{
	}

	// Template UI
	// 

	public function build_template_ui($host, $context = null)
	{
		$host->add_field('user_sms_message', 'User SMS Message', 'full', db_varchar)->tab('SMS');
		$host->add_field('admin_sms_message', 'Admin SMS Message', 'full', db_varchar)->tab('SMS');
	}

	public function init_template_data($host)
	{
		if (!$host->init_template_extension())
			return;

		if (!strlen($host->user_sms_message)) 
			$host->user_sms_message = $host->get_subject();

		if (!strlen($host->admin_sms_message)) 
			$host->admin_sms_message = $host->get_internal_subject();
	}


	// Sending
	// 

	public function send_notification($template) 
	{
		if ($template->user_sms_message)
			$this->send_message($template->get_recipients(), $template->user_sms_message);

		if ($template->admin_sms_message)
			$this->send_message($template->get_recipients(true), $template->admin_sms_message);

		return true;
	}

	public function send_test_message($recipient) 
	{
		$message = 'This is a test notification from '.c('site_name').'.';
		$this->send_message(array($recipient), $message);
		return true;
	}

	/**
	 * Sends SMS message to a specific recipient(s).
	 * @param array $recipients An array of recipients phone numbers.
	 * @param string $message Message text
	 * @return array Returns an array of identifiers assigned to the messages by the SMS provider.
	 */
	public function send_message($recipients, $message)
	{
		$host = $this->get_host_object();
		if (!$host)
			throw new Exception("The send_message() method must be called from a host object");

		$recipient_numbers = $this->get_recipient_numbers($recipients);
		if (!count($recipient_numbers))
			return;

		$fields = array();
		$fields['username'] = $host->username;
		$fields['password'] = $host->password;
		$fields['to'] = implode(',', $recipient_numbers);
		$fields['text'] = $message;

		if ($host->maxsplit)
			$fields['maxsplit'] = 9;

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, self::api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$response = curl_exec($ch);

		if (curl_errno($ch))
			throw new Phpr_SystemException("Error connecting to SMS provider: ".curl_error($ch));
		else
			curl_close($ch);
			
		$matches = array();
		if (!preg_match('/OK:\s+(.*)$/m', $response, $matches))
			throw new Phpr_SystemException('Error sending SMS message: '. $matches[1]);
			
		$code = $matches[1];
		if ($code != '0')
			throw new Phpr_SystemException("Error sending SMS message.");
	}

	private function get_recipient_numbers($recipients) 
	{
		$recipient_numbers = array();
		foreach ($recipients as $recipient) {
			if (!$recipient->mobile)
				continue;

			$recipient_numbers[] = $recipient->mobile;
		}

		return $recipient_numbers;
	}
	
}

