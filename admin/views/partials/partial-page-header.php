<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Shared branded page header.
 *
 * Expects $darkshield_page_title and $darkshield_page_subtitle to be set
 * by the including view before this partial is required.
 */
$title    = isset( $darkshield_page_title ) ? $darkshield_page_title : __( 'DarkShield', 'darkshield' );
$subtitle = isset( $darkshield_page_subtitle ) ? $darkshield_page_subtitle : '';
$mode     = DarkShield_Utils::get_mode();
$mode_dot = array(
	'normal'   => '🟢',
	'national' => '🟡',
	'offline'  => '🔴',
);
$dot      = isset( $mode_dot[ $mode ] ) ? $mode_dot[ $mode ] : '⚪';
?>
<div class="darkshield-header">
	<div>
		<h1><span class="darkshield-brand-mark">🛡️</span> <?php echo esc_html( $title ); ?></h1>
		<?php if ( $subtitle ) : ?>
			<p class="darkshield-subtitle"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
	</div>
	<div class="darkshield-header-meta">
		<span class="darkshield-header-chip"><?php echo esc_html( $dot . ' ' . DarkShield_Utils::get_mode_label() ); ?></span>
	</div>
</div>
