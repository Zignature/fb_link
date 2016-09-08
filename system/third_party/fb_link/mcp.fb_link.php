<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include_once dirname(__FILE__).'/config.php';

// autoload Facebook
require __DIR__ . '/vendor/autoload.php';
use Facebook\Facebook;

class Fb_link_mcp {
	
	function __construct()
	{
		// Get the DB settings
		$query = ee()->db->get('fb_link');
		if($query->num_rows() > 0) {
			$row = $query->row();
			$this->settings = array(
                'id'            => $row->id,
				'app_id'		=> $row->app_id,
				'app_secret'	=> $row->app_secret,
                'default_token' => $row->default_token,
                'tokens'        => $row->tokens,
                'created_by'    => $row->created_by,
                'created_date'  => $row->created_date
			);
		}
	}

    /**
     * Module settings
     *
     * Users authenticate app- if successful display a "Get Tokens" button - if not display the error (all via Javascript)
     * User clicks "Get Tokens" to run the get_tokens methods (which gets both page and app tokens and stores them) and the page refreshes with the tokens displayed in a radio button select form.
     *
     * @return mixed
     */
	function index() {
	
		// Load necessary helpers and libraries
		ee()->load->library('table');
		ee()->load->helper('form');

		// Set page title
		ee()->view->cp_page_title = lang('cp_title');
		
		$vars['id'] = NULL;
		$vars['app_id'] = NULL;
		$vars['app_secret'] = NULL;
		$vars['add_app'] = ee('CP/URL', 'fb_link/add_app');
        $vars['add_token'] = ee('CP/URL', 'fb_link/add_token');
        $vars['clear_token'] = ee('CP/URL', 'fb_link/clear_tokens');
		$vars['form_hidden'] = NULL;

		if (!empty($this->settings)) {
            $vars['id'] = $this->settings['id'];
            $vars['form_hidden'] = array('id' => $this->settings['id']);
            $vars['app_id'] = $this->settings['app_id'];
            $vars['app_secret'] = $this->settings['app_secret'];
            $vars['default_token'] = $this->settings['default_token'];
            if(!empty($this->settings['tokens'])) {
                $vars['tokens'] = unserialize($this->settings['tokens']);
            }
            $vars['created_by'] = $this->settings['created_by'];
            $vars['created_date'] = $this->settings['created_date'];

            // Load Facebook SDK if the App ID has been set
            $fb_sdk = "<script>window.fbAsyncInit = function() {FB.init({appId : '" . $this->settings['app_id'] . "', xfbml : false, cookie : true, version : '" . GRAPH_VERSION . "'});};(function(d, s, id){var js, fjs = d.getElementsByTagName(s)[0];if (d.getElementById(id)) {return;}js = d.createElement(s); js.id = id;js.src = \"//connect.facebook.net/en_US/sdk.js\";fjs.parentNode.insertBefore(js, fjs);}(document, 'script', 'facebook-jssdk'));
</script>";
            $fb_token_link = "<script>function token_link(){return '" . html_entity_decode(cp_url('addons_modules/show_module_cp', array('module'=>'fb_link','method'=>'get_tokens'))) . "';}</script>";

            ee()->cp->add_to_head($fb_sdk);
            ee()->cp->add_to_head($fb_token_link);
		}

		return ee()->load->view('index', $vars, TRUE);
	}


    /**
     * Save basic app settings
     *
     * Saves the app id and secret from Facebook. We do this first since
     * everything else depends on these.
     */
	function add_app() {
		// Load necessary helpers and libraries
		ee()->load->helper('form');
		
		$data = array(
			'app_id'		=> ee()->input->post('app_id'),
			'app_secret'	=> ee()->input->post('app_secret'),
		);

        $fbid = ee()->db->select('id')->get('fb_link');

		if ($fbid->num_rows() > 0) {
            $id = $fbid->row_array();

            // If settings changed then clear the old tokens
            if($data['app_id'] != $this->settings['app_id'] || $data['app_secret'] != $this->settings['app_secret']) {
                $data['default_token'] = NULL;
                $data['tokens'] = NULL;
            }
			ee()->db->update('fb_link', $data, $id);
		} else {
			ee()->db->insert('fb_link', $data);
		}
				
		ee()->session->set_flashdata('message_success', lang('app_updated'));
		
		ee()->functions->redirect(ee('CP/URL', 'fb_link'));
	}


    /**
     * Save app token
     *
     * The default token is selected from the available list.
     *
     */
    function add_token() {
        // Load necessary helpers and libraries
		ee()->load->helper('form');

        $data = array(
            'default_token' => ee()->input->post("default_token")
        );
        ee()->db->update('fb_link', $data, array('id' => $this->settings['id']));

        ee()->session->set_flashdata('message_success', lang('app_updated'));

        ee()->functions->redirect(ee('CP/URL', 'fb_link'));
    }


    /**
     * Get both app and page tokens and store them in the DB for reference.
     * Old tokens are always replaced when this is called.
     *
     * @throws Exception
     */
    function get_tokens() {

        ee()->load->library('logger');

        $fb = new Facebook(array(
            'app_id' => $this->settings['app_id'],
            'app_secret' => $this->settings['app_secret'],
            'default_graph_version' => GRAPH_VERSION
        ));

        // Get app token
        $app_token = array(
            'type'  => 'app',
            'token' =>  $fb->getApp()->getAccessToken()->getValue(),
            'name'  =>  'App'
        );

        // Get user token
        $user_token = $fb->getJavaScriptHelper()->getAccessToken();

        // First we need a long-lived user token
        if (!empty($user_token)) {
            // Get a long-lived user token
            try {
                $longUserToken = $fb->getOAuth2Client()->getLongLivedAccessToken($user_token);
            } catch (\Exception $ex) {
                // Handle a token error
                ee()->logger->developer('FB Link Error: ' . $ex->getMessage());
            }
        }

        // Now we can get page tokens
        if (!empty($longUserToken)) {
            $fb->setDefaultAccessToken($longUserToken->getValue());
            try {
                $pages = $fb->request('GET', '/me/accounts');
                $user = $fb->request('GET', '/me');
                $responses = $fb->sendBatchRequest(array($pages, $user));
            } catch (FacebookRequestException $ex) {
                // Facebook request error
                ee()->logger->developer('FB Link Error: ' . $ex->getMessage());
            } catch (\Exception $ex) {
                // Some other error
                ee()->logger->developer('FB Link Error: ' . $ex->getMessage());
            }
        }

        // Work with our tokens
        foreach($responses as $key => $response) {
            if ($response->isError()) {
                $e = $response->getThrownException();
                throw new Exception($e->getMessage(), $e->getCode());
            } else {
                $arr = $response->getDecodedBody();
                // If it's 'data' then it's tokens
                if(isset($arr['data'])) {
                    $stored_tokens = array();
                    foreach ($arr['data'] as $page) {
                        $stored_tokens[$key]['type'] = 'page';
                        $stored_tokens[$key]['token'] = $page['access_token'];
                        $stored_tokens[$key]['name'] = $page['name'];
                    }
                    $stored_tokens[] = $app_token;
                } else if (isset($arr['name'])) {
                    $username = $arr['name'];
                }
            }
        }

        // Store everything in the DB. Replacing old data.
        $data = array(
            'tokens'     => serialize($stored_tokens),
            'created_by'    => $username,
            'created_date'  => time()
        );
        ee()->db->update('fb_link', $data, array('id' => $this->settings['id']));
    }

    
    /**
     * Clear all tokens
     *
     * Delete tokens from the DB
     */
    function clear_tokens() {
        $data = array(
            'default_token' => NULL,
            'tokens' => NULL
        );
        ee()->db->update('fb_link', $data, array('id' => $this->settings['id']));

        ee()->functions->redirect(ee('CP/URL', 'fb_link'));

    }

}

// END CLASS

/* End of file mcp.fb_link.php */
/* Location: ./system/expressionengine/third_party/fb_link/mcp.fb_link.php */