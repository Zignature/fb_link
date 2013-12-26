<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fb_link_mcp {
	
	function __construct()
	{
		$this->EE =& get_instance();
		
		$this->_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=fb_link';
		$this->_form_url = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=fb_link';
		
		// Check for DB settings then load the Facebook model if they exist
		$query = ee()->db->get('fb_link');
		if($query->num_rows() > 0) {
			$row = $query->row();
			$config = array(
				'appId'		=> $row->app_id,
				'secret'	=> $row->app_secret,
			);
			ee()->load->library('facebook', $config);
		}
	}
	
	function index() {
	
		// Load necessary helpers and libraries
		ee()->load->library('table');
		ee()->load->helper('form');

		// Set page title
		ee()->view->cp_page_title = lang('cp_title');
		
		$vars['id'] = NULL;
		$vars['app_id'] = NULL;
		$vars['app_secret'] = NULL;
		$vars['access_token'] = NULL;
		$vars['add_app'] = $this->_form_url.AMP.'method=add_app';
		$vars['form_hidden'] = NULL;
						
		$query = ee()->db->get('fb_link');
		
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $app_data) {
				$vars['id'] = $app_data['id'];
				$vars['form_hidden'] = array('id' => $app_data['id']);
				$vars['app_id'] = $app_data['app_id'];
				$vars['app_secret'] = $app_data['app_secret'];
				$vars['access_token'] = $app_data['access_token'];
			}				
		}
		
		return ee()->load->view('index', $vars, TRUE);
	}
	 	
	function add_app() {
		// Load necessary helpers and libraries
		ee()->load->helper('form');
		
		$data = array(
			'id'			=> ee()->input->get_post('id'),
			'app_id'		=> ee()->input->post('app_id'),
			'app_secret'	=> ee()->input->post('app_secret'),
            'access_token'  => ee()->input->post('access_token'),
		);

		if ($data['id'] != NULL) {
			ee()->db->update('fb_link', $data);
		} else {
			ee()->db->insert('fb_link', $data);
		}
				
		ee()->session->set_flashdata('message_success', lang('app_updated'));
		
		ee()->functions->redirect($this->_base_url);
	}

}

// END CLASS

/* End of file mcp.fb_link.php */
/* Location: ./system/expressionengine/third_party/fb_link/mcp.fb_link.php */