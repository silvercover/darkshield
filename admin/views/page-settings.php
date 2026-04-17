<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1>🛡️ <?php esc_html_e( 'DarkShield — Settings', 'darkshield' ); ?></h1>

	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

	<div style="margin-top:20px;">
		<form method="post" action="options.php">
			<?php
			settings_fields( 'darkshield_options' );
			do_settings_sections( 'darkshield-settings' );
			submit_button();
			?>
		</form>
	</div>
</div>
