<?php

/**
 * Init plugin options page
 * @package fawaterk
 */
add_action('admin_init', function () {
	register_setting('fawaterk_plugin_options', 'fawaterk_plugin_options', 'theme_options_validate');
});
add_action('admin_menu', function () {
	add_menu_page(__('Fawaterk Settings', 'fawaterk'), __('Fawaterk Settings', 'fawaterk'), 'edit_theme_options', 'fawaterk_settings', 'fawaterk_plugin_options_page');
});

/**
 * Create the options page
 */
function fawaterk_plugin_options_page()
{

	/**
	 * Create arrays for our select and radio options
	 */
	$fawaterk_options = [
		'enabled'               => array(
			'title'   => __('Enable/Disable', 'fawaterk'),
			'type'    => 'checkbox',
			'label'   => __('Enable Fawaterak', 'fawaterk'),
			'description' => __('Enable or disable fawaterk', 'fawaterk'),
			'default' => 'no',
		),
		'title'                 => array(
			'title'       => __('Title', 'fawaterk'),
			'type'        => 'text',
			'description' => __('This controls the title which the user sees during checkout.', 'fawaterk'),
			'default'     => __('Fawaterak', 'fawaterk'),
			'desc_tip'    => true,
		),
		'description'           => array(
			'title'       => __('Description', 'fawaterk'),
			'type'        => 'text',
			'desc_tip'    => true,
			'description' => __('This controls the description which the user sees during checkout.', 'fawaterk'),
			'default'     => __("Pay via Fawaterak: You can pay with Credit/Debit cards or via Fawry and mobile wallets.", 'fawaterk'),
		),
		'mobile_wallet_title'                 => array(
			'title'       => __('Mobile Wallet Title', 'fawaterk'),
			'type'        => 'text',
			'description' => __('This controls mobile wallet payment title which the user sees during checkout.', 'fawaterk'),
			'default'     => __('Mobile Wallet', 'fawaterk'),
			'desc_tip'    => true,
		),
		'fawry_title'                 => array(
			'title'       => __('Fawry Title', 'fawaterk'),
			'type'        => 'text',
			'description' => __('This controls mobile wallet payment title which the user sees during checkout.', 'fawaterk'),
			'default'     => __('Fawry', 'fawaterk'),
			'desc_tip'    => true,
		),
		'private_key'           => array(
			'title'       => __('API credentials', 'fawaterk'),
			'type'        => 'text',
			'description' =>  __('Enter your Fawaterak API credentials to process payments via Fawaterak.'),
		),
		'webhook_url'           => array(
			'title'       => __('WebHook Url', 'fawaterk'),
			'type'        => 'text',
			'disabled' => true,
			'description' =>  __('Copy This to the redirect url field at Fawaterak Website'),
			'default' => get_site_url() . '/wc-api/fawaterak_webhook',
			'custom_attributes' => array('readonly' => 'readonly'),
		),
	];


	if (!isset($_REQUEST['settings-updated']))
		$_REQUEST['settings-updated'] = false;

?>
	<div class="wrap">
		<div class="heading">
			<img src="https://fawaterk.com/wp-content/uploads/2021/02/Fawaterk-Logo-1_en.png" alt="Fawaterk" width="200">
		</div>
		<?php if (false !== $_REQUEST['settings-updated']) : ?>
			<div class="updated fade">
				<p><strong><?php _e('Settings', 'fawaterk'); ?></strong></p>
			</div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields('fawaterk_plugin_options'); ?>
			<?php $options = get_option('fawaterk_plugin_options'); ?>


			<table class="form-table">

				<?php foreach ($fawaterk_options as $id => $field) :
					$field_id = "fawaterk_plugin_options[" . $id . "]";
				?>

					<?php if ($field['type'] == 'checkbox') : ?>
						<tr valign="top">
							<th scope="row">
								<label class="description" for="<?php echo $field_id; ?>"><?php echo $field['title']; ?></label>
							</th>
							<td>
								<input id="<?php echo $field_id; ?>" name="<?php echo $field_id; ?>" type="checkbox" value="1" <?php checked('1', $options[$id]); ?> />
								<p class="description">
									<?php echo $field['description']; ?>
								</p>
							</td>
						</tr>
					<?php elseif ($field['type'] == 'text') :  ?>
						<tr valign="top">
							<th scope="row">
								<label class="description" for="<?php echo $field_id; ?>"><?php echo $field['title']; ?></label>
							</th>
							<td>
								<input <?php if (isset($field['disabled'])) echo 'disabled'; ?> id="<?php echo $field_id; ?>" class="regular-text" type="text" name="<?php echo $field_id; ?>" value="<?php esc_attr_e($options[$id]); ?>" />
								<p class="description">
									<?php echo $field['description']; ?>
								</p>
							</td>
						</tr>
				<?php endif;
				endforeach; ?>
			</table>

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'fawaterk'); ?>" />
			</p>
		</form>
	</div>
<?php
}

/**
 * Sanitize and validate input. Accepts an array, return a sanitized array.
 */
function theme_options_validate($input)
{
	// global $fawaterk_options;

	// // Our checkbox value is either 0 or 1
	// if (!isset($input['option1']))
	// 	$input['option1'] = null;
	// $input['option1'] = ($input['option1'] == 1 ? 1 : 0);

	// // Say our text option must be safe text with no HTML tags
	// $input['sometext'] = wp_filter_nohtml_kses($input['sometext']);

	return $input;
}
