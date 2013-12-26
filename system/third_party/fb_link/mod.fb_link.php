<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

// require_once PATH_THIRD.'fb_link/libraries/facebook.php';

class Fb_link {

	var $return_data = '';
	
	function __construct() {
		$this->EE =& get_instance();

        // Check for DB settings then load the Facebook model if they exist
        $query = ee()->db->get('fb_link');
        if($query->num_rows() > 0) {
            $row = $query->row();
            $config = array(
                'appId'		=> $row->app_id,
                'secret'	=> $row->app_secret,
                'file_upload'   => false,
                'allowSignedRequest'    => false,
            );

            ee()->load->library('facebook', $config);
            ee()->facebook->setAccessToken($row->access_token);
       	}
    }
	
	function graph() {

		// Load Typography Class to parse data
		ee()->load->library('typography');
		ee()->typography->initialize();
		ee()->load->helper('url');
        ee()->load->helper('fb_parse_helper');
	
		$output = '';

		$params = array(
			'graph'		=>	ee()->TMPL->fetch_param('graph'),
			'query'		=>	ee()->TMPL->fetch_param('query'),
            'output'    =>  ee()->TMPL->fetch_param('output'),
		);
		
		// Set the path
		if(!empty($params['graph'])) {
			$path = $params['graph'];
		}
		
		if(!empty($params['query'])) {
			$path = 'fql?q='.str_replace(' ', '+', $params['query']);
		}
		
		try {
			// We need to set the index for the parser later
            $data = ee()->facebook->api($path);

		} catch (FacebookApiException $e) {
			error_log($e);
			return $output;
		}

        if($params['output'] == 'json') {
            return $data;
        }

		// We need to make some "rows" for the EE parser.
		$rows[] = make_rows($data);
				
		/*
		//
		// This may be handy for pagination later but for now it's just filed away.
		//
		if (preg_match("/".LD."paging".RD."(.+?)".LD.'\/'."paging".RD."/s", $this->EE->TMPL->tagdata, $page_match)) {
			// The pattern was found and we set aside the paging tagdata for later and created a copy of all the other tagdata for use
			$paging = $page_match[1];
			// Replace the {paging} variable pairs with nothing and set this aside for later.
			$tag_data = preg_replace("/".LD."paging".RD.".+?".LD.'\/'."paging".RD."/s", "", $this->EE->TMPL->tagdata);
		*/
		
		$tag_data = ee()->TMPL->tagdata;
						
		$output = ee()->TMPL->parse_variables($tag_data, $rows);
														
		return $output;
		
	}
}

/* End of file mod.fb_link.php */
/* Location: ./system/expressionengine/third_party/fb_link/mod.fb_link.php */