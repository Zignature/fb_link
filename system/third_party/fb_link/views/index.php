<?php

	// Some JS to load the FB login and retrieve an access token
	ee()->cp->load_package_js('fb_link');
?>

<h3>App Setup</h3>
<p><?=lang('set_instructions')?></p>

<?=form_open($add_app, '', $form_hidden);?>

<?php
	ee()->table->set_template($cp_table_template);
	ee()->table->set_heading(lang('settings'), lang('value'));	
	
	ee()->table->add_row(lang('app_id'), form_input('app_id', $app_id));
	ee()->table->add_row(lang('app_secret'), form_input('app_secret', $app_secret));

    ee()->table->add_row('<button name="fb-token" type="button" class="submit" id="fb-token">Get App Token</button>', form_input('access_token', $access_token));

	echo ee()->table->generate();
?>

<?=form_submit('submit', lang('save'), 'class="submit"')?>
<?=form_close()?>