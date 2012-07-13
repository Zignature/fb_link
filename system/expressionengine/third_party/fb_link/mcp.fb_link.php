<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fb_link_mcp {
	
	function __construct()
	{
		$this->EE =& get_instance();
		
		$this->_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=fb_link';
		$this->_form_url = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=fb_link';
	}
	
	function index() {
	
		// Load necessary helpers and libraries
		$this->EE->load->helper('form');

		// Set page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('cp_title'));
		
		$vars['id'] = NULL;
		$vars['app_id'] = NULL;
		$vars['app_secret'] = NULL;
		$vars['form_action'] = $this->_form_url.AMP.'method=add_app';
		$vars['form_hidden'] = NULL;
						
		$query = $this->EE->db->get('fb_link');
		
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $app_data) {
				$vars['id'] = $app_data['id'];
				$vars['form_hidden'] = array('id' => $app_data['id']);
				$vars['app_id'] = $app_data['app_id'];
				$vars['app_secret'] = $app_data['app_secret'];
			}				
		}
				
		return $this->EE->load->view('index', $vars, TRUE);
	}
	
	function add_app() {
		// Load necessary helpers and libraries
		$this->EE->load->helper('form');
		
		$data = array(
			'id'			=> $this->EE->input->get_post('id'),
			'app_id'		=> $this->EE->input->post('app_id'),
			'app_secret'	=> $this->EE->input->post('app_secret')
		);

		if ($data['id'] != NULL) {
			$this->EE->db->update('fb_link', $data);
		} else {
			$this->EE->db->insert('fb_link', $data);
		}
				
		$this->EE->session->set_flashdata('message_success', lang('app_updated'));
		
		$this->EE->functions->redirect($this->_base_url);
	}
	
}

// END CLASS

/* End of file mcp.fb_link.php */
/* Location: ./system/expressionengine/third_party/fb_feed/mcp.fb_link.php */