<div id="fb-root"></div>

<?php

	// Some JS to load the FB login and retrieve an access token
	$fb_js = "<script>
	window.fbAsyncInit = function() {
    FB.init({
      appId      : '".$app_id."', // App ID
      status     : true, // check login status
      cookie     : false, // enable cookies to allow the server to access the session
      xfbml      : false  // parse XFBML
    });
  };

  // Load the SDK Asynchronously
  (function(d){
     var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement('script'); js.id = id; js.async = true;
     js.src = '//connect.facebook.net/en_US/all.js';
     ref.parentNode.insertBefore(js, ref);
   }(document));
</script>

<script>
$(document).ready(function() {
   $('#fb-login').click(function() {
	   var cb = function(response) {
	   	   $.ajax({
	   	   		url: 'https://graph.facebook.com/oauth/access_token',
	   	   		data: {client_id: '".$app_id."', client_secret: '".$app_secret."', grant_type: 'fb_exchange_token', fb_exchange_token: response.authResponse.accessToken},
	   	   		success: function(data) {
			   	   var tk = data.match(/access_token=.*?&/);
			   	   $('#access_token').val(tk[0].slice(13, -1));
			   	},
			   	error: function(data) {
				   alert('An error occurred trying to retrieve an access token. Please contact the module developer.');
			   	},
		        
		    });
	   };
	   FB.login(cb, {scope: 'read_stream'});
   });
 });
</script>";

	$this->cp->add_to_head($fb_js);
?>

<p><?=lang('set_instructions')?></p>

<?=form_open($add_app, '', $form_hidden);?>

<?php
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(lang('settings'), lang('value'));	
	
	$this->table->add_row(lang('app_id'), form_input('app_id', $app_id));
	$this->table->add_row(lang('app_secret'), form_input('app_secret', $app_secret));
	
	echo $this->table->generate();
?>

<?=form_submit('submit', lang('save'), 'class="submit"')?>
<?=form_close()?>

<p>&nbsp;</p>

<?php
	$this->table->set_template($cp_table_template);
	$this->table->set_heading('App Access Token', '');
	
	$this->table->add_row('Access Token', $app_id.'|'.$app_secret);
	
	echo $this->table->generate();
?>

<p>&nbsp;</p>

<?=form_open($add_token, '', $form_hidden);?>

<?php
	$this->table->set_template($cp_table_template);
	$this->table->set_heading('User Access Token', '');
		
	$this->table->add_row('Access Token', form_input(array('name' => 'access_token', 'id' => 'access_token', 'value' => $access_token)));
	
	echo $this->table->generate();
	
	$fb = array('name' => 'fb-login', 'class' => 'submit', 'id' => 'fb-login', 'content' => 'Get Token');
	
	echo form_button($fb);
?>

<?=form_submit('submit', 'Save Token', 'class="submit"')?>
<?=form_close()?>