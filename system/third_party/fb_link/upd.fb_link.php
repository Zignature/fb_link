<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fb_link_upd {

	var $version = '3.0a';
	
	function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Install
	 *
	 * @access	public
	 * @return	bool
	 */

	function install()
	{
		$this->EE->load->dbforge();
		
		$mod_data=array(
			'module_name' => 'Fb_link',
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);
		
		$this->EE->db->insert('modules', $mod_data);
		
		$fields = array(
			'id'			=> array('type'=>'INT','constrain'=>'2','unsigned'=>TRUE,'auto_increment'=>TRUE),
			'app_id'		=> array('type'=>'VARCHAR','constraint'=>'20'),
			'app_secret'	=> array('type'=>'VARCHAR','constraint'=>'40'),
			'access_token'	=> array('type' => 'VARCHAR','constraint'=>'200'),
			);
		
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->create_table('fb_link');
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstall
	 *
	 * @access	public
	 * @return	bool
	 */
	 
	function uninstall()
	{		
		$this->EE->load->dbforge();

		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Fb_link'));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Fb_link');
		$this->EE->db->delete('modules');
		
		$this->EE->dbforge->drop_table('fb_link');
				
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Update
	 *
	 * @access	public
	 * @return	bool
	 */	
	 
	function update($current = '')
	{
		if (version_compare($current, '3.0a', '=')) {
			return FALSE;
		}
		
		$this->EE->load->dbforge();
		
		if (version_compare($current, '2.1', '<')) {			
			$field = array(
				'access_token' => array('type' => 'VARCHAR','constraint'=>'200')
			);
			
			$this->EE->dbforge->add_column('fb_link', $field);
		}

		return TRUE;

	}
	
}