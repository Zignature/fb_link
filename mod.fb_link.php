<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

// include config file
include_once dirname(__FILE__).'/config.php';

// autoload Facebook
require __DIR__ . '/vendor/autoload.php';
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class Fb_link {


	function __construct() {

        // Get the DB settings
        $query = ee()->db->get('fb_link');
        if($query->num_rows() > 0) {
            $row = $query->row();
            $this->settings = array(
                'id'            => $row->id,
                'app_id'		=> $row->app_id,
                'app_secret'	=> $row->app_secret,
                'default_token' => $row->default_token,
                'tokens'        => $row->tokens
            );
        }
    }
    

    /**
     * Primary function
     *
     * @return string
     */
	function graph() {

		// Load Typography Class to parse data
		ee()->load->library('typography');
		ee()->typography->initialize();
		ee()->load->helper('url');
        ee()->load->helper('fb_parse_helper');
	
		$output = '';

		$params = array(
            'token'     =>  ee()->TMPL->fetch_param('token', $this->settings['default_token']),
            'request'	=>	ee()->TMPL->fetch_param('request'),
            'limit'     =>  ee()->TMPL->fetch_param('limit'),
            'json'    	=>  ee()->TMPL->fetch_param('json', 'no')
		);

        $fb = new Facebook(array(
            'app_id' => $this->settings['app_id'],
            'app_secret' => $this->settings['app_secret'],
            'default_graph_version' => GRAPH_VERSION
        ));

		try {
            // We need to set the index for the parser later
            $response = $fb->get($params['request'], $params['token']);
        } catch (FacebookResponseException $e) {
            ee()->load->library('logger');
            ee()->logger->developer('FB Graph Tag Error: ' . $e->getMessage());
            // Return empty output
            return $output;
		} catch (FacebookSDKException $e) {
            // Log error
			ee()->load->library('logger');
            ee()->logger->developer('FB Graph Tag Error: ' . $e->getMessage());
            // Return empty output
            return $output;
		}

		// We need to make some "rows" for the EE parser.
		$rows[] = make_rows($response->getDecodedBody());

        if (!empty($params['limit'])) {
            array_slice($rows[0], 0, $params['limit']);
        }

        if($params['json'] == 'yes') {
            // Output our JSON here
            $jsonResponse = json_encode($rows[0]);
            ee()->output->send_ajax_response($jsonResponse);
        }

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
/* Location: ./system/user/addons/fb_link/mod.fb_link.php */