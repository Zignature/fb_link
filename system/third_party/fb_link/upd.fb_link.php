<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fb_link_upd {

	var $version = '4.0';
	
	function __construct()
	{
		//$this->EE =& get_instance();
	}
	

	/**
	 * Module Install
	 *
	 * @access	public
	 * @return	bool
	 */

	function install()
	{
        ee()->load->dbforge();
		
		$mod_data=array(
			'module_name' => 'Fb_link',
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);

        ee()->db->insert('modules', $mod_data);
		
		$fields = array(
			'id'			=> array('type'=>'INT','constraint'=>'2','unsigned'=>TRUE,'auto_increment'=>TRUE),
			'app_id'		=> array('type' => 'VARCHAR','constraint' => '20'),
			'app_secret'	=> array('type' => 'VARCHAR','constraint' => '40'),
			'default_token'	=> array('type' => 'VARCHAR','constraint' => '200'),
            'tokens'        => array('type' => 'TEXT'),
            'created_by'    => array('type' => 'VARCHAR', 'constraint' => '255'),
            'created_date'  => array('type' => 'INT', 'constraint' => '10')
			);

        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('id', TRUE);
        ee()->dbforge->create_table('fb_link');
		
		return TRUE;
	}
	

	/**
	 * Module Uninstall
	 *
	 * @access	public
	 * @return	bool
	 */
	 
	function uninstall()
	{		
		ee()->load->dbforge();

		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Fb_link'));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', 'Fb_link');
        ee()->db->delete('modules');

        ee()->dbforge->drop_table('fb_link');
				
		return TRUE;
	}
	

	/**
	 * Module Update
	 *
	 * @access	public
	 * @return	bool
	 */	
	 
	function update($current = '')
	{
		if (version_compare($current, '3.5a', '=')) {
			return FALSE;
		}
		
		ee()->load->dbforge();
		
		if (version_compare($current, '2.1', '<')) {			
			$field = array(
				'access_token' => array('type' => 'VARCHAR','constraint' => '200')
			);
			
			ee()->dbforge->add_column('fb_link', $field);
		}

        if (version_compare($current, '3.5a', '<')) {
            $field = array(
                'tokens'    => array('type' => 'TEXT'),
                'created_by'  => array('type' => 'VARCHAR', 'constraint' => '255'),
                'created_date'=> array('type' => 'INT', 'constraint' => '10')
            );
            ee()->dbforge->add_column('fb_link', $field);

            $field = array(
                'access_token'  => array(
                    'name'  => 'default_token',
                    'type'  => 'TEXT'
                )
            );
            ee()->dbforge->modify_column('fb_link', $field);
        }

		return TRUE;

	}
	
}