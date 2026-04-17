<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$current = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
$tabs    = array(
	'darkshield'             => __( 'Dashboard', 'darkshield' ),
	'darkshield-scanner'     => __( 'Scanner', 'darkshield' ),
	'darkshield-performance' => __( 'Performance', 'darkshield' ),
	'darkshield-settings'    => __( 'Settings', 'darkshield' ),
	'darkshield-log'         => __( 'Log', 'darkshield' ),
	'darkshield-whitelist'   => __( 'Whitelist', 'darkshield' ),
	'darkshield-about'       => __( 'About', 'darkshield' ),
);
?>
<nav class="nav-tab-wrapper">
	<?php foreach ( $tabs as $slug => $label ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"
			class="nav-tab <?php echo $current === $slug ? 'nav-tab-active' : ''; ?>">
			<?php echo esc_html( $label ); ?>
		</a>
	<?php endforeach; ?>
</nav>
