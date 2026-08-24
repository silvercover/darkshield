<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$repo = new DarkShield_Rule_Repository();

// Save (add or edit)
if ( isset( $_POST['darkshield_save_rule'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'darkshield_rule_save' ) ) {
	$data = array(
		'id'             => isset( $_POST['rule_id'] ) ? (int) $_POST['rule_id'] : 0,
		'name'           => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
		'enabled'        => isset( $_POST['enabled'] ) ? 1 : 0,
		'action'         => isset( $_POST['action'] ) ? wp_unslash( $_POST['action'] ) : 'allow',
		'priority'       => isset( $_POST['priority'] ) ? (int) $_POST['priority'] : 10,
		'match_type'     => isset( $_POST['match_type'] ) ? wp_unslash( $_POST['match_type'] ) : 'suffix',
		'pattern'        => isset( $_POST['pattern'] ) ? wp_unslash( $_POST['pattern'] ) : '',
		'path_pattern'   => isset( $_POST['path_pattern'] ) ? wp_unslash( $_POST['path_pattern'] ) : '',
		'resource_types' => isset( $_POST['resource_types'] ) && is_array( $_POST['resource_types'] ) ? implode( ',', array_map( 'sanitize_key', wp_unslash( $_POST['resource_types'] ) ) ) : '',
		'role_condition' => isset( $_POST['role_condition'] ) ? wp_unslash( $_POST['role_condition'] ) : '',
		'page_condition' => isset( $_POST['page_condition'] ) ? wp_unslash( $_POST['page_condition'] ) : '',
		'schedule_start' => isset( $_POST['schedule_start'] ) ? wp_unslash( $_POST['schedule_start'] ) : '',
		'schedule_end'   => isset( $_POST['schedule_end'] ) ? wp_unslash( $_POST['schedule_end'] ) : '',
	);

	$result = $repo->save( $data );

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule saved.', 'darkshield' ) . '</p></div>';
	}
}

// Delete
if ( isset( $_POST['darkshield_delete_rule'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'darkshield_rule_delete' ) ) {
	$repo->delete( (int) $_POST['darkshield_delete_rule'] );
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule deleted.', 'darkshield' ) . '</p></div>';
}

// Toggle
if ( isset( $_POST['darkshield_toggle_rule'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'darkshield_rule_toggle' ) ) {
	$repo->toggle( (int) $_POST['darkshield_toggle_rule'] );
}

$rules      = $repo->get_all();
$edit_id    = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$edit_rule  = $edit_id ? $repo->get( $edit_id ) : null;
$resource_type_options = array(
	'script'  => __( 'Script', 'darkshield' ),
	'style'   => __( 'Style', 'darkshield' ),
	'font'    => __( 'Font', 'darkshield' ),
	'image'   => __( 'Image', 'darkshield' ),
	'iframe'  => __( 'Iframe', 'darkshield' ),
	'http'    => __( 'HTTP Request', 'darkshield' ),
);

// URL simulator
$sim_result = null;
if ( isset( $_POST['darkshield_simulate'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'darkshield_rule_simulate' ) ) {
	$sim_url = isset( $_POST['sim_url'] ) ? esc_url_raw( wp_unslash( $_POST['sim_url'] ) ) : '';
	if ( $sim_url ) {
		$blocked    = DarkShield_Utils::should_block( $sim_url );
		$sim_result = $blocked
			? __( 'This URL would be BLOCKED.', 'darkshield' )
			: __( 'This URL would be ALLOWED.', 'darkshield' );
	}
}

$darkshield_page_title    = __( 'Rules', 'darkshield' );
$darkshield_page_subtitle = __( 'Fine-grained allow/deny rules by domain, path, role, page and resource type.', 'darkshield' );
?>

<div class="wrap darkshield">
	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-page-header.php'; ?>
	<?php require DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

	<div>
		<div class="darkshield-stats-row">
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Total Rules', 'darkshield' ); ?></h3>
				<p style="--ds-stat-color:#0284c7;"><?php echo esc_html( count( $rules ) ); ?></p>
			</div>
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Allow Rules', 'darkshield' ); ?></h3>
				<p style="--ds-stat-color:#16a34a;"><?php echo esc_html( count( array_filter( $rules, function ( $r ) { return 'allow' === $r['action']; } ) ) ); ?></p>
			</div>
			<div class="darkshield-stat-card">
				<h3><?php esc_html_e( 'Deny Rules', 'darkshield' ); ?></h3>
				<p style="--ds-stat-color:#dc2626;"><?php echo esc_html( count( array_filter( $rules, function ( $r ) { return 'deny' === $r['action']; } ) ) ); ?></p>
			</div>
		</div>

		<!-- Add / Edit Rule -->
		<div class="card" style="max-width:100%;padding:20px;">
			<h2 style="margin-top:0;"><?php echo $edit_rule ? esc_html__( 'Edit Rule', 'darkshield' ) : esc_html__( 'Add Rule', 'darkshield' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'darkshield_rule_save' ); ?>
				<input type="hidden" name="rule_id" value="<?php echo esc_attr( $edit_rule ? $edit_rule['id'] : 0 ); ?>" />

				<table class="form-table">
					<tr>
						<th><label for="ds-rule-name"><?php esc_html_e( 'Name', 'darkshield' ); ?></label></th>
						<td><input type="text" id="ds-rule-name" name="name" class="regular-text" value="<?php echo esc_attr( $edit_rule ? $edit_rule['name'] : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Allow payment API', 'darkshield' ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="ds-rule-action"><?php esc_html_e( 'Action', 'darkshield' ); ?></label></th>
						<td>
							<select id="ds-rule-action" name="action">
								<option value="allow" <?php selected( $edit_rule ? $edit_rule['action'] : 'allow', 'allow' ); ?>><?php esc_html_e( 'Allow', 'darkshield' ); ?></option>
								<option value="deny" <?php selected( $edit_rule ? $edit_rule['action'] : '', 'deny' ); ?>><?php esc_html_e( 'Deny', 'darkshield' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ds-rule-priority"><?php esc_html_e( 'Priority', 'darkshield' ); ?></label></th>
						<td>
							<input type="number" id="ds-rule-priority" name="priority" value="<?php echo esc_attr( $edit_rule ? $edit_rule['priority'] : 10 ); ?>" style="width:80px;" />
							<p class="description"><?php esc_html_e( 'Lower number is evaluated first. First matching rule wins.', 'darkshield' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="ds-rule-match-type"><?php esc_html_e( 'Domain Match Type', 'darkshield' ); ?></label></th>
						<td>
							<select id="ds-rule-match-type" name="match_type">
								<?php foreach ( array( 'suffix', 'exact', 'wildcard', 'regex' ) as $mt ) : ?>
									<option value="<?php echo esc_attr( $mt ); ?>" <?php selected( $edit_rule ? $edit_rule['match_type'] : 'suffix', $mt ); ?>><?php echo esc_html( ucfirst( $mt ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ds-rule-pattern"><?php esc_html_e( 'Domain Pattern', 'darkshield' ); ?></label></th>
						<td><input type="text" id="ds-rule-pattern" name="pattern" class="regular-text" value="<?php echo esc_attr( $edit_rule ? $edit_rule['pattern'] : '' ); ?>" placeholder="api.example.com" /></td>
					</tr>
					<tr>
						<th><label for="ds-rule-path"><?php esc_html_e( 'Path Pattern', 'darkshield' ); ?></label></th>
						<td>
							<input type="text" id="ds-rule-path" name="path_pattern" class="regular-text" value="<?php echo esc_attr( $edit_rule ? $edit_rule['path_pattern'] : '' ); ?>" placeholder="/payment/*" />
							<p class="description"><?php esc_html_e( 'Optional. Must also match for the rule to apply.', 'darkshield' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Resource Types', 'darkshield' ); ?></th>
						<td>
							<?php
							$selected_types = $edit_rule ? array_map( 'trim', explode( ',', $edit_rule['resource_types'] ) ) : array();
							foreach ( $resource_type_options as $rt_key => $rt_label ) :
								?>
								<label style="margin-right:12px;">
									<input type="checkbox" name="resource_types[]" value="<?php echo esc_attr( $rt_key ); ?>" <?php checked( in_array( $rt_key, $selected_types, true ) ); ?> />
									<?php echo esc_html( $rt_label ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Leave all unchecked to match every resource type.', 'darkshield' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="ds-rule-role"><?php esc_html_e( 'Role Condition', 'darkshield' ); ?></label></th>
						<td>
							<select id="ds-rule-role" name="role_condition">
								<option value=""><?php esc_html_e( 'Any', 'darkshield' ); ?></option>
								<option value="logged_in" <?php selected( $edit_rule ? $edit_rule['role_condition'] : '', 'logged_in' ); ?>><?php esc_html_e( 'Logged-in users', 'darkshield' ); ?></option>
								<option value="logged_out" <?php selected( $edit_rule ? $edit_rule['role_condition'] : '', 'logged_out' ); ?>><?php esc_html_e( 'Guests (logged-out)', 'darkshield' ); ?></option>
								<option value="manage_options" <?php selected( $edit_rule ? $edit_rule['role_condition'] : '', 'manage_options' ); ?>><?php esc_html_e( 'Administrators', 'darkshield' ); ?></option>
								<option value="edit_posts" <?php selected( $edit_rule ? $edit_rule['role_condition'] : '', 'edit_posts' ); ?>><?php esc_html_e( 'Authors/Editors', 'darkshield' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ds-rule-page"><?php esc_html_e( 'Page Condition', 'darkshield' ); ?></label></th>
						<td>
							<select id="ds-rule-page" name="page_condition">
								<option value=""><?php esc_html_e( 'Any page', 'darkshield' ); ?></option>
								<option value="front_page" <?php selected( $edit_rule ? $edit_rule['page_condition'] : '', 'front_page' ); ?>><?php esc_html_e( 'Front page', 'darkshield' ); ?></option>
								<?php if ( class_exists( 'WooCommerce' ) ) : ?>
									<option value="woocommerce_shop" <?php selected( $edit_rule ? $edit_rule['page_condition'] : '', 'woocommerce_shop' ); ?>><?php esc_html_e( 'WooCommerce shop', 'darkshield' ); ?></option>
									<option value="woocommerce_cart" <?php selected( $edit_rule ? $edit_rule['page_condition'] : '', 'woocommerce_cart' ); ?>><?php esc_html_e( 'WooCommerce cart', 'darkshield' ); ?></option>
									<option value="woocommerce_checkout" <?php selected( $edit_rule ? $edit_rule['page_condition'] : '', 'woocommerce_checkout' ); ?>><?php esc_html_e( 'WooCommerce checkout', 'darkshield' ); ?></option>
								<?php endif; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Schedule Window', 'darkshield' ); ?></th>
						<td>
							<input type="time" name="schedule_start" value="<?php echo esc_attr( $edit_rule && $edit_rule['schedule_start'] ? substr( $edit_rule['schedule_start'], 0, 5 ) : '' ); ?>" />
							&nbsp;–&nbsp;
							<input type="time" name="schedule_end" value="<?php echo esc_attr( $edit_rule && $edit_rule['schedule_end'] ? substr( $edit_rule['schedule_end'], 0, 5 ) : '' ); ?>" />
							<p class="description"><?php esc_html_e( 'Optional. Leave both empty to apply at any time.', 'darkshield' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="ds-rule-enabled"><?php esc_html_e( 'Enabled', 'darkshield' ); ?></label></th>
						<td><input type="checkbox" id="ds-rule-enabled" name="enabled" <?php checked( $edit_rule ? (bool) $edit_rule['enabled'] : true ); ?> /></td>
					</tr>
				</table>

				<p>
					<button type="submit" name="darkshield_save_rule" value="1" class="button button-primary"><?php echo $edit_rule ? esc_html__( 'Update Rule', 'darkshield' ) : esc_html__( 'Add Rule', 'darkshield' ); ?></button>
					<?php if ( $edit_rule ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-rules' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'darkshield' ); ?></a>
					<?php endif; ?>
				</p>
			</form>
		</div>

		<!-- Test URL -->
		<div class="card" style="max-width:100%;padding:20px;margin-top:20px;">
			<h2 style="margin-top:0;"><?php esc_html_e( 'Test a URL', 'darkshield' ); ?></h2>
			<form method="post" style="display:flex;gap:10px;">
				<?php wp_nonce_field( 'darkshield_rule_simulate' ); ?>
				<input type="text" name="sim_url" class="regular-text" placeholder="https://example.com/path" value="<?php echo isset( $_POST['sim_url'] ) ? esc_attr( wp_unslash( $_POST['sim_url'] ) ) : ''; ?>" />
				<button type="submit" name="darkshield_simulate" value="1" class="button"><?php esc_html_e( 'Simulate', 'darkshield' ); ?></button>
			</form>
			<?php if ( null !== $sim_result ) : ?>
				<p style="margin-top:10px;font-weight:600;"><?php echo esc_html( $sim_result ); ?></p>
			<?php endif; ?>
		</div>

		<!-- Rules Table -->
		<?php if ( ! empty( $rules ) ) : ?>
			<div class="darkshield-table-wrap" style="margin-top:20px;">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Priority', 'darkshield' ); ?></th>
						<th><?php esc_html_e( 'Name', 'darkshield' ); ?></th>
						<th><?php esc_html_e( 'Action', 'darkshield' ); ?></th>
						<th><?php esc_html_e( 'Pattern', 'darkshield' ); ?></th>
						<th><?php esc_html_e( 'Hits', 'darkshield' ); ?></th>
						<th><?php esc_html_e( 'Enabled', 'darkshield' ); ?></th>
						<th style="width:160px;"><?php esc_html_e( 'Actions', 'darkshield' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rules as $rule ) : ?>
						<tr>
							<td><?php echo esc_html( $rule['priority'] ); ?></td>
							<td><?php echo esc_html( $rule['name'] ? $rule['name'] : '—' ); ?></td>
							<td>
								<?php if ( 'deny' === $rule['action'] ) : ?>
									<span style="color:#dc2626;font-weight:600;"><?php esc_html_e( 'Deny', 'darkshield' ); ?></span>
								<?php else : ?>
									<span style="color:#16a34a;font-weight:600;"><?php esc_html_e( 'Allow', 'darkshield' ); ?></span>
								<?php endif; ?>
							</td>
							<td><code><?php echo esc_html( $rule['pattern'] ); ?></code><?php echo $rule['path_pattern'] ? ' <code>' . esc_html( $rule['path_pattern'] ) . '</code>' : ''; ?></td>
							<td><?php echo esc_html( $rule['hit_count'] ); ?></td>
							<td>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'darkshield_rule_toggle' ); ?>
									<button type="submit" name="darkshield_toggle_rule" value="<?php echo esc_attr( $rule['id'] ); ?>" class="button button-small">
										<?php echo $rule['enabled'] ? esc_html__( 'On', 'darkshield' ) : esc_html__( 'Off', 'darkshield' ); ?>
									</button>
								</form>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=darkshield-rules&edit=' . $rule['id'] ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'darkshield' ); ?></a>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'darkshield_rule_delete' ); ?>
									<button type="submit" name="darkshield_delete_rule" value="<?php echo esc_attr( $rule['id'] ); ?>" class="button button-small" style="color:#a00;"
										onclick="return confirm('<?php esc_attr_e( 'Delete this rule?', 'darkshield' ); ?>');">
										<?php esc_html_e( 'Delete', 'darkshield' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
		<?php else : ?>
			<div class="card" style="padding:20px;margin-top:20px;">
				<p><?php esc_html_e( 'No rules yet. Add one above to override the default whitelist/mode behavior for specific roles, pages, paths or resource types.', 'darkshield' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
