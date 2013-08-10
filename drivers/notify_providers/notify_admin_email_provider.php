<?php

class Notify_Admin_Email_Provider extends Notify_Provider_Base
{
	const mode_smtp = 'smtp';
	const mode_sendmail = 'sendmail';
	const mode_mail = 'mail';

	/**
	 * Returns information about the provider.
	 * @return array Returns array with two keys: name and description
	 * array('name' => 'User Emailer', 'code' => 'user_emailer', 'description' => 'Sends an email to the user visiting the site')
	 */
	public function get_info()
	{
		return array(
			'name' => 'Staff Emailer',
			'code' => 'admin_emailer',
			'description' => 'Sends an email to internal staff'
		);
	}
		
	/**
	 * Builds the provider configuration user interface.
	 * @param $host ActiveRecord object to add fields to
	 */
	public function build_config_ui($host, $context = null)
	{
		$host->add_field('send_mode', 'Email Method', 'full', db_varchar)->tab('General')->display_as(frm_dropdown);
		$host->add_field('sender_name', 'Sender Name', 'left', db_varchar)->tab('General');
		$host->add_field('sender_email', 'Sender Email', 'right', db_varchar)->tab('General');
		
		$host->add_field('smtp_address', 'SMTP Address', 'full', db_varchar)->tab('SMTP');
		$host->add_field('smtp_authorization', 'SMTP Authorization Required', 'full', db_bool)->tab('SMTP');
		$host->add_field('smtp_user', 'User', 'left', db_varchar)->tab('SMTP');
		$host->add_field('smtp_password', 'Password', 'right', db_varchar)->tab('SMTP');
		$host->add_field('smtp_port', 'SMTP Port', 'full', db_varchar)->tab('SMTP');
		$host->add_field('smtp_ssl', 'SSL connection required', 'full', db_bool)->tab('SMTP');
		
		$host->add_field('sendmail_path', 'Sendmail path', 'full', db_varchar)->tab('Sendmail')
			->comment('Enter the full path to the server sendmail binary. Example: /usr/sbin/sendmail');

		// Conditional visibility
		$host->form_tab_id('SMTP', 'tab_smtp');
		$host->form_tab_id('Sendmail', 'tab_sendmail');
		$host->form_tab_visibility('SMTP', $host->send_mode == self::mode_smtp);
		$host->form_tab_visibility('Sendmail', $host->send_mode == self::mode_sendmail);

		// Admin (Internal) Template
		$host->add_field('admin_template_css', 'CSS', 'full', db_varchar)->tab('Email Template')
			->css_classes('code')->display_as(frm_code_editor)->size('large')
			->comment('This CSS document will be included to the layout', 'above')->language('css');

		$host->add_field('admin_template_content', 'Content', 'full', db_varchar)->tab('Email Template')
			->css_classes('code')->display_as(frm_code_editor)->size('huge')
			->comment('Please provide the HTML/PHP code for internal emails', 'above');
	}

	public function get_send_mode_options($key_index = -1)
	{
		return array(
			self::mode_mail     => 'PHP mail',
			self::mode_sendmail => 'Sendmail',
			self::mode_smtp     => 'SMTP',
		);
	}	

	public function before_config_ui_render($host, $controller) 
	{ 
		// Contains logic used in conditional visibility
		$controller->add_javascript($this->get_public_asset_path('javascript/provider_form.js'));
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
		$host->sender_name = Phpr::$config->get('APP_NAME');
		$host->sender_email = "noreply@yoursite.com";
		$host->send_mode = self::mode_mail;
		$host->smtp_port = 25;        
	}

	// Template UI
	// 

	public function build_template_ui($host, $context = null)
	{
		$host->add_field('admin_template_disabled', 'Disable Staff Email', 'full', db_bool)->comment('Check this box if you do not want this template to be sent')->tab('Staff Email');
		$host->add_field('admin_email_subject', 'Email Subject', 'full', db_varchar)->tab('Staff Email');
		$host->add_field('admin_email_content', 'Email Content', 'full', db_varchar)->display_as(frm_html)->size('huge')->tab('Staff Email');
	}

	public function init_template_data($host)
	{
		if (!$host->init_template_extension())
			return;

		if (!strlen($host->admin_email_subject)) $host->admin_email_subject = $host->get_internal_subject();
		if (!strlen($host->admin_email_content)) $host->admin_email_content = $host->get_internal_content();
	}

	// Sending
	// 

	public function send_notification($template) 
	{
		if ($template->admin_template_disabled)
			return false;
		
		if ($template->admin_email_subject && $template->admin_email_content) {
			$this->send_email(
				$template->get_recipients(true), 
				$template->admin_email_subject, 
				$template->admin_email_content
			);
			return true;
		}
		else 
			return false;
	}

	public function send_test_message($recipient) 
	{
		$subject = 'This is a test notification from '.c('site_name').'.';
		$content = '<p>Hi there!</p><p>This is a test message from '.c('site_name').'.</p><p>If you received this message by mistake it is safe to ignore.</p>';
		$this->send_email(array($recipient), $subject, $content);
		return true;
	}

	/**
	 * Sends email message
	 * @param string $recipients Specifies an email address of a recipient
	 * @param string $content 
	 * @param string $subject Specifies a message subject
	 */
	public function send_email($recipients = array(), $subject, $content)
	{
		if (!is_array($recipients))
			$recipients = array($recipients);

		if (!count($recipients))
			return;

		$host = $this->get_host_object();
		if (!$host)
			throw new Exception("The send_email() method must be called from a host object");

		$mailer = Net_Email::create();

		switch ($host->send_mode)
		{
			case self::mode_smtp:
				if ($host->smtp_authorization)
					$mailer->set_mode_smtp($host->smtp_address, $host->smtp_port, $host->smtp_ssl, $host->smtp_user, base64_decode($host->smtp_password));
				else
					$mailer->set_mode_smtp($host->smtp_address, $host->smtp_port, $host->smtp_ssl);
			break;
			case self::mode_sendmail:
				$mailer->set_mode_sendmail($host->sendmail_path);
			break;
			case self::mode_sendmail:
				$mailer->set_mode_mail();
			break;
		}

		$mailer->set_subject($subject);
		$mailer->set_content($content);
		$mailer->set_sender($host->sender_email, $host->sender_name);
		$mailer->add_recipients($recipients);
		$mailer->send();
	}	
  

}

