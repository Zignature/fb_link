<p><?=lang('instructions')?></p>

<?=form_open($form_action, '', $form_hidden);?>

<div style="margin-bottom:10px; margin-top:10px;">
<?=form_label(lang('app_id'), 'app_id')?>
<?=form_input('app_id', $app_id)?>
</div>

<div style="margin-bottom:10px;">
<?=form_label(lang('app_secret'), 'app_secret')?>
<?=form_input('app_secret', $app_secret)?>
</div>

<?=form_submit('submit', lang('save'), 'class="submit"')?>
<?=form_close()?>