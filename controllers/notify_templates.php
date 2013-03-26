<?

class Notify_Templates extends Admin_Settings_Controller
{
	public $implement = 'Db_ListBehavior, Db_FormBehavior';
	public $list_model_class = 'Notify_Template';
	public $list_record_url = null;

	public $form_model_class = 'Notify_Template';
	public $form_not_found_message = 'Template not found';
	public $form_create_context_name = 'create';
	public $form_redirect = null;
	public $form_create_title = 'New Notification Template';
	public $form_edit_title = 'Edit Notification Template';
	public $form_flash_id = 'form_flash';

	public $list_search_enabled = true;
	public $list_search_fields = array('@code', '@subject', '@description');
	public $list_search_prompt = 'find templates by code, subject or description';

	public $form_edit_save_flash = 'Notification template has been successfully saved';
	public $form_create_save_flash = 'Notification template has been successfully added';
	public $form_edit_delete_flash = 'Notification template has been successfully deleted';

	public $global_handlers = array('on_test');

	public function __construct()
	{
		parent::__construct();
		$this->app_menu = 'system';
		$this->app_module_name = 'System';

		$this->list_record_url = url('/notify/templates/edit/');
		$this->form_redirect = url('/notify/templates/');
	}
	
	public function index()
	{
		try
		{
			Notify_Manager::auto_create_templates();
			$this->app_page_title = 'Notification Templates';
		}
		catch (Exception $ex)
		{
			$this->handle_page_error($ex);
		}
	}

	protected function on_test($id)
	{
		try
		{
			$obj = strlen($id) ? $this->form_find_model_object($id) : $this->form_create_model_object();
			$obj->validate_data(post($this->form_model_class, array()));
			$obj->add_recipient($this->active_user);
			$obj->add_recipient($this->active_user, true);
			$obj->send();
			
			echo Admin_Html::flash_message('The test message has been successfully sent.');
		}
		catch (Exception $ex)
		{
			Phpr::$response->ajax_report_exception($ex, true, true);
		}
	}
}

