<?php
if (! defined('ABSPATH')) {
    exit;
}

$wl = new DarkShield_Whitelist();
$whitelist = $wl->get_all();

// Add
if (isset($_POST['darkshield_add_domain']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'darkshield_whitelist_action')) {
    $nd = isset($_POST['new_domain']) ? sanitize_text_field(wp_unslash($_POST['new_domain'])) : '';
    if ($wl->add($nd)) {
        $whitelist = $wl->get_all();
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Domain added.', 'darkshield') . '</p></div>';
    } else {
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Already exists or invalid.', 'darkshield') . '</p></div>';
    }
}

// Remove
if (isset($_POST['darkshield_remove_domain']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'darkshield_whitelist_action')) {
    $wl->remove(sanitize_text_field(wp_unslash($_POST['darkshield_remove_domain'])));
    $whitelist = $wl->get_all();
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Domain removed.', 'darkshield') . '</p></div>';
}

// Import
if (isset($_POST['darkshield_import_whitelist']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'darkshield_whitelist_action')) {
    $raw = isset($_POST['import_domains']) ? sanitize_textarea_field(wp_unslash($_POST['import_domains'])) : '';
    $domains = array_filter(array_map('trim', explode("\n", $raw)));
    if (! empty($domains)) {
        $wl->import($domains);
        $whitelist = $wl->get_all();
        /* translators: %d: number of domains processed during import */
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d domains processed.', 'darkshield'), count($domains)) . '</p></div>';
    }
}

// Clear
if (isset($_POST['darkshield_clear_whitelist']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'darkshield_whitelist_action')) {
    $wl->clear();
    $whitelist = array();
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Whitelist cleared.', 'darkshield') . '</p></div>';
}

// Search
$search = isset($_GET['wl_search']) ? sanitize_text_field(wp_unslash($_GET['wl_search'])) : '';
$filtered = $whitelist;
if ($search) {
    $filtered = array_filter($whitelist, function ($d) use ($search) {
        return strpos($d, $search) !== false;
    });
}

$services = DarkShield_Utils::get_allowed_services();
?>

<div class="wrap">
    <h1>🛡️ <?php esc_html_e('DarkShield — Whitelist', 'darkshield'); ?></h1>
    <?php include DARKSHIELD_PLUGIN_DIR . 'admin/views/partials/partial-nav-tabs.php'; ?>

    <div style="margin-top:20px;">

        <!-- Stats -->
        <div style="display:flex;gap:15px;flex-wrap:wrap;margin-bottom:20px;">
            <div class="card" style="flex:1;min-width:140px;padding:15px;">
                <h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e('Whitelisted', 'darkshield'); ?></h3>
                <p style="margin:0;font-size:22px;font-weight:bold;color:#2271b1;"><?php echo esc_html(count($whitelist)); ?></p>
            </div>
            <div class="card" style="flex:1;min-width:140px;padding:15px;">
                <h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e('Services', 'darkshield'); ?></h3>
                <p style="margin:0;font-size:22px;font-weight:bold;color:#00a32a;"><?php echo esc_html(count($services)); ?></p>
            </div>
            <div class="card" style="flex:1;min-width:140px;padding:15px;">
                <h3 style="margin:0 0 5px;font-size:12px;color:#666;"><?php esc_html_e('Mode', 'darkshield'); ?></h3>
                <?php
                $mi = array('normal' => '🟢', 'national' => '🟡', 'offline' => '🔴');
                $mode = DarkShield_Utils::get_mode();
                ?>
                <p style="margin:0;font-size:22px;font-weight:bold;"><?php echo esc_html((isset($mi[$mode]) ? $mi[$mode] . ' ' : '') . DarkShield_Utils::get_mode_label()); ?></p>
            </div>
        </div>

        <!-- Add Domain -->
        <div class="card" style="max-width:100%;padding:20px;margin-bottom:20px;">
            <h2 style="margin-top:0;"><?php esc_html_e('Add Domain', 'darkshield'); ?></h2>
            <p><?php esc_html_e('Whitelisted domains are never blocked, regardless of mode.', 'darkshield'); ?></p>
            <form method="post" style="display:flex;gap:10px;">
                <?php wp_nonce_field('darkshield_whitelist_action'); ?>
                <input type="text" name="new_domain" placeholder="example.com" class="regular-text" required />
                <button type="submit" name="darkshield_add_domain" value="1" class="button button-primary"><?php esc_html_e('Add', 'darkshield'); ?></button>
            </form>
        </div>

        <!-- Bulk Import -->
        <div class="card" style="max-width:100%;padding:20px;margin-bottom:20px;">
            <h2 style="margin-top:0;"><?php esc_html_e('Bulk Import', 'darkshield'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('darkshield_whitelist_action'); ?>
                <textarea name="import_domains" rows="4" class="large-text code" placeholder="domain1.com&#10;domain2.com"></textarea>
                <p style="margin-top:10px;">
                    <button type="submit" name="darkshield_import_whitelist" value="1" class="button"><?php esc_html_e('Import', 'darkshield'); ?></button>
                </p>
            </form>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;">
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=darkshield-whitelist')); ?>">
                <?php wp_nonce_field('darkshield_whitelist_action'); ?>
                <button type="submit" name="darkshield_export_whitelist" value="1" class="button"><?php esc_html_e('Export', 'darkshield'); ?></button>
            </form>
            <form method="post">
                <?php wp_nonce_field('darkshield_whitelist_action'); ?>
                <button type="submit" name="darkshield_clear_whitelist" value="1" class="button" style="color:#a00;"
                    onclick="return confirm('<?php esc_attr_e('Clear entire whitelist?', 'darkshield'); ?>');">
                    <?php esc_html_e('Clear All', 'darkshield'); ?>
                </button>
            </form>
        </div>

        <!-- Search -->
        <?php if (count($whitelist) > 5) : ?>
            <form method="get" style="margin-bottom:15px;">
                <input type="hidden" name="page" value="darkshield-whitelist" />
                <div style="display:flex;gap:10px;">
                    <input type="text" name="wl_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search...', 'darkshield'); ?>" class="regular-text" />
                    <button type="submit" class="button"><?php esc_html_e('Search', 'darkshield'); ?></button>
                    <?php if ($search) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=darkshield-whitelist')); ?>" class="button"><?php esc_html_e('Reset', 'darkshield'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>

        <!-- Whitelist Table -->
        <?php if (! empty($filtered)) : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th><?php esc_html_e('Domain', 'darkshield'); ?></th>
                        <th><?php esc_html_e('Iranian', 'darkshield'); ?></th>
                        <th><?php esc_html_e('Also in Services', 'darkshield'); ?></th>
                        <th style="width:100px;"><?php esc_html_e('Action', 'darkshield'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($filtered as $domain) : ?>
                        <tr>
                            <td><?php echo esc_html($i++); ?></td>
                            <td><code><?php echo esc_html($domain); ?></code></td>
                            <td>
                                <?php if (DarkShield_Utils::is_iranian_domain($domain)) : ?>
                                    <span style="color:#00a32a;">✓ <?php esc_html_e('Yes', 'darkshield'); ?></span>
                                <?php else : ?>
                                    <span style="color:#999;">✗ <?php esc_html_e('No', 'darkshield'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (DarkShield_Utils::is_allowed_service($domain)) : ?>
                                    <span style="color:#2271b1;">✓</span>
                                <?php else : ?>
                                    <span style="color:#999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('darkshield_whitelist_action'); ?>
                                    <button type="submit" name="darkshield_remove_domain" value="<?php echo esc_attr($domain); ?>"
                                        class="button button-small" style="color:#a00;"
                                        onclick="return confirm('<?php esc_attr_e('Remove?', 'darkshield'); ?>');">
                                        <?php esc_html_e('Remove', 'darkshield'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($search) : ?>
                <p style="margin-top:10px;color:#666;">
                    <?php
                    /* translators: %1$d: number of filtered results, %2$d: total whitelist count, %3$s: search term */
                    printf(esc_html__('Showing %1$d of %2$d matching "%3$s"', 'darkshield'), count($filtered), count($whitelist), esc_html($search)); ?>
                </p>
            <?php endif; ?>

        <?php else : ?>
            <div class="card" style="padding:20px;">
                <?php if ($search) : ?>
                    <p><?php esc_html_e('No domains match your search.', 'darkshield'); ?></p>
                <?php else : ?>
                    <p><?php esc_html_e('Whitelist is empty. Add domains above.', 'darkshield'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Allowed Services Info -->
        <?php if (! empty($services)) : ?>
            <div class="card" style="max-width:100%;padding:20px;margin-top:20px;">
                <h2 style="margin-top:0;">
                    <?php esc_html_e('Allowed Services', 'darkshield'); ?>
                    <span style="font-size:13px;font-weight:normal;color:#666;">(<?php esc_html_e('from Settings', 'darkshield'); ?>)</span>
                </h2>
                <p style="color:#666;font-size:13px;"><?php esc_html_e('These are allowed in all modes and don\'t need to be whitelisted separately.', 'darkshield'); ?></p>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">
                    <?php foreach ($services as $svc) : ?>
                        <span style="padding:3px 10px;background:#f0f6fc;border:1px solid #c3c4c7;border-radius:3px;font-size:12px;">
                            <code style="background:none;padding:0;"><?php echo esc_html($svc); ?></code>
                        </span>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top:10px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=darkshield-settings')); ?>" class="button button-small"><?php esc_html_e('Manage Services', 'darkshield'); ?></a>
                </p>
            </div>
        <?php endif; ?>

        <!-- How It Works -->
        <div class="card" style="max-width:100%;padding:20px;margin-top:20px;">
            <h2 style="margin-top:0;"><?php esc_html_e('How It Works', 'darkshield'); ?></h2>
            <div style="background:#f9f9f9;padding:15px;border-radius:4px;font-size:13px;line-height:2;">
                <code style="display:block;">
                    1. <?php esc_html_e('Normal mode → Always allowed', 'darkshield'); ?> ✅<br>
                    2. <?php esc_html_e('Local/same-site → Always allowed', 'darkshield'); ?> ✅<br>
                    3. <strong><?php esc_html_e('Whitelisted → Always allowed', 'darkshield'); ?></strong> ✅<br>
                    4. <strong><?php esc_html_e('Allowed service → Always allowed', 'darkshield'); ?></strong> ✅<br>
                    5. <?php esc_html_e('Messenger API (if enabled) → Allowed', 'darkshield'); ?> ✅<br>
                    6. <?php esc_html_e('National + Iranian → Allowed', 'darkshield'); ?> ✅<br>
                    7. <?php esc_html_e('National + Foreign → Blocked', 'darkshield'); ?> ❌<br>
                    8. <?php esc_html_e('Offline → Blocked', 'darkshield'); ?> ❌
                </code>
            </div>
            <p style="margin-top:10px;color:#666;font-size:13px;">
                <?php esc_html_e('Subdomain matching: adding "example.com" also allows "sub.example.com".', 'darkshield'); ?>
            </p>
        </div>

    </div>
</div>