<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$darkshield_page_title    = __( 'Settings', 'darkshield' );
$darkshield_page_subtitle = __( 'Choose a shield mode and fine-tune every blocker.', 'darkshield' );
?>
<div class="wrap darkshield">
	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-page-header.php'; ?>

	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

	<div>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'darkshield_options' );
			do_settings_sections( 'darkshield-settings' );
			submit_button();
			?>
		</form>
	</div>
</div>
