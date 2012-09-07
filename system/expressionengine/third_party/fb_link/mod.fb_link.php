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
		$parsed_row = '';
		
		$params = array(
			'graph'		=>	$this->EE->TMPL->fetch_param('graph'),
			'limit'		=>	$this->EE->TMPL->fetch_param('limit'),
			'fields'	=>	$this->EE->TMPL->fetch_param('fields'),
		);
		
		/*
		// LEGACY CODE
		//
		// This was necessary before a major update to the Graph API.  That made life easier..
		//
		if(empty($params['limit'])) {
			unset($params['limit']);
		}
		
		if(empty($params['fields'])) {
			unset($params['fields']);
		}
		*/
		
		// Set the path
		$path = $params['graph'];
		unset($params['graph']);
		
		try {
			// We need to set the index for the parser later
			$data = $this->EE->base_facebook->graph($path);
		} catch (FacebookApiException $e) {
			error_log($e);
			return $output;
		}
				
		// We need to make some "rows" for the EE parser.
		$rows[] = $this->make_rows($data);

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
		
		$tag_data = $this->EE->TMPL->tagdata;
				
		// Now we parse by row so we can do some formatting for the data and utilize conditionals.
		/*foreach($rows as $item => $row) {
				
			// Format the message HTML is one exists				
			if(isset($row['message'])) {
				$row['message'] = auto_link($this->EE->typography->parse_type($row['message'], array('text_format' => 'lite', 'html_format' => 'safe', 'auto_links' => 'y')));
			}
			
			// Make some date conversions to utilize the built-in EE date format= functionality.  In the future this may you pattern matching to be more robust.
			$row['created_time'] = strtotime($row['created_time']);
			$row['updated_time'] = strtotime($row['updated_time']);
					
			// Let's create a special post_id variable.  This is for building links to specific posts.
			$id = explode('_', $row['id']);
			$row['post_id'] = $id[1];
			
			// Set our conditionals
			$cond				=	$row;
			$cond['likes']		=	(isset($row['likes'])) ? 'TRUE' : 'FALSE';
			$cond['comments']	=	($row['comments'][0]['count'] > 0) ? 'TRUE' : 'FALSE';
			
			$tagdata = $this->EE->functions->prep_conditionals($tag_data, $cond);
			$output .= $this->EE->TMPL->parse_variables_row($tagdata, $row);
			
		}*/
		
		$output = $this->EE->TMPL->parse_variables($tag_data, $rows);
												
		return $output;
		
	}
	
	// Create rows for the EE parser.  Some FB data is an array that is not indexed.  For example the from data is an associative array.  THe EE parser needs a "row" to work with.  This function will recursively work through the data and if an array is not indexed will create the index.  It's a beast of a function but necessary for now and should be flexible enough to cope with FB structure changes.
	public function make_rows($array) {
		$var = array();
		
		// Let work through each item to catch arrays and format them for parsing
		foreach($array as $k => $v) {
		
			// Not an array so pass over to the formatting function.
			if(!is_array($v)) {
				$var[$k] = $this->format($k,$v);
				
			// Is an array so hold up we need some more work.
			} elseif(is_array($v)){
				// If it's not numeric we need to create that.
				
				if(!is_numeric($k) && !is_numeric(array_shift(array_keys($v)))) {
				
					// We need to rename the "row" named data based on it's parent or else the parser gets confused.
					if(isset($v['data'])) {
						$v[$k.':data'] = $v['data'];
						unset($v['data']);						
					}
					
					$var[$k][0] = $this->make_rows($v);
				} else {
					$var[$k] = $this->make_rows($v);
				}
			}
		}
		
		return $var;
	}

	// Function to handle the formatting of certain fields.
	public function format($k, $v) {		
		if($k == 'message') {
			$v = auto_link($this->EE->typography->parse_type($v, array('text_format' => 'lite', 'html_format' => 'safe', 'auto_links' => 'y')));
		}
		
		if(($k == 'created_time') || ($k == 'updated_time')) {
			$v = strtotime($v);
		}
		
		return $v;
	}
}

/* End of file mod.fb_link.php */
/* Location: ./system/expressionengine/third_party/fb_feed/mod.fb_link.php */