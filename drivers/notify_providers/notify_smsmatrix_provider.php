<?php

class Notify_SmsMatrix_Provider extends Notify_Provider_Base
{
	const api_url = 'https://www.smsmatrix.com/matrix';

	/**
	 * Returns information about the provider.
	 * @return array Returns array with two keys: name and description
	 * array('name' => 'User Emailer', 'code' => 'user_emailer', 'description' => 'Sends an email to the user visiting the site')
	 */
	public function get_info()
	{
		return array(
			'name' => 'SMS Matrix',
			'code' => 'smsmatrix',
			'description' => 'Sends an SMS using SMS Matrix'
		);
	}
	
	/**
	 * Builds the provider configuration user interface.
	 * @param $host ActiveRecord object to add fields to
	 */
	public function build_config_ui($host, $context = null)
	{
		$host->add_field('username', 'User Name', 'left')->display_as(frm_text)->comment('Your SMS Matrix username', 'above')->validation()->fn('trim')->required('Please specify your user name');
		$host->add_field('password', 'Password', 'right')->display_as(frm_password)->comment('Your SMS Matrix password', 'above')->validation()->fn('trim')->required('Please specify your password');
		$host->add_field('tts_fallback', 'TTS Fallback', 'full', db_bool)->display_as(frm_checkbox)->comment('If enabled, system will automatically check if provided phone number is mobile or land-line, so appropriate message (SMS or TTS) will be sent. Otherwise, sending SMS to land-line numbers will result in failure. This feature only works for USA/CAN numbers.', 'below');
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
		$fields['tts'] = $host->tts_fallback ? 1 : 0;
		$fields['pin'] = implode(',', $recipient_numbers);
		$fields['txt'] = $message;

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
		if (!preg_match('/STATUSCODE=([0-9]+)/m', $response, $matches))
			throw new Phpr_SystemException("Invalid SMS response text ".$response);
			
		$code = $matches[1];
		if ($code <= 399) {
			if (!preg_match('/ID=([\d\w\s]+)$/m', $response, $matches))
				return array();
				
			return array(trim($matches[1]));
		}

		if (preg_match('/STATUSTXT=([\d\w\s]+)$/m', $response, $matches))
			throw new Phpr_SystemException("Error sending SMS message: ". $matches[1]);
		else
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

