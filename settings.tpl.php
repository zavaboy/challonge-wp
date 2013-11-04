<?php
// Exit on direct request.
defined( 'ABSPATH' ) OR exit;
?>
<div class="wrap challonge challonge-settings">
	<div id="challonge-donate">
		<p>
			<a href="http://zavaboy.org/" id="challonge-developer">
				<img src="http://www.gravatar.com/avatar/<?php echo md5('zavaboy@gmail.com'); ?>?s=32" />
				<strong>Ivik Injerd</strong><br />
				http://zavaboy.org/
			</a>
		</p>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
			">
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>
		<p><small><?php _e( 'If you like this plugin, please consider donating to show your appreciation.<br /><strong>Thank you!</strong>', Challonge_Plugin::TEXT_DOMAIN ); ?></small></p>
	</div>
	<h2><?php _e('Challonge', Challonge_Plugin::TEXT_DOMAIN) ?> <?php _e('Settings', Challonge_Plugin::TEXT_DOMAIN) ?></h2>
	<p><?php _e('The only setup needed is your API key. Simple, right?', Challonge_Plugin::TEXT_DOMAIN) ?></p>
	<form action="options.php" method="post">
		<?php
		settings_fields('challonge_options');
		$options = $this->aOptions;
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="challonge-apikey"><?php _e('API Key', Challonge_Plugin::TEXT_DOMAIN) ?></label></th>
				<td><input type="text" id="challonge-apikey" name="challonge_options[api_key]" size="40"
					value="<?php echo esc_attr($options['api_key_input']) ?>" />
				<span id="challonge-apikey-check"><?php _e('Verifying...', Challonge_Plugin::TEXT_DOMAIN) ?></span>
				<span id="challonge-apikey-ok">&#x2714; <?php _e('Valid', Challonge_Plugin::TEXT_DOMAIN) ?></span>
				<span id="challonge-apikey-fail">&#x2718; <?php _e('Invalid', Challonge_Plugin::TEXT_DOMAIN) ?></span>
				<br />
				<?php _e('Don\'t have an API key?', Challonge_Plugin::TEXT_DOMAIN) ?>
				<a href="https://challonge.com/users/+/change_password" target="_blank"><?php _e('Get one.', Challonge_Plugin::TEXT_DOMAIN) ?></a></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Privacy', Challonge_Plugin::TEXT_DOMAIN) ?></th>
				<td>
					<label for="challonge-public_shortcode"><input type="checkbox" id="challonge-public_shortcode"
						name="challonge_options[public_shortcode]" <?php checked($options['public_shortcode'], true) ?>/>
					<?php _e('Embeded Challonge tournaments are publicly displayed by default.', Challonge_Plugin::TEXT_DOMAIN) ?></label><br />
					<label for="challonge-public_widget"><input type="checkbox" id="challonge-public_widget"
						name="challonge_options[public_widget]" <?php checked($options['public_widget'], true) ?>/>
					<?php _e('Challonge widgets are publicly displayed by default.', Challonge_Plugin::TEXT_DOMAIN) ?></label>
				</td>
			</tr>
		</table>
		<?php
		do_settings_fields('challonge_options', 'default');
		?>
		<?php submit_button(); ?>
	</form>
</div>