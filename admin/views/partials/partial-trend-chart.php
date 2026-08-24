<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Self-hosted inline SVG bar chart — no external charting library.
 * Expects $darkshield_trend_data: array of [ 'd' => 'Y-m-d', 'total' => int, 'blocked' => int ].
 */
$data = isset( $darkshield_trend_data ) ? $darkshield_trend_data : array();

$max = 1;
foreach ( $data as $row ) {
	$max = max( $max, (int) $row['total'] );
}

$width       = 560;
$height      = 140;
$bar_gap     = 4;
$count       = max( 1, count( $data ) );
$bar_width   = ( $width - ( $bar_gap * ( $count - 1 ) ) ) / $count;
?>
<div class="darkshield-trend-chart">
	<?php if ( empty( $data ) ) : ?>
		<p style="color:#666;"><?php esc_html_e( 'Not enough data yet.', 'darkshield' ); ?></p>
	<?php else : ?>
		<svg viewBox="0 0 <?php echo esc_attr( $width ); ?> <?php echo esc_attr( $height + 20 ); ?>" width="100%" height="<?php echo esc_attr( $height + 20 ); ?>" role="img" aria-label="<?php esc_attr_e( 'Blocked resources trend', 'darkshield' ); ?>">
			<?php
			$x = 0;
			foreach ( $data as $row ) :
				$total   = (int) $row['total'];
				$blocked = (int) $row['blocked'];
				$h_total = $max > 0 ? ( $total / $max ) * $height : 0;
				$h_block = $max > 0 ? ( $blocked / $max ) * $height : 0;
				$y_total = $height - $h_total;
				$y_block = $height - $h_block;
				?>
				<rect x="<?php echo esc_attr( $x ); ?>" y="<?php echo esc_attr( $y_total ); ?>" width="<?php echo esc_attr( $bar_width ); ?>" height="<?php echo esc_attr( $h_total ); ?>" fill="#c7d2fe">
					<title><?php echo esc_html( $row['d'] . ': ' . $total . ' total' ); ?></title>
				</rect>
				<rect x="<?php echo esc_attr( $x ); ?>" y="<?php echo esc_attr( $y_block ); ?>" width="<?php echo esc_attr( $bar_width ); ?>" height="<?php echo esc_attr( $h_block ); ?>" fill="#dc2626">
					<title><?php echo esc_html( $row['d'] . ': ' . $blocked . ' blocked' ); ?></title>
				</rect>
				<text x="<?php echo esc_attr( $x + $bar_width / 2 ); ?>" y="<?php echo esc_attr( $height + 14 ); ?>" font-size="9" text-anchor="middle" fill="#666"><?php echo esc_html( substr( $row['d'], 5 ) ); ?></text>
				<?php
				$x += $bar_width + $bar_gap;
			endforeach;
			?>
		</svg>
		<p style="margin-top:6px;font-size:12px;color:#666;">
			<span style="display:inline-block;width:10px;height:10px;background:#c7d2fe;margin-right:4px;"></span><?php esc_html_e( 'Total', 'darkshield' ); ?>
			&nbsp;&nbsp;
			<span style="display:inline-block;width:10px;height:10px;background:#dc2626;margin-right:4px;"></span><?php esc_html_e( 'Blocked', 'darkshield' ); ?>
		</p>
	<?php endif; ?>
</div>
