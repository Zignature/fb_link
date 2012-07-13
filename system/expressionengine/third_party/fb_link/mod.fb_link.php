<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

class Fb_link {

	var $return_data = '';
	
	function __construct() {
		$this->EE =& get_instance();
	}
	
	function auth_token() {
		
		// Start with a query to get info
		$query = $this->EE->db->get('fb_link');
		
		// Check for the app data and continue
		if ($query->num_rows() > 0) {
			$app = $query->row_array();
			
			// Gets OAuth token	
			$auth = "https://graph.facebook.com/oauth/access_token?client_id=".$app['app_id']."&client_secret=".$app['app_secret']."&grant_type=client_credentials";
     	     	
			$get_auth = curl_init($auth);
			curl_setopt ($get_auth, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt ($get_auth, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt ($get_auth, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; fr; rv:1.9.1.1) Gecko/20090715 Firefox/3.5.1');
			$access_token = curl_exec($get_auth);
			curl_close($get_auth);
			
			return $access_token;
		}
	}
	
	function wall() {
	
		$output = '';
		
		// Parameters
		$pageId = $this->EE->TMPL->fetch_param('page');
    	$limit = $this->EE->TMPL->fetch_param('limit', 5);
    	$owner = $this->EE->TMPL->fetch_param('owner_only', 'no');
		
		$access_token = $this->auth_token();
     	
     	if ($access_token != NULL) {    	     				
			// Builds the data query
			$pageUrl = 'https://graph.facebook.com/'.$pageId.'/';
			if ($owner == 'yes') {
				$pageUrl .= 'posts?limit='.$limit.'&'.$access_token;
			} else {
				$pageUrl .= 'feed?limit='.$limit.'&'.$access_token;
			}

			// Fetch the data from Facebook
			$fetch = curl_init();
			curl_setopt ($fetch, CURLOPT_URL, "$pageUrl");
			curl_setopt ($fetch, CURLOPT_RETURNTRANSFER, 1);

			$page = curl_exec($fetch);
			curl_close($fetch);
		
			// Write the data to an array
			$data = json_decode($page);
				
			// Load Typography Class to parse data
			$this->EE->load->library('typography');
			$this->EE->typography->initialize();
			$this->EE->load->helper('url');
						
			// Begin to work through each FB post
			foreach ($data->data as $row) {
			
				// Array of standard keys/values in Graph
				$feed_row = array(
					'from_name' => $row->from->name,
					'from_id' => $row->from->id,
					'profile' => 'http://www.facebook.com/profile.php?id='."{$row->from->id}",
					'profile_pic' => 'https://graph.facebook.com/'."{$row->from->id}".'/picture',
					'type' => $row->type,
					'created' => strtotime($row->created_time),
					'updated' => strtotime($row->updated_time),
				);
				
				// Get just post ID for creating links
				$id = explode("_", $row->id);
				$feed_row['id'] = $id[1];
				$feed_row['page'] = $id[0];
				
				// Works through array items that may or may not exist in each post.  If it exists the value is set for use if it does not exist then the value is set to NULL.
				$key_check = array('message','link','description','picture','source','name', 'object_id', 'caption');
			
				foreach ($key_check as $key => $value) {
					if (array_key_exists($value, $row)) {
						$feed_row[$value] = $this->EE->typography->format_characters($row->$value);
					} else {
						$feed_row[$value] = '';
					}
				}
			
				// Format message for links
				if (isset($row->message)) {
					$feed_row['message'] = auto_link($this->EE->typography->parse_type($row->message, array('text_format' => 'lite', 'html_format' => 'safe', 'auto_links' => 'n')));
				}
					
				// Check to see if there are any likes or comments	
				if (isset($row->likes->count)) {
					$feed_row['likes'] = $row->likes->count;
				} else {
					$feed_row['likes'] = '';
				}
				
				if (isset($row->comments->count)) {
					$feed_row['comments'] = $row->comments->count;
				} else {
					$feed_row['comments'] = '';
				}

				// Change the Graph name for photos/videos to something more meaningful.
				$feed_row['object_name'] = $feed_row['name'];
				unset($feed_row['name']);
						
				// Handle conditionals and parse data	
				$tagdata = $this->EE->functions->prep_conditionals($this->EE->TMPL->tagdata, $feed_row);
				$output .= $this->EE->TMPL->parse_variables_row($tagdata, $feed_row);
			}
		}
			
		return $output;

	}
		
	function album() {
	
		$output = '';
		
		// Paramters
		$owner = $this->EE->TMPL->fetch_param('owner');
		$album = $this->EE->TMPL->fetch_param('album');
		$limit = $this->EE->TMPL->fetch_param('limit', 10);

		$access_token = $this->auth_token();

		if ($access_token != NULL) {
		
			// Build query, fetch, and decode into array
			$url = 'https://api.facebook.com/method/fql.query?'.$access_token.'&format=json&query=';
			$url .= urlencode('SELECT src_small,src_big,link,caption,created FROM photo WHERE aid IN (SELECT aid FROM album WHERE owner = '.$owner.' AND name = \''.$album.'\') LIMIT '.$limit.'');
			$fetch = curl_init();
			curl_setopt ($fetch, CURLOPT_URL, "$url");
			curl_setopt ($fetch, CURLOPT_RETURNTRANSFER, 1);

			$queryFetch = curl_exec($fetch);
			curl_close($fetch);
				
			$gallery = json_decode($queryFetch);
						
			// Array of keys/values in Graph
			foreach ($gallery as $pic) {
				$feed_row = array(
					'img_small' => htmlspecialchars($pic->src_small),
					'img_big' => htmlspecialchars($pic->src_big),
					'img_link' => htmlspecialchars($pic->link),
					'caption' => $pic->caption,
					'created' => strtotime($pic->created)
				);
			
			$tagdata = $this->EE->functions->prep_conditionals($this->EE->TMPL->tagdata, $feed_row);
			$output .= $this->EE->TMPL->parse_variables_row($tagdata, $feed_row);
		
			}
		}
					
		return $output;	
	}
	
}

/* End of file mod.fb_link.php */
/* Location: ./system/expressionengine/third_party/fb_feed/mod.fb_link.php */