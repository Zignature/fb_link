<p><?=lang('set_instructions')?></p>

<?=form_open($form_action, '', $form_hidden);?>

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
<h3>Facebook Graph Explorer</h3>
<p>Every item that is exposed in the Facebook Graph API is available to use in your templates. Before building your template it is recommended to view what is available in the Graph using the <strong><a href="http://developers.facebook.com/tools/explorer">Graph Explorer Tool</a></strong>. When using the Graph Explorer be sure to use your apps access token to only see data open to use in EE tags.</p>
<?php if(isset($app_id) && isset($app_secret)):?>
<p>Your app access token is <strong><?=$app_id?>|<?=$app_secret?></strong>.</p>
<?php endif;?>
<p>More instructions and examples can be found at <a href="http://www.hicksondesign.com">Hickson Design</a>.</p>