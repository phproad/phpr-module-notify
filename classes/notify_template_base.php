<?php

class Notify_Template_Base extends Phpr_Driver_Base
{
	public static $driver_folder = 'notify_templates';
	public static $driver_suffix = '_template';

	public $required_params = array();

	public function get_info()
	{
		return array(
			'name'=> 'Notification',
			'description' => 'Generic Notification',
			'code' => 'module:notification_template'
		);
	}
	
	public function get_name() 
	{
		$info = $this->get_info();
		return (isset($info['name'])) ? $info['name'] : 'Unknown';
	}

	public function get_code() 
	{
		$info = $this->get_info();
		return (isset($info['code'])) ? $info['code'] : 'unknown';
	}

	public function get_description() 
	{
		$info = $this->get_info();
		return (isset($info['description'])) ? $info['description'] : 'Unknown notification provider';
	}
	
	// Short content (eg: SMS / Email subject)
	public function get_subject() { return false; }

	// Long content (eg: Email)
	public function get_content() { return false; }

	// Short content for system staff
	public function get_internal_subject() { return false; }

	// Long content for system staff
	public function get_internal_content() { return false; }

	// Short content for system staff
	public function get_external_subject() { return false; }

	// Long content for system staff
	public function get_external_content() { return false; }    

	// Send public
	public function prepare_template($template, $params=array()) { }
	
	// Send system
	public function prepare_system_template($template, $params=array()) { }

	// Helper
	public function get_partial_path($partial_name = null)
	{
		$class_name = get_class($this);
		$class_path = File_Path::get_path_to_class($class_name);
		return $class_path.'/'.strtolower($class_name).'/'.$partial_name;
	}

	public function get_required_params()
	{
		return $this->required_params;
	}
}