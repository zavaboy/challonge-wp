<?php
// Exit on direct request.
defined( 'ABSPATH' ) OR exit;
// Exit on direct request.
$this instanceof Challonge_Plugin OR exit;
?>
<div class="wrap challonge challonge-settings">
	<h2><?php
		printf(
			/* translators:
				%s is the title of the plugin (hint: it will always be "Challonge")
			*/
			__('%s Settings', Challonge_Plugin::TEXT_DOMAIN),
			Challonge_Plugin::TITLE
		); ?></h2>
	<div id="challonge-donate">
		<p>
			<a href="http://zavaboy.org/" id="challonge-developer">
				<img src="http://www.gravatar.com/avatar/830465c035ca0fc9a3b18e4ece5a672f?s=32" />
				<strong>Ivik Injerd</strong><br />
				http://zavaboy.org/
			</a>
		</p>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
			<input type="hidden" name="cmd" value="_s-xclick" />
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----" />
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" />
		</form>
		<p id="challonge-donate-ty">
			<?php _e( 'If you like this plugin, please consider donating to show your appreciation.', Challonge_Plugin::TEXT_DOMAIN ); ?><br />
			<strong><?php _e( 'Thank you!', Challonge_Plugin::TEXT_DOMAIN ); ?></strong><br />
			<a id="challonge-donate-hide" href="#"><?php _e( 'Hide', Challonge_Plugin::TEXT_DOMAIN ); ?></a>
		</p>
	</div>
	<p><?php _e('The only setup needed is your API key. Simple, right?', Challonge_Plugin::TEXT_DOMAIN) ?></p>
	<form action="options.php" method="post">
		<?php
		settings_fields( 'challonge_options' );
		$options = $this->aOptions;
		$user = wp_get_current_user();
// echo'<pre>';print_r($options);echo'</pre>';
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="challonge-apikey"><?php _e('API Key', Challonge_Plugin::TEXT_DOMAIN) ?></label></th>
				<td>
					<input type="text" id="challonge-apikey" name="challonge_options[api_key]" size="40"
						value="<?php echo esc_attr($options['api_key_input']) ?>" />
					<span id="challonge-apikey-check" class="dashicons-before dashicons-format-status">
						<?php _e('Verifying...', Challonge_Plugin::TEXT_DOMAIN) ?>
					</span>
					<span id="challonge-apikey-ok" class="dashicons-before dashicons-yes">
						<?php _e('Valid', Challonge_Plugin::TEXT_DOMAIN) ?>
					</span>
					<span id="challonge-apikey-fail" class="dashicons-before dashicons-no">
						<?php _e('Invalid', Challonge_Plugin::TEXT_DOMAIN) ?>
					</span>
					<span id="challonge-apikey-error" class="dashicons-before dashicons-warning">
						<span id="challonge-apikey-errmsg"></span>
					</span>
					<br />
					<?php _e('Don\'t have an API key?', Challonge_Plugin::TEXT_DOMAIN) ?>
					<a href="https://challonge.com/settings/developer" target="_blank"><?php _e('Get one.', Challonge_Plugin::TEXT_DOMAIN) ?></a>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Privacy', Challonge_Plugin::TEXT_DOMAIN) ?></th>
				<td>
					<label for="challonge-public_shortcode"><input type="checkbox" id="challonge-public_shortcode"
						name="challonge_options[public_shortcode]" <?php checked($options['public_shortcode'], true) ?>/>
					<?php _e('Embedded Challonge tournaments are publicly displayed by default.', Challonge_Plugin::TEXT_DOMAIN) ?></label><br />
					<label for="challonge-public_widget"><input type="checkbox" id="challonge-public_widget"
						name="challonge_options[public_widget]" <?php checked($options['public_widget'], true) ?>/>
					<?php _e('Challonge widgets are publicly displayed by default.', Challonge_Plugin::TEXT_DOMAIN) ?></label><br />
					<label for="challonge-public_widget_signup"><input type="checkbox" id="challonge-public_widget_signup"
						name="challonge_options[public_widget_signup]" <?php checked($options['public_widget_signup'], true) ?>/>
					<?php _e('The Signup buttons in the widget are publicly displayed. (The user will be asked to login before signing up to a tournament.)', Challonge_Plugin::TEXT_DOMAIN) ?></label><br />
					<label for="challonge-public_ignore_exclusion"><input type="checkbox" id="challonge-public_ignore_exclusion"
						name="challonge_options[public_ignore_exclusion]" <?php checked($options['public_ignore_exclusion'], true) ?>/>
					<?php _e('Do not exclude tournaments that are excluded from search engines and the public browsable index.', Challonge_Plugin::TEXT_DOMAIN) ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Display', Challonge_Plugin::TEXT_DOMAIN) ?></th>
				<td>
					<?php _e('Manage the table headers for tournament listings:', Challonge_Plugin::TEXT_DOMAIN) ?><br />
					<ul id="challonge-headers_shortcode-list">
						<?php
							if (is_array($options['headers_shortcode'])) {
								$filter_out = array_flip(array_filter(array_keys($this->aOptionsDefault['headers_shortcode'][0]),function($v){
									return $v[0]=='_';
								}));
								foreach ($options['headers_shortcode'] AS $k => $v) {
						?>
							<li class="ui-state-default challonge-<?php echo $v['show']?'show':'hide' ?>">
								<input id="challonge-headers_shortcode-<?php echo esc_attr( $v['prop'] ) ?>"
									type="hidden" name="challonge_options[headers_shortcode][]"
									value="<?php echo esc_attr(json_encode(array_diff_key($v, $filter_out))) ?>" />
								<span class="challonge-headers_shortcode-item">
									<a class="challonge-headers_shortcode-togglevis dashicons dashicons-visibility togglevis challonge-show" tabindex="0"></a>
									<a class="challonge-headers_shortcode-togglevis dashicons dashicons-hidden togglevis challonge-hide" tabindex="0"></a>
									<span class="challonge-headers_shortcode-label"><?php echo $v['alias'] ?: $v['name'] ?></span>
									<a class="challonge-headers_shortcode-edit dashicons dashicons-edit challonge-hover" tabindex="0"></a>
								</span>
								<span class="challonge-headers_shortcode-handle dashicons dashicons-menu challonge-handle" tabindex="0"></span>
								<div class="challonge-headers_shortcode-editform" data-prop="<?php echo esc_attr( $v['prop'] ) ?>"
									title="<?php echo esc_attr(__('Edit Header:', Challonge_Plugin::TEXT_DOMAIN) . ' ' . $v['name'] ) ?>">
									<fieldset>
										<label for="challonge-headers_shortcode-<?php echo esc_attr( $v['prop'] ) ?>-alias"><?php _e('Alias', Challonge_Plugin::TEXT_DOMAIN) ?></label>
										<input id="challonge-headers_shortcode-<?php echo esc_attr( $v['prop'] ) ?>-alias"
											type="text" value="<?php echo esc_attr( $v['alias'] ) ?>"
											placeholder="<?php echo esc_attr( $v['name'] ) ?>" class="ui-widget-content ui-corner-all" />
										<label for="challonge-headers_shortcode-<?php echo esc_attr( $v['prop'] ) ?>-format"><?php
											/* translator:
												This word "format" refers to the noun as in "date format" and NOT the verb as in "format C drive"
											*/
											_e('Format', Challonge_Plugin::TEXT_DOMAIN) ?></label>
										<select id="challonge-headers_shortcode-<?php echo esc_attr( $v['prop'] ) ?>-format"
											class="ui-widget-content ui-corner-all">
											<?php foreach ( $v['_formats'] AS $fmt => $fmt_desc ) { ?>
												<option value="<?php echo esc_attr( $fmt ) ?>" <?php selected( $v['format'], $fmt ) ?>><?php echo esc_html( $fmt_desc ) ?></option>
											<?php } ?>
										</select>
									</fieldset>
								</div>
							</li>
						<?php
								}
							} else {
								echo '<li><em>there was an error</em><li>';
							}
						?>
					</ul>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Participants', Challonge_Plugin::TEXT_DOMAIN) ?></th>
				<td>
					<?php _e('Use the following template for participant names:', Challonge_Plugin::TEXT_DOMAIN) ?><br />
					<input type="text" id="challonge-participant_name" name="challonge_options[participant_name]" size="64"
						value="<?php echo esc_attr($options['participant_name']) ?>" /><br />
					<a href="#show-tokens" id="challonge-participant_name-showtokens"><?php _e('Show me all of the available tokens!', Challonge_Plugin::TEXT_DOMAIN) ?></a>
					<div id="challonge-participant_name-tokens">
						<p><?php _e('Here are all of the tokens you may use:', Challonge_Plugin::TEXT_DOMAIN) ?></p>
						<table>
							<thead>
								<tr>
									<th class="mono"><?php _e('Token', Challonge_Plugin::TEXT_DOMAIN) ?></th>
									<th><?php _e('Description', Challonge_Plugin::TEXT_DOMAIN) ?></th>
									<th><select id="challonge-participant_name-tokens-users"><?php
										$all_users = get_users('number=1000');
										foreach ($all_users AS $k=>$v) {
											$userdata = esc_attr(json_encode(array(
												'uid'=>$v->ID,
												'login'=>$v->user_login,
												'nice'=>$v->user_nicename,
												'first'=>$v->user_firstname,
												'last'=>$v->user_lastname,
												'nick'=>$v->nickname,
												'display'=>$v->display_name,
												'role'=>current($v->roles),
											)));
											echo '<option value="'.$v->ID
												.'" data-userdata="'.$userdata
												.'"'.($v->ID==$user->ID?' selected="selected"':'')
												.'>'.$v->user_login.'</option>';
										}
										//unset($all_users); // The variable may be too big to just leave around; we no longer need it.
									?></select></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="token">%uid%</td>
									<td><?php _e('User ID', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td class="userdata"></td>
								</tr>
								<tr>
									<td class="token">%login%</td>
									<td><?php _e('Username', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td class="userdata"></td>
								</tr>
								<tr>
									<td class="token">%nice%</td>
									<td><?php _e('Username without funk', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td class="userdata"></td>
								</tr>
								<tr>
									<td class="token">%first%</td>
									<td><?php _e('First Name', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td class="userdata"></td>
								</tr>
								<tr>
									<td class="token">%last%</td>
									<td><?php _e('Last Name', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td class="userdata"></td>
								</tr>
								<tr>
									<td class="token">%nick%</td>
									<td><?php _e('Nickname', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td class="userdata"></td>
								</tr>
								<tr>
									<td class="token">%display%</td>
									<td><?php _e('Display Name', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td class="userdata"></td>
								</tr>
								<tr>
									<td class="token">%role%</td>
									<td><?php _e('User Role', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td class="userdata"></td>
								</tr>
								<tr>
									<td class="token">%whatev%</td>
									<td><?php _e('Custom input field', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td><input type="text" size="16" placeholder="<?php _e('example', Challonge_Plugin::TEXT_DOMAIN) ?>" style="width:12em" /></td>
								</tr>
								<tr>
									<td class="token">%whatev:<strong title="Any number">N</strong>%</td>
									<td><?php _e('Custom input field with set width', Challonge_Plugin::TEXT_DOMAIN) ?></td>
									<td><input type="text" size="16" placeholder="%whatev:7%" style="width:7em" /></td>
								</tr>
								<tr>
									<td class="token">%meta:<strong title="Any user meta name">field_name</strong>%</td>
									<td>
										<?php _e('Any user meta field', Challonge_Plugin::TEXT_DOMAIN) ?>
										<span>(<a href="#show-usermeta" id="challonge-participant_name-showusermetatokens"><?php
											_e('Show List', Challonge_Plugin::TEXT_DOMAIN)
										?></a>)</span>
										<div id="challonge-participant_name-usermetatokens">
											<p>
												<strong><?php
													_e( "Note: Just because a meta field is listed here, doesn't mean it should be used.",
														Challonge_Plugin::TEXT_DOMAIN )
												?></strong>
												<?php
													_e( 'This list is exhaustive and includes user meta data that is mostly useless for this purpose. Some of these fields may contain sensitive user information.',
														Challonge_Plugin::TEXT_DOMAIN )
												?>
											</p>
											<ul>
												<?php
													$usermeta = array();
													foreach ($all_users AS $k=>$v) {
														$usermeta = array_merge($usermeta,array_keys(get_user_meta($v->ID)));
													}
													sort($usermeta);
													foreach ( $usermeta AS $k => $v ) {
														switch ($v) {
															case 'nickname':
															case 'first_name':
															case 'last_name':
																continue 2;
															default:
																echo '<li>' . esc_html( $v ) . '</li>';
																break;
														}
													}
												?>
											</ul>
										</div>
									</td>
									<td><em>e.g.</em> <tt>%meta:clan_tag%</tt></td>
								</tr>
							</tbody>
						</table>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Scoring', Challonge_Plugin::TEXT_DOMAIN) ?></th>
				<td>
					<p><?php
						printf(
							/* translator:
								%s is the name of the third-party website this plugin interfaces with (hint: it will always be "Challonge.com")
							*/
							__(
								'Remember, you can always correct any reported scores with your %s account.',
								Challonge_Plugin::TEXT_DOMAIN
							),
							Challonge_Plugin::THIRD_PARTY
						);
					?></p>
					<label for="challonge-scoring-both"><input type="radio" id="challonge-scoring-both"
						name="challonge_options[scoring]" value="both"  <?php checked($options['scoring'], 'both') ?>/>
					<?php _e('Both participants must report a score and both reports must agree.', Challonge_Plugin::TEXT_DOMAIN) ?></label><br />
					<label for="challonge-scoring-one"><input type="radio" id="challonge-scoring-one"
						name="challonge_options[scoring]" value="one"  <?php checked($options['scoring'], 'one') ?>/>
					<?php
						printf(
							/* translator:
								%s is the name of the third-party website this plugin interfaces with (hint: it will always be "Challonge.com")
							*/
							__(
								'A participant may report the score if they were matched with an opponent that has signed up by other means. (eg: via %s)',
								Challonge_Plugin::TEXT_DOMAIN
							),
							Challonge_Plugin::THIRD_PARTY
						);
					?></label><br />
					<label for="challonge-scoring-any"><input type="radio" id="challonge-scoring-any"
						name="challonge_options[scoring]" value="any" <?php checked($options['scoring'], 'any') ?>/>
					<?php _e('Either participant may report the score.', Challonge_Plugin::TEXT_DOMAIN) ?></label><br />
					<label for="challonge-scoring-none"><input type="radio" id="challonge-scoring-none"
						name="challonge_options[scoring]" value="none" <?php checked($options['scoring'], 'none') ?>/>
					<?php _e('Disable score reporting; no participants may report a score.', Challonge_Plugin::TEXT_DOMAIN) ?></label><br /><br />
					<label for="challonge-scoring_opponent"><input type="checkbox" id="challonge-scoring_opponent"
						name="challonge_options[scoring_opponent]" <?php checked($options['scoring_opponent'], true) ?>/>
					<?php _e('Participants report their opponents\' scores when reporting their own.', Challonge_Plugin::TEXT_DOMAIN) ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Caching', Challonge_Plugin::TEXT_DOMAIN) ?></th>
				<td>
					<?php
						printf(
							/* translator:
								%s is a text input box for a number
							*/
							__( 'Cache API responses for %s seconds. (Set this to "0" to disable caching.)', Challonge_Plugin::TEXT_DOMAIN),
							'<input type="text" id="challonge-caching" name="challonge_options[caching]"'
							. ' size="3" maxlength="5" value="' . esc_attr( $options['caching'] ) . '" />'
						);
					?><br />
					<label for="challonge-caching_adaptive"><input type="checkbox" id="challonge-caching_adaptive"
						name="challonge_options[caching_adaptive]" <?php checked($options['caching_adaptive'], true) ?>/>
					<?php _e('Enable adaptive caching. Adaptive caching will expire cached API responses based on their content.', Challonge_Plugin::TEXT_DOMAIN) ?></label><br />
					<label for="challonge-caching_freshness"><input type="checkbox" id="challonge-caching_freshness"
						name="challonge_options[caching_freshness]" <?php checked($options['caching_freshness'], true) ?>/>
					<?php _e('Show cache age. Enabling this will also allow users to manually refresh content.', Challonge_Plugin::TEXT_DOMAIN) ?></label><br />
					<label for="challonge-caching_clear"><input type="checkbox" id="challonge-caching_clear"
						name="challonge_options[caching_clear]" <?php checked(true, true) ?>/>
					<?php _e('Clear any cached API responses when I click save.', Challonge_Plugin::TEXT_DOMAIN) ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Security', Challonge_Plugin::TEXT_DOMAIN) ?></th>
				<td>
					<label for="challonge-no_ssl_verify"><input type="checkbox" id="challonge-no_ssl_verify"
						name="challonge_options[no_ssl_verify]" <?php checked($options['no_ssl_verify'], true) ?>/>
					<?php _e('Disable SSL verification.', Challonge_Plugin::TEXT_DOMAIN) ?></label><br />
					<div class="challonge-important"><?php _e('Disabling SSL verification is a security risk. This may be, however, a way around SSL errors if you are getting them.', Challonge_Plugin::TEXT_DOMAIN) ?></div>
				</td>
			</tr>
		</table>
		<?php
		do_settings_fields('challonge_options', 'default');
		?>
		<?php submit_button(); ?>
		<div id="challonge-version"><?php echo Challonge_Plugin::TITLE . ' ' . Challonge_Plugin::VERSION; ?></div>
	</form>
</div>