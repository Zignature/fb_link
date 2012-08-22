<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

// require_once PATH_THIRD.'fb_link/libraries/facebook.php';

class Fb_link {

	var $return_data = '';
	
	function __construct() {
		$this->EE =& get_instance();
		
		$this->EE->load->model('base_facebook');
	}
	
	function graph() {
	
		// Load Typography Class to parse data
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->EE->load->helper('url');
	
		$output = '';
		
		$params = array(
			'graph'		=>	$this->EE->TMPL->fetch_param('graph'),
			'limit'		=>	$this->EE->TMPL->fetch_param('limit'),
			'fields'	=>	$this->EE->TMPL->fetch_param('fields'),
		);
		
		if(empty($params['limit'])) {
			unset($params['limit']);
		}
		
		if(empty($params['fields'])) {
			unset($params['fields']);
		}
		
		// Set the path
		$path = $params['graph'];
		unset($params['graph']);

		try {
			// We need to set the index for the parser later
			$data = $this->EE->base_facebook->graph($path, $params);
		} catch (FacebookApiException $e) {
			error_log($e);
			return $output;
		}
		
		$tagdata = $this->EE->TMPL->tagdata;
		
		// We need to make some "rows" for the EE parser.
		$vars[0] = $this->make_rows($data);
		print_r($vars);
		
		// Do some formatting for the data
		foreach($vars[0]['data'] as $item => $row) {

			// Set our conditionals
			$cond				= $row;
			$cond['likes']		= (isset($row['likes'])) ? 'TRUE' : 'FALSE';
			
			// Get the tagdata for each post and run conditionals on them.
			// $tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
			
			/*					
			if(isset($row['message'])) {
				$row['message'] = auto_link($this->EE->typography->parse_type($row['message'], array('text_format' => 'lite', 'html_format' => 'safe', 'auto_links' => 'y')));
			}	
						
			// Let's create a special post_id variable.  This is for building links to specific posts.
			if($item == 'data') {
				$id = explode('_', $row['id']);
				$row['post_id'] = $id[1];
			}
			*/
		}
		
		$output = $this->EE->TMPL->parse_variables($tagdata, $vars);

		return $output;
		
	}
	
	// Create rows for the EE parser.  Some FB data is an array that is not indexed.  For example the from data is an associative array.  THe EE parser needs a "row" to work with.  This function will recursively work through the data and if an array is not indexed will create the index.  It's a beast of a function but necessary for now and should be flexible enough to cope with FB structure changes.
	public function make_rows($array) {
		$var = array();
				
		foreach($array as $k => $v) {
			if(!is_array($v)) {
				$var[$k] = $v;
			} elseif(is_array($v)){
				if(!is_numeric($k) && !is_numeric(array_shift(array_keys($v)))) {
					$var[$k][0] = $this->make_rows($v);
				} else {
					$var[$k] = $this->make_rows($v);
				}
			}
		}
		
		return $var;
	}
}

/* End of file mod.fb_link.php */
/* Location: ./system/expressionengine/third_party/fb_feed/mod.fb_link.php */