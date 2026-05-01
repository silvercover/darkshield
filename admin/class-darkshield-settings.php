<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DarkShield_Settings {

	public function register_settings() {
		register_setting(
			'darkshield_options',
			'darkshield_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		register_setting(
			'darkshield_options',
			'darkshield_allowed_services',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_services' ),
				'default'           => '',
			)
		);

		// Mode
		add_settings_section(
			'ds_mode',
			__( 'Shield Mode', 'darkshield' ),
			function () {
				echo '<p>' . esc_html__( 'Select the operating mode.', 'darkshield' ) . '</p>';
			},
			'darkshield-settings'
		);

		add_settings_field( 'ds_mode_field', __( 'Current Mode', 'darkshield' ), array( $this, 'render_mode' ), 'darkshield-settings', 'ds_mode' );

		// Blockers
		add_settings_section(
			'ds_blockers',
			__( 'Blocker Options', 'darkshield' ),
			function () {
				echo '<p>' . esc_html__( 'Enable or disable individual blockers.', 'darkshield' ) . '</p>';
			},
			'darkshield-settings'
		);

		$blockers = array(
			'block_fonts'     => array( __( 'Block External Fonts', 'darkshield' ), __( 'Google Fonts, Typekit, Font Awesome', 'darkshield' ) ),
			'block_cdn'       => array( __( 'Block External CDNs', 'darkshield' ), __( 'cdnjs, jsDelivr, unpkg', 'darkshield' ) ),
			'block_analytics' => array( __( 'Block Analytics & Tracking', 'darkshield' ), __( 'Google Analytics, GTM, Hotjar, Clarity', 'darkshield' ) ),
			'block_updates'   => array( __( 'Block WordPress Updates', 'darkshield' ), __( 'Core, plugin, theme update checks', 'darkshield' ) ),
			'block_gravatar'  => array( __( 'Block Gravatar', 'darkshield' ), __( 'Replace with local SVG avatar', 'darkshield' ) ),
			'block_embeds'    => array( __( 'Block External Embeds', 'darkshield' ), __( 'YouTube, Vimeo, Twitter oEmbed', 'darkshield' ) ),
			'block_recaptcha' => array( __( 'Block reCAPTCHA', 'darkshield' ), __( 'reCAPTCHA, hCaptcha, Turnstile', 'darkshield' ) ),
			'block_heartbeat' => array( __( 'Limit Heartbeat API', 'darkshield' ), __( 'Reduce to 120s, disable in Offline', 'darkshield' ) ),
			'block_email'     => array( __( 'Block Emails (Offline)', 'darkshield' ), __( 'Intercept and log instead of sending', 'darkshield' ) ),
			'allow_messenger' => array( __( 'Allow Messenger APIs', 'darkshield' ), __( 'Telegram, Slack, Discord, Push', 'darkshield' ) ),
			'block_emoji'     => array( __( 'Block WordPress Emoji', 'darkshield' ), __( 'Emoji detection script, staticize filters, s.w.org dns-prefetch', 'darkshield' ) ),
		);

		foreach ( $blockers as $key => $info ) {
			add_settings_field(
				'ds_' . $key,
				$info[0],
				array( $this, 'render_checkbox' ),
				'darkshield-settings',
				'ds_blockers',
				array(
					'key'  => $key,
					'desc' => $info[1],
				)
			);
		}

		// Services
		add_settings_section(
			'ds_services',
			__( 'Allowed Service URLs', 'darkshield' ),
			function () {
				echo '<p>' . esc_html__( 'Domains for SMS, Telegram, payment gateways. Allowed in ALL modes including Offline.', 'darkshield' ) . '</p>';
			},
			'darkshield-settings'
		);

		add_settings_field( 'ds_services_field', __( 'Service Domains', 'darkshield' ), array( $this, 'render_services' ), 'darkshield-settings', 'ds_services' );

		// Logging
		add_settings_section(
			'ds_log',
			__( 'Logging', 'darkshield' ),
			function () {
				echo '<p>' . esc_html__( 'Configure request logging.', 'darkshield' ) . '</p>';
			},
			'darkshield-settings'
		);

		add_settings_field(
			'ds_log_enabled',
			__( 'Enable Logging', 'darkshield' ),
			array( $this, 'render_checkbox' ),
			'darkshield-settings',
			'ds_log',
			array(
				'key'  => 'log_enabled',
				'desc' => __( 'Log blocked and allowed requests.', 'darkshield' ),
			)
		);
		add_settings_field(
			'ds_log_retention',
			__( 'Retention (days)', 'darkshield' ),
			array( $this, 'render_number' ),
			'darkshield-settings',
			'ds_log',
			array(
				'key' => 'log_retention',
				'min' => 1,
				'max' => 365,
			)
		);
	}

	// ========================================
	// Field Renderers
	// ========================================

	public function render_mode() {
		$mode  = DarkShield_Utils::get_mode();
		$modes = array(
			'normal'   => array( '🟢', __( 'Normal — All requests allowed', 'darkshield' ), '#00a32a' ),
			'national' => array( '🟡', __( 'National — Only Iranian domains allowed', 'darkshield' ), '#dba617' ),
			'offline'  => array( '🔴', __( 'Offline — All external blocked', 'darkshield' ), '#d63638' ),
		);
		foreach ( $modes as $key => $m ) {
			$style = ( $mode === $key ) ? 'font-weight:bold;color:' . $m[2] . ';' : '';
			printf(
				'<label style="display:block;margin-bottom:10px;padding:8px 12px;border:1px solid #ddd;border-radius:4px;%s"><input type="radio" name="darkshield_settings[mode]" value="%s" %s /> %s %s</label>',
				esc_attr( $style ),
				esc_attr( $key ),
				checked( $mode, $key, false ),
				esc_html( $m[0] ),
				esc_html( $m[1] )
			);
		}
	}

	public function render_checkbox( $args ) {
		$val  = DarkShield_Utils::get_setting( $args['key'], 0 );
		$desc = isset( $args['desc'] ) ? $args['desc'] : '';
		printf(
			'<label><input type="checkbox" name="darkshield_settings[%s]" value="1" %s /> %s</label>',
			esc_attr( $args['key'] ),
			checked( 1, $val, false ),
			esc_html( $desc )
		);
	}

	public function render_number( $args ) {
		$val = DarkShield_Utils::get_setting( $args['key'], 30 );
		$min = isset( $args['min'] ) ? $args['min'] : 1;
		$max = isset( $args['max'] ) ? $args['max'] : 365;
		printf(
			'<input type="number" name="darkshield_settings[%s]" value="%d" min="%d" max="%d" class="small-text" />',
			esc_attr( $args['key'] ),
			(int) $val,
			(int) $min,
			(int) $max
		);
	}

	// ========================================
	// Service Domains + Quick Add
	// ========================================

	public function render_services() {
		$services = get_option( 'darkshield_allowed_services', '' );
		?>
		<textarea name="darkshield_allowed_services" rows="8" class="large-text code"
					placeholder="api.kavenegar.com&#10;api.telegram.org&#10;api.zarinpal.com"
		><?php echo esc_textarea( $services ); ?></textarea>
		<p class="description"><?php esc_html_e( 'One domain per line. Subdomain matching supported (adding example.com also allows sub.example.com).', 'darkshield' ); ?></p>

		<div style="margin-top:15px;">
			<p><strong><?php esc_html_e( 'Quick Add:', 'darkshield' ); ?></strong></p>

			<!-- Iranian Services -->
			<p style="margin:10px 0 5px;font-size:12px;font-weight:600;color:#333;">🇮🇷 <?php esc_html_e( 'Iranian Services', 'darkshield' ); ?></p>
			<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:15px;">
				<?php
				$iranian = array(
					__( 'SMS Providers', 'darkshield' )  => "api.kavenegar.com\nrest.payamak-panel.com\napi.sms.ir\napi.ghasedak.me\napi.melipayamak.com\napi2.ippanel.com\napi.limosms.com\napi.farazsms.com\nsms.magfa.com\napi.payamresan.com",
					__( 'Zarinpal', 'darkshield' )       => "api.zarinpal.com\nwww.zarinpal.com\nsandbox.zarinpal.com",
					__( 'IDPay', 'darkshield' )          => 'api.idpay.ir',
					__( 'NextPay', 'darkshield' )        => "api.nextpay.org\nnextpay.org",
					__( 'Pay.ir', 'darkshield' )         => "pay.ir\napi.pay.ir",
					__( 'Zibal', 'darkshield' )          => "gateway.zibal.ir\napi.zibal.ir",
					__( 'PayPing', 'darkshield' )        => 'api.payping.ir',
					__( 'Vandar', 'darkshield' )         => "api.vandar.io\nipg.vandar.io",
					__( 'Shaparak Banks', 'darkshield' ) => "sep.shaparak.ir\npec.shaparak.ir\nbpm.shaparak.ir\nsadad.shaparak.ir\nmabna.shaparak.ir\nasan.shaparak.ir\nikc.shaparak.ir\nmellat.shaparak.ir\npna.shaparak.ir",
					__( 'Jibit', 'darkshield' )          => "api.jibit.ir\nnapi.jibit.ir",
					__( 'PNA', 'darkshield' )            => "pna.shaparak.ir\nrefund.pna.co.ir",
					__( 'SizPay', 'darkshield' )         => 'rt.sizpay.ir',
					__( 'Aqayepardakht', 'darkshield' )  => 'panel.aqayepardakht.ir',
					__( 'Bitpay', 'darkshield' )         => "bitpay.ir\napi.bitpay.ir",
					__( 'Iranian Maps', 'darkshield' )   => "api.neshan.org\napi.cedarmaps.com\nmap.ir\napi.balad.ir",
					__( 'Iranian CDN', 'darkshield' )    => "cdn.arvancloud.ir\nstatic.arvancloud.ir\narvancloud.com",
					__( 'Iranian Analytics', 'darkshield' ) => "cdn.yektanet.com\nyektanet.com",
				);
				$this->render_quick_buttons( $iranian );
				?>
			</div>

			<!-- Messenger APIs -->
			<p style="margin:10px 0 5px;font-size:12px;font-weight:600;color:#333;">💬 <?php esc_html_e( 'Messenger APIs', 'darkshield' ); ?></p>
			<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:15px;">
				<?php
				$messenger = array(
					__( 'Telegram', 'darkshield' )      => 'api.telegram.org',
					__( 'Slack', 'darkshield' )         => "hooks.slack.com\napi.slack.com",
					__( 'Discord', 'darkshield' )       => "discord.com\ndiscordapp.com",
					__( 'WhatsApp', 'darkshield' )      => "api.whatsapp.com\ngraph.facebook.com",
					__( 'Push Services', 'darkshield' ) => "fcm.googleapis.com\nonesignal.com\napi.pusher.com\napi.pushover.net",
				);
				$this->render_quick_buttons( $messenger );
				?>
			</div>

			<!-- International Payment -->
			<p style="margin:10px 0 5px;font-size:12px;font-weight:600;color:#333;">💳 <?php esc_html_e( 'International Payment', 'darkshield' ); ?></p>
			<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:15px;">
				<?php
				$intl_payment = array(
					__( 'PayPal', 'darkshield' )        => "api.paypal.com\nwww.paypal.com\napi-m.paypal.com\napi-3t.paypal.com\nipnpb.paypal.com",
					__( 'Stripe', 'darkshield' )        => "api.stripe.com\njs.stripe.com\nhooks.stripe.com\ncheckout.stripe.com\nfiles.stripe.com",
					__( 'Square', 'darkshield' )        => "connect.squareup.com\napi.squareup.com\nweb.squarecdn.com",
					__( 'Mollie', 'darkshield' )        => "api.mollie.com\nwww.mollie.com",
					__( 'Razorpay', 'darkshield' )      => "api.razorpay.com\ncheckout.razorpay.com",
					__( 'Paddle', 'darkshield' )        => "vendors.paddle.com\ncheckout.paddle.com\nsandbox-vendors.paddle.com",
					__( 'Braintree', 'darkshield' )     => "api.braintreegateway.com\npayments.braintree-api.com\nclient-analytics.braintreegateway.com",
					__( 'Authorize.net', 'darkshield' ) => "api.authorize.net\napi2.authorize.net\naccept.authorize.net",
					__( '2Checkout', 'darkshield' )     => "api.2checkout.com\nwww.2checkout.com\nsecure.2checkout.com",
					__( 'Klarna', 'darkshield' )        => "api.klarna.com\napi-na.klarna.com\napi-oc.klarna.com",
					__( 'Adyen', 'darkshield' )         => "checkout-live.adyen.com\npal-live.adyen.com",
					__( 'WooCommerce', 'darkshield' )   => "woocommerce.com\napi.woocommerce.com",
				);
				$this->render_quick_buttons( $intl_payment );
				?>
			</div>

			<!-- Email Services -->
			<p style="margin:10px 0 5px;font-size:12px;font-weight:600;color:#333;">📧 <?php esc_html_e( 'Email Services', 'darkshield' ); ?></p>
			<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:15px;">
				<?php
				$email = array(
					__( 'Mailchimp', 'darkshield' )  => "api.mailchimp.com\nus1.api.mailchimp.com",
					__( 'SendGrid', 'darkshield' )   => 'api.sendgrid.com',
					__( 'Mailgun', 'darkshield' )    => 'api.mailgun.net',
					__( 'Amazon SES', 'darkshield' ) => "email.us-east-1.amazonaws.com\nemail.eu-west-1.amazonaws.com",
					__( 'Brevo', 'darkshield' )      => "api.sendinblue.com\napi.brevo.com",
					__( 'Postmark', 'darkshield' )   => 'api.postmarkapp.com',
					__( 'SparkPost', 'darkshield' )  => 'api.sparkpost.com',
				);
				$this->render_quick_buttons( $email );
				?>
			</div>

			<!-- Other APIs -->
			<p style="margin:10px 0 5px;font-size:12px;font-weight:600;color:#333;">🔌 <?php esc_html_e( 'Other APIs', 'darkshield' ); ?></p>
			<div style="display:flex;gap:8px;flex-wrap:wrap;">
				<?php
				$other = array(
					__( 'Google APIs', 'darkshield' ) => "www.googleapis.com\nmaps.googleapis.com\ntranslate.googleapis.com\nfcm.googleapis.com",
					__( 'Cloudflare', 'darkshield' )  => "api.cloudflare.com\nchallenges.cloudflare.com",
					__( 'reCAPTCHA', 'darkshield' )   => "www.google.com\nwww.gstatic.com\nwww.recaptcha.net",
					__( 'hCaptcha', 'darkshield' )    => "hcaptcha.com\njs.hcaptcha.com\napi.hcaptcha.com",
					__( 'Zapier', 'darkshield' )      => 'hooks.zapier.com',
					__( 'GitHub API', 'darkshield' )  => "api.github.com\nraw.githubusercontent.com",
					__( 'OpenAI', 'darkshield' )      => 'api.openai.com',
				);
				$this->render_quick_buttons( $other );
				?>
			</div>
		</div>

		<!-- Info box -->
		<div style="margin-top:20px;padding:12px 15px;background:#f0f6fc;border:1px solid #c3c4c7;border-radius:4px;">
			<p style="margin:0;font-size:13px;color:#666;">
				<strong>ℹ️ <?php esc_html_e( 'Service Domains vs Whitelist:', 'darkshield' ); ?></strong><br>
				<?php esc_html_e( 'Both bypass blocking. Service Domains are for APIs and payment gateways (managed here with Quick Add). Whitelist is for general domains (managed in the Whitelist page). Use whichever is more convenient.', 'darkshield' ); ?>
			</p>
		</div>

		<script>
		(function($){
			// Store original label for each button on init
			$('.darkshield-quick-add').each(function(){
				$(this).data('original-label', $(this).text().trim());
			});

			$('.darkshield-quick-add').on('click', function(){
				var $btn = $(this);
				var $t = $('textarea[name="darkshield_allowed_services"]');
				var cur = $t.val().trim();
				var existing = cur ? cur.split("\n").map(function(d){ return d.trim().toLowerCase(); }) : [];
				var originalLabel = $btn.data('original-label');
				var toAdd = [];

				$btn.data('domains').split("\n").forEach(function(d){
					d = d.trim().toLowerCase();
					if (d && existing.indexOf(d) === -1) {
						toAdd.push(d);
					}
				});

				if (toAdd.length > 0) {
					$t.val(cur ? cur + "\n" + toAdd.join("\n") : toAdd.join("\n"));
					$btn.html('✅ ' + originalLabel + ' <small>(' + toAdd.length + ')</small>');
					$btn.prop('disabled', true).css({
						'border-color': '#00a32a',
						'color': '#00a32a',
						'background': '#edf7ed'
					});
					setTimeout(function(){
						$btn.text(originalLabel);
						$btn.prop('disabled', false).css({
							'border-color': '',
							'color': '',
							'background': ''
						});
					}, 3000);
				} else {
					$btn.html('☑️ ' + originalLabel);
					$btn.prop('disabled', true).css({
						'color': '#999',
						'border-color': '#ccc'
					});
					setTimeout(function(){
						$btn.text(originalLabel);
						$btn.prop('disabled', false).css({
							'color': '',
							'border-color': ''
						});
					}, 2000);
				}
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Render Quick Add buttons from array.
	 *
	 * @param array $groups Label => domains pairs.
	 */
	private function render_quick_buttons( $groups ) {
		foreach ( $groups as $label => $domains ) {
			$tooltip = str_replace( "\n", ', ', $domains );
			printf(
				'<button type="button" class="button button-small darkshield-quick-add" data-domains="%s" title="%s">+ %s</button>',
				esc_attr( $domains ),
				esc_attr( $tooltip ),
				esc_html( $label )
			);
		}
	}

	// ========================================
	// Sanitize
	// ========================================

	public function sanitize_settings( $input ) {
		$s = array();

		// Mode
		if ( isset( $input['mode'] ) ) {
			DarkShield_Utils::set_mode( sanitize_text_field( $input['mode'] ) );
		}

		// Checkboxes
		$checks = array(
			'block_fonts',
			'block_cdn',
			'block_analytics',
			'block_updates',
			'block_gravatar',
			'block_embeds',
			'block_recaptcha',
			'block_heartbeat',
			'block_email',
			'log_enabled',
			'allow_messenger',
		);
		foreach ( $checks as $k ) {
			$s[ $k ] = isset( $input[ $k ] ) ? 1 : 0;
		}

		// Number
		$s['log_retention'] = isset( $input['log_retention'] )
			? max( 1, min( 365, absint( $input['log_retention'] ) ) )
			: 30;

		return $s;
	}

	public function sanitize_services( $input ) {
		if ( empty( $input ) ) {
			return '';
		}

		$clean = array();
		foreach ( explode( "\n", $input ) as $line ) {
			$d = strtolower( trim( $line ) );
			$d = preg_replace( '#^https?://#', '', $d );
			$d = preg_replace( '#^www\.#', '', $d );
			$d = preg_replace( '#/.*$#', '', $d );
			$d = preg_replace( '#:\d+$#', '', $d );
			$d = sanitize_text_field( $d );
			if ( ! empty( $d ) && strpos( $d, '.' ) !== false ) {
				$clean[] = $d;
			}
		}

		$clean = array_unique( $clean );
		sort( $clean );
		return implode( "\n", $clean );
	}
}
