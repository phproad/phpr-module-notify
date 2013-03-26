<?php

class Notify 
{
	public static function trigger($template_code, $params=array())
	{
		$template = Notify_Manager::create_template_from_code($template_code);

		// Check required parameters
		foreach ($template->required_params as $name) {
			if (!array_key_exists($name, $params)) {
				trace_log('Notification trigger ('.$this->_class_name.') is missing required parameter: '. $name);
				return;
			}
		}
		
		$template->prepare_template($template, $params);
		return $template->send();
	}

	public static function create_recipient($name, $email, $mobile_number)
	{
		$user = new stdObject();
		$user->name = $name;
		$user->email = $email;
		$user->mobile = $mobile_number;
		return $user;
	}    
}