<?php
	// Some JS to load the FB login and retrieve an access token
	ee()->cp->load_package_js('fb_link');
?>

<?=form_open($add_app, '', $form_hidden);?>

<?php
	ee()->table->set_template($cp_table_template);
    ee()->table->set_caption(lang('app_settings'));

	//ee()->table->set_heading(lang('settings'), lang('value'));
	
	ee()->table->add_row(lang('app_id'), form_input('app_id', $app_id));
	ee()->table->add_row(lang('app_secret'), form_input('app_secret', $app_secret));

	echo ee()->table->generate();
?>

<?=form_submit('submit', lang('save_settings'), 'class="submit"')?>
<?=form_close()?>

<br />

<?php
    if(empty($tokens) && !empty($app_id) && !empty($app_secret)) {

        ee()->table->set_template($cp_table_template);
        ee()->table->set_caption(lang('access_tokens'));

        ee()->table->add_row('<button name="fb-authorize" type="button" class="submit" id="fb-authorize">Get Access Tokens</button>');

        echo ee()->table->generate();

    } elseif (!empty($tokens)) {
        echo form_open($add_token, '', $form_hidden);

        $cp_table_template['table_open'] = '<table class="mainTable" border="0" cellspacing="0" cellpadding="0" style="table-layout:fixed; word-wrap: break-word;">';

        ee()->table->set_template($cp_table_template);
        ee()->table->set_caption(lang('access_tokens') . ' retrieved by ' . $created_by . ' on ' . date("M d, Y", $created_date));

        ee()->table->set_heading(array(
            array('data' => lang('selected'), 'width' => '8%'),
            array('data' => lang('name'), 'width' => '15%'),
            array('data' => lang('token'), 'width' => '77%')
        ));

        foreach($tokens as $token) {
            $radio_status = ($default_token == $token['token']) ? TRUE : FALSE;
            ee()->table->add_row(array('data' => form_radio('default_token', $token['token'], $radio_status), 'align' => 'center'), $token['name'], $token['token']);
        };

        echo ee()->table->generate();

        echo form_submit('submit', lang('save_tokens'), 'class="submit"') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . $clear_token . '">' . lang('clear_tokens') . '</a>';
        echo form_close();
    }
?>

