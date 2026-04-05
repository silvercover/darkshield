<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'darkshield_log';
DarkShield_Utils::ensure_tables();
$ok = DarkShield_Utils::table_exists( $table );

// Clear log
if ( isset( $_POST['darkshield_clear_log'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'darkshield_clear_log' ) && $ok ) {
    $wpdb->query( "TRUNCATE TABLE {$table}" );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Log cleared.', 'darkshield' ) . '</p></div>';
}

// Filters
$fd  = isset( $_GET['log_domain'] ) ? sanitize_text_field( wp_unslash( $_GET['log_domain'] ) ) : '';
$ft  = isset( $_GET['log_type'] ) ? sanitize_text_field( wp_unslash( $_GET['log_type'] ) ) : '';
$fs  = isset( $_GET['log_source'] ) ? sanitize_text_field( wp_unslash( $_GET['log_source'] ) ) : '';
$fm  = isset( $_GET['log_mode'] ) ? sanitize_text_field( wp_unslash( $_GET['log_mode'] ) ) : '';
$fb  = isset( $_GET['log_status'] ) ? sanitize_text_field( wp_unslash( $_GET['log_status'] ) ) : '';
$fdf = isset( $_GET['log_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['log_date_from'] ) ) : '';
$fdt = isset( $_GET['log_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['log_date_to'] ) ) : '';

$w = array( '1=1' );
$a = array();
if ( $fd )        { $w[] = 'domain LIKE %s';        $a[] = '%' . $wpdb->esc_like( $fd ) . '%'; }
if ( $ft )        { $w[] = 'type = %s';             $a[] = $ft; }
if ( $fs )        { $w[] = 'source = %s';           $a[] = $fs; }
if ( $fm )        { $w[] = 'mode = %s';             $a[] = $fm; }
if ( '' !== $fb ) { $w[] = 'blocked = %d';           $a[] = (int) $fb; }
if ( $fdf )       { $w[] = 'DATE(created_at) >= %s'; $a[] = $fdf; }
if ( $fdt )       { $w[] = 'DATE(created_at) <= %s'; $a[] = $fdt; }

$ws    = implode( ' AND ', $w );
$paged = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
$pp    = 50;
$off   = ( $paged - 1 ) * $pp;

$total = 0; $logs = array();
$types = array(); $sources = array(); $modes = array();
$st = 0; $sb = 0; $sa = 0;

if ( $ok ) {
    if ( ! empty( $a ) ) {
        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$ws}", $a ) );
        $qa    = $a;
        $qa[]  = $pp;
        $qa[]  = $off;
        $logs  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$ws} ORDER BY created_at DESC LIMIT %d OFFSET %d", $qa ) );
    } else {
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        $logs  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $pp, $off ) );
    }
    $types   = $wpdb->get_col( "SELECT DISTINCT type FROM {$table} WHERE type!='' ORDER BY type" );
    $sources = $wpdb->get_col( "SELECT DISTINCT source FROM {$table} WHERE source!='' ORDER BY source" );
    $modes   = $wpdb->get_col( "SELECT DISTINCT mode FROM {$table} WHERE mode!='' ORDER BY mode" );
    $st      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    $sb      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE blocked=1" );
    $sa      = $st - $sb;
}

$tp = ceil( $total / max( $pp, 1 ) );
$hf = $fd || $ft || $fs || $fm || '' !== $fb || $fdf || $fdt;
?>

<div class="wrap">
    <h1>🛡️ <?php esc_html_e( 'DarkShield — Log', 'darkshield' ); ?></h1>
    <?php include DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

    <div style="margin-top:20px;">

        <!-- Stats -->
        <div style="display:flex;gap:15px;flex-wrap:wrap;margin-bottom:20px;">
            <div class="card" style="flex:1;min-width:130px;padding:15px;">
                <h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e( 'Total', 'darkshield' ); ?></h3>
                <p style="margin:0;font-size:22px;font-weight:bold;"><?php echo esc_html( number_format_i18n( $st ) ); ?></p>
            </div>
            <div class="card" style="flex:1;min-width:130px;padding:15px;">
                <h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e( 'Blocked', 'darkshield' ); ?></h3>
                <p style="margin:0;font-size:22px;font-weight:bold;color:#d63638;"><?php echo esc_html( number_format_i18n( $sb ) ); ?></p>
            </div>
            <div class="card" style="flex:1;min-width:130px;padding:15px;">
                <h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e( 'Allowed', 'darkshield' ); ?></h3>
                <p style="margin:0;font-size:22px;font-weight:bold;color:#00a32a;"><?php echo esc_html( number_format_i18n( $sa ) ); ?></p>
            </div>
            <div class="card" style="flex:1;min-width:130px;padding:15px;">
                <h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php echo $hf ? esc_html__( 'Filtered', 'darkshield' ) : esc_html__( 'Showing', 'darkshield' ); ?></h3>
                <p style="margin:0;font-size:22px;font-weight:bold;color:#2271b1;"><?php echo esc_html( number_format_i18n( $total ) ); ?></p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card" style="max-width:100%;padding:15px;margin-bottom:20px;">
            <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
                <input type="hidden" name="page" value="darkshield-log" />

                <label>
                    <span style="display:block;font-size:11px;font-weight:600;color:#666;"><?php esc_html_e( 'Domain', 'darkshield' ); ?></span>
                    <input type="text" name="log_domain" value="<?php echo esc_attr( $fd ); ?>" style="width:140px;" />
                </label>

                <label>
                    <span style="display:block;font-size:11px;font-weight:600;color:#666;"><?php esc_html_e( 'Type', 'darkshield' ); ?></span>
                    <select name="log_type">
                        <option value=""><?php esc_html_e( 'All', 'darkshield' ); ?></option>
                        <?php foreach ( $types as $t ) : ?>
                            <option value="<?php echo esc_attr( $t ); ?>" <?php selected( $ft, $t ); ?>><?php echo esc_html( ucfirst( $t ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span style="display:block;font-size:11px;font-weight:600;color:#666;"><?php esc_html_e( 'Source', 'darkshield' ); ?></span>
                    <select name="log_source">
                        <option value=""><?php esc_html_e( 'All', 'darkshield' ); ?></option>
                        <?php foreach ( $sources as $s ) : ?>
                            <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $fs, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span style="display:block;font-size:11px;font-weight:600;color:#666;"><?php esc_html_e( 'Mode', 'darkshield' ); ?></span>
                    <select name="log_mode">
                        <option value=""><?php esc_html_e( 'All', 'darkshield' ); ?></option>
                        <?php foreach ( $modes as $m ) : ?>
                            <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $fm, $m ); ?>><?php echo esc_html( ucfirst( $m ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span style="display:block;font-size:11px;font-weight:600;color:#666;"><?php esc_html_e( 'Status', 'darkshield' ); ?></span>
                    <select name="log_status">
                        <option value=""><?php esc_html_e( 'All', 'darkshield' ); ?></option>
                        <option value="1" <?php selected( $fb, '1' ); ?>><?php esc_html_e( 'Blocked', 'darkshield' ); ?></option>
                        <option value="0" <?php selected( $fb, '0' ); ?>><?php esc_html_e( 'Allowed', 'darkshield' ); ?></option>
                    </select>
                </label>

                <label>
                    <span style="display:block;font-size:11px;font-weight:600;color:#666;"><?php esc_html_e( 'From', 'darkshield' ); ?></span>
                    <input type="date" name="log_date_from" value="<?php echo esc_attr( $fdf ); ?>" />
                </label>

                <label>
                    <span style="display:block;font-size:11px;font-weight:600;color:#666;"><?php esc_html_e( 'To', 'darkshield' ); ?></span>
                    <input type="date" name="log_date_to" value="<?php echo esc_attr( $fdt ); ?>" />
                </label>

                <button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'darkshield' ); ?></button>
                <?php if ( $hf ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-log' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'darkshield' ); ?></a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;align-items:center;">
            <form method="post">
                <?php wp_nonce_field( 'darkshield_clear_log' ); ?>
                <button type="submit" name="darkshield_clear_log" value="1" class="button" style="color:#a00;"
                        onclick="return confirm('<?php esc_attr_e( 'Clear all logs?', 'darkshield' ); ?>');">
                    <?php esc_html_e( 'Clear All', 'darkshield' ); ?>
                </button>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-log' ) ); ?>">
                <?php wp_nonce_field( 'darkshield_log_actions' ); ?>
                <?php
                foreach ( array( 'log_domain', 'log_type', 'log_source', 'log_mode', 'log_status', 'log_date_from', 'log_date_to' ) as $ep ) {
                    $ev = isset( $_GET[ $ep ] ) ? sanitize_text_field( wp_unslash( $_GET[ $ep ] ) ) : '';
                    if ( '' !== $ev ) {
                        echo '<input type="hidden" name="' . esc_attr( $ep ) . '" value="' . esc_attr( $ev ) . '" />';
                    }
                }
                ?>
                <button type="submit" name="darkshield_export_log" value="1" class="button"><?php esc_html_e( 'Export CSV', 'darkshield' ); ?></button>
            </form>

            <?php if ( $hf ) : ?>
                <span style="color:#666;font-size:13px;font-style:italic;"><?php esc_html_e( 'Export includes filtered results only.', 'darkshield' ); ?></span>
            <?php endif; ?>
        </div>

        <!-- Table -->
        <?php if ( ! empty( $logs ) ) : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width:50px;"><?php esc_html_e( 'ID', 'darkshield' ); ?></th>
                    <th><?php esc_html_e( 'URL', 'darkshield' ); ?></th>
                    <th><?php esc_html_e( 'Domain', 'darkshield' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'darkshield' ); ?></th>
                    <th><?php esc_html_e( 'Source', 'darkshield' ); ?></th>
                    <th><?php esc_html_e( 'Mode', 'darkshield' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'darkshield' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'darkshield' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $mc = array( 'national' => '#dba617', 'offline' => '#d63638', 'normal' => '#00a32a' );
                $mi = array( 'national' => '🟡', 'offline' => '🔴', 'normal' => '🟢' );
                foreach ( $logs as $log ) :
                    $lc = isset( $mc[ $log->mode ] ) ? $mc[ $log->mode ] : '#666';
                    $li = isset( $mi[ $log->mode ] ) ? $mi[ $log->mode ] : '';
                ?>
                <tr>
                    <td><?php echo esc_html( $log->id ); ?></td>
                    <td><code style="font-size:11px;word-break:break-all;max-width:280px;display:inline-block;"><?php echo esc_html( DarkShield_Utils::truncate( $log->url, 100 ) ); ?></code></td>
                    <td>
                        <code><?php echo esc_html( $log->domain ); ?></code>
                        <?php if ( DarkShield_Utils::is_whitelisted( $log->domain ) ) : ?>
                            <br><small style="color:#2271b1;"><?php esc_html_e( 'whitelisted', 'darkshield' ); ?></small>
                        <?php endif; ?>
                        <?php if ( DarkShield_Utils::is_allowed_service( $log->domain ) ) : ?>
                            <br><small style="color:#00a32a;"><?php esc_html_e( 'service', 'darkshield' ); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="darkshield-badge"><?php echo esc_html( ucfirst( $log->type ) ); ?></span></td>
                    <td style="font-size:12px;"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $log->source ) ) ); ?></td>
                    <td><span style="color:<?php echo esc_attr( $lc ); ?>;font-weight:600;"><?php echo esc_html( $li . ' ' . ucfirst( $log->mode ) ); ?></span></td>
                    <td>
                        <?php if ( $log->blocked ) : ?>
                            <span style="color:#d63638;font-weight:600;">&#10007; <?php esc_html_e( 'Blocked', 'darkshield' ); ?></span>
                        <?php else : ?>
                            <span style="color:#00a32a;font-weight:600;">&#10003; <?php esc_html_e( 'Allowed', 'darkshield' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;font-size:12px;">
                        <?php echo esc_html( $log->created_at ); ?>
                        <br><small style="color:#999;"><?php echo esc_html( human_time_diff( strtotime( $log->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'darkshield' ) ); ?></small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ( $tp > 1 ) : ?>
        <div class="tablenav bottom" style="margin-top:10px;">
            <div class="tablenav-pages">
                <?php
                $base = admin_url( 'admin.php?page=darkshield-log' );
                $fp   = compact( 'fd', 'ft', 'fs', 'fm', 'fb', 'fdf', 'fdt' );
                $fmap = array( 'fd' => 'log_domain', 'ft' => 'log_type', 'fs' => 'log_source', 'fm' => 'log_mode', 'fb' => 'log_status', 'fdf' => 'log_date_from', 'fdt' => 'log_date_to' );
                foreach ( $fmap as $var => $param ) {
                    if ( ! empty( $$var ) || '0' === $$var ) {
                        $base = add_query_arg( $param, $$var, $base );
                    }
                }
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%', $base ),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $tp,
                    'current'   => $paged,
                ) );
                ?>
                <span class="displaying-num" style="margin-left:10px;">
                    <?php printf( esc_html__( '%1$s–%2$s of %3$s', 'darkshield' ), number_format_i18n( $off + 1 ), number_format_i18n( min( $off + $pp, $total ) ), number_format_i18n( $total ) ); ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <?php elseif ( $ok ) : ?>
            <div class="card" style="padding:20px;">
                <?php if ( $hf ) : ?>
                    <p><?php esc_html_e( 'No entries match your filters.', 'darkshield' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-log' ) ); ?>"><?php esc_html_e( 'Reset', 'darkshield' ); ?></a></p>
                <?php else : ?>
                    <p><?php esc_html_e( 'No log entries yet. Blocked requests will appear here.', 'darkshield' ); ?></p>
                    <?php if ( 'normal' === DarkShield_Utils::get_mode() ) : ?>
                        <p style="color:#666;"><?php esc_html_e( 'DarkShield is in Normal mode. Switch to National or Offline to start blocking.', 'darkshield' ); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="notice notice-error"><p><?php esc_html_e( 'Log table missing. Deactivate and reactivate the plugin.', 'darkshield' ); ?></p></div>
        <?php endif; ?>

        <!-- Log Info -->
        <?php if ( $ok && $st > 0 ) : ?>
        <div class="card" style="max-width:100%;padding:15px;margin-top:20px;">
            <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;color:#666;">
                <span><strong><?php esc_html_e( 'Retention:', 'darkshield' ); ?></strong> <?php printf( esc_html__( '%d days', 'darkshield' ), DarkShield_Utils::get_setting( 'log_retention', 30 ) ); ?></span>
                <span><strong><?php esc_html_e( 'Logging:', 'darkshield' ); ?></strong>
                    <?php echo DarkShield_Utils::get_setting( 'log_enabled', 1 )
                        ? '<span style="color:#00a32a;">' . esc_html__( 'Enabled', 'darkshield' ) . '</span>'
                        : '<span style="color:#d63638;">' . esc_html__( 'Disabled', 'darkshield' ) . '</span>'; ?>
                </span>
                <span><strong><?php esc_html_e( 'Block rate:', 'darkshield' ); ?></strong> <?php echo esc_html( $st > 0 ? round( ( $sb / $st ) * 100, 1 ) . '%' : '0%' ); ?></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-settings' ) ); ?>" style="margin-left:auto;"><?php esc_html_e( 'Log Settings →', 'darkshield' ); ?></a>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
