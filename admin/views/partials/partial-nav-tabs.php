<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$current = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
$tabs    = array(
	'darkshield'             => array( '🏠', __( 'Dashboard', 'darkshield' ) ),
	'darkshield-scanner'     => array( '🔍', __( 'Scanner', 'darkshield' ) ),
	'darkshield-performance' => array( '🚀', __( 'Performance', 'darkshield' ) ),
	'darkshield-settings'    => array( '⚙️', __( 'Settings', 'darkshield' ) ),
	'darkshield-log'         => array( '📋', __( 'Log', 'darkshield' ) ),
	'darkshield-whitelist'   => array( '📝', __( 'Whitelist', 'darkshield' ) ),
	'darkshield-rules'       => array( '🧩', __( 'Rules', 'darkshield' ) ),
	'darkshield-about'       => array( 'ℹ️', __( 'About', 'darkshield' ) ),
);
?>
<nav class="darkshield-nav" aria-label="<?php esc_attr_e( 'DarkShield sections', 'darkshield' ); ?>">
	<?php foreach ( $tabs as $slug => $tab ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"
			class="darkshield-nav-item <?php echo $current === $slug ? 'is-active' : ''; ?>">
			<span class="darkshield-nav-icon" aria-hidden="true"><?php echo esc_html( $tab[0] ); ?></span>
			<span class="darkshield-nav-label"><?php echo esc_html( $tab[1] ); ?></span>
		</a>
	<?php endforeach; ?>
</nav>
