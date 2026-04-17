<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$table = $wpdb->prefix . 'darkshield_scan_results';
if ( ! DarkShield_Utils::table_exists( $table ) ) {
	echo '<p>' . esc_html__( 'Scan table not found.', 'darkshield' ) . '</p>';
	return;
}
$results = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY domain ASC, created_at DESC LIMIT 500" );
if ( empty( $results ) ) {
	echo '<p>' . esc_html__( 'No scan results yet.', 'darkshield' ) . '</p>';
	return;
}
?>
<table class="widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Domain', 'darkshield' ); ?></th>
			<th><?php esc_html_e( 'URL', 'darkshield' ); ?></th>
			<th><?php esc_html_e( 'Type', 'darkshield' ); ?></th>
			<th><?php esc_html_e( 'Location', 'darkshield' ); ?></th>
			<th><?php esc_html_e( 'Line', 'darkshield' ); ?></th>
			<th><?php esc_html_e( 'Status', 'darkshield' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $results as $r ) : ?>
			<tr>
				<td><code><?php echo esc_html( $r->domain ); ?></code></td>
				<td><code style="font-size:11px;word-break:break-all;"><?php echo esc_html( DarkShield_Utils::truncate( $r->url, 80 ) ); ?></code></td>
				<td><span class="darkshield-badge"><?php echo esc_html( ucfirst( $r->type ) ); ?></span></td>
				<td style="font-size:11px;"><?php echo esc_html( $r->file_path ); ?></td>
				<td><?php echo $r->line_number > 0 ? esc_html( $r->line_number ) : '—'; ?></td>
				<td>
					<?php if ( 'blocked' === $r->status ) : ?>
						<span style="color:#d63638;font-weight:600;">&#10007; <?php esc_html_e( 'Blocked', 'darkshield' ); ?></span>
					<?php else : ?>
						<span style="color:#dba617;">&#9888; <?php esc_html_e( 'Detected', 'darkshield' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<p style="color:#666;font-size:12px;margin-top:10px;">
	<?php
	/* translators: %d: number of scan results displayed */
	printf( esc_html__( 'Showing %d results (max 500).', 'darkshield' ), count( $results ) );
	?>
</p>