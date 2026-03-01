<?php
/**
 * Plugin Name:       Rabbit Builds Anti-Spam Comment Shield
 * Plugin URI:        https://wordpress.org/plugins/rabbitbuilds-anti-spam-comment-shield/
 * Description:       Advanced, lightweight, and GDPR-compliant anti-spam protection for WordPress comments. Zero configuration needed — just activate and forget spam forever.
 * Author:            Rabbit Builds
 * Author URI:        https://rabbitbuilds.com/
 * Version:           2.0.4
 * Text Domain:       rabbitbuilds-anti-spam-comment-shield
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
defined('ABSPATH') || die();

/**
 * ─── Constants ───────────────────────────────────────────────────────────────
 */
define('RBASCS_VERSION', '2.0.4');
define('RBASCS_URL', plugin_dir_url(__FILE__));

// Generate a unique key from NONCE_SALT or DOCUMENT_ROOT
$rbascs_key_source = defined('NONCE_SALT') && NONCE_SALT
    ? NONCE_SALT
    : (isset($_SERVER['DOCUMENT_ROOT']) ? sanitize_text_field(wp_unslash($_SERVER['DOCUMENT_ROOT'])) : plugin_dir_path(__FILE__));
define('RBASCS_UNIQUE_KEY', md5($rbascs_key_source));

/**
 * ─── Default Settings ────────────────────────────────────────────────────────
 */
function rbascs_get_defaults()
{
    return array(
        'enable_hash_check' => 1,
        'enable_honeypot' => 1,
        'enable_time_check' => 1,
        'min_submit_time' => 3,
        'blocked_message' => __('Your comment was blocked by our anti-spam protection. If you believe this is an error, please try again.', 'rabbitbuilds-anti-spam-comment-shield'),
        'enable_rest_protect' => 1,
    );
}

function rbascs_get_options()
{
    $defaults = rbascs_get_defaults();
    $options = get_option('rbascs_settings', array());
    return wp_parse_args($options, $defaults);
}

function rbascs_get_default_stats()
{
    return array(
        'blocked_total' => 0,
        'blocked_today' => 0,
        'blocked_date' => current_time('Y-m-d'),
        'last_blocked_at' => '',
    );
}

function rbascs_get_stats($refresh_daily = false)
{
    $stats = wp_parse_args(
        get_option('rbascs_stats', array()),
        rbascs_get_default_stats()
    );

    if ($refresh_daily && $stats['blocked_date'] !== current_time('Y-m-d')) {
        $stats['blocked_today'] = 0;
        $stats['blocked_date'] = current_time('Y-m-d');
        update_option('rbascs_stats', $stats);
    }

    return $stats;
}

/**
 * ─── Activation Hook ─────────────────────────────────────────────────────────
 */
register_activation_hook(__FILE__, 'rbascs_activation_hook');

function rbascs_activation_hook()
{
    set_transient('rbascs_activation_notice', true, 5);

    // Initialize stats if not existing
    if (false === get_option('rbascs_stats')) {
        update_option('rbascs_stats', rbascs_get_default_stats());
    }

    // Initialize default settings
    if (false === get_option('rbascs_settings')) {
        update_option('rbascs_settings', rbascs_get_defaults());
    }
}

/**
 * ─── Deactivation Hook ──────────────────────────────────────────────────────
 */
register_deactivation_hook(__FILE__, 'rbascs_deactivation_hook');

function rbascs_deactivation_hook()
{
    // Clean up transients only; preserve stats and settings
    delete_transient('rbascs_activation_notice');
}

/**
 * ─── Admin Notice on Activation ─────────────────────────────────────────────
 */
add_action('admin_notices', 'rbascs_activation_notice');

function rbascs_activation_notice()
{
    if (get_transient('rbascs_activation_notice')) {
        ?>
        <div class="notice notice-success is-dismissible" style="border-left-color: #DC2626;">
            <p>
                <strong>🛡️ <?php esc_html_e('Rabbit Builds Anti-Spam Comment Shield', 'rabbitbuilds-anti-spam-comment-shield'); ?></strong>
                <?php esc_html_e('is now active!', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                <?php echo wp_kses_post(__('Please <strong>clear your page cache</strong> for the protection to take effect.', 'rabbitbuilds-anti-spam-comment-shield')); ?>
            </p>
        </div>
        <?php
        delete_transient('rbascs_activation_notice');
    }
}

/**
 * ─── Plugin Action Links (Settings + Support) ──────────────────────────────
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'rbascs_action_links');

function rbascs_action_links($links)
{
    $custom_links = array(
        '<a href="' . esc_url(admin_url('options-general.php?page=rabbitbuilds-anti-spam-comment-shield')) . '">' . esc_html__('Settings', 'rabbitbuilds-anti-spam-comment-shield') . '</a>',
        '<a rel="noopener" title="Technical Support" href="' . esc_url('https://rabbitbuilds.com/contact/') . '" target="_blank">' . esc_html__('Get Support', 'rabbitbuilds-anti-spam-comment-shield') . '</a>',
    );
    return array_merge($custom_links, $links);
}

/**
 * ─── Admin Menu & Settings Page ─────────────────────────────────────────────
 */
add_action('admin_menu', 'rbascs_admin_menu');

function rbascs_admin_menu()
{
    add_options_page(
        __('Rabbit Builds Anti-Spam Comment Shield', 'rabbitbuilds-anti-spam-comment-shield'),
        __('Rabbit Builds Anti-Spam', 'rabbitbuilds-anti-spam-comment-shield'),
        'manage_options',
        'rabbitbuilds-anti-spam-comment-shield',
        'rbascs_settings_page'
    );
}

/**
 * ─── Register Settings ──────────────────────────────────────────────────────
 */
add_action('admin_init', 'rbascs_register_settings');

function rbascs_register_settings()
{
    register_setting('rbascs_settings_group', 'rbascs_settings', 'rbascs_sanitize_settings');
}

function rbascs_sanitize_settings($input)
{
    $sanitized = array();
    $sanitized['enable_hash_check'] = isset($input['enable_hash_check']) ? 1 : 0;
    $sanitized['enable_honeypot'] = isset($input['enable_honeypot']) ? 1 : 0;
    $sanitized['enable_time_check'] = isset($input['enable_time_check']) ? 1 : 0;
    $sanitized['min_submit_time'] = isset($input['min_submit_time']) ? absint($input['min_submit_time']) : 3;
    $sanitized['blocked_message'] = isset($input['blocked_message']) ? sanitize_textarea_field($input['blocked_message']) : '';
    $sanitized['enable_rest_protect'] = isset($input['enable_rest_protect']) ? 1 : 0;

    if ($sanitized['min_submit_time'] < 1) {
        $sanitized['min_submit_time'] = 1;
    }
    if ($sanitized['min_submit_time'] > 30) {
        $sanitized['min_submit_time'] = 30;
    }

    return $sanitized;
}

/**
 * ─── Enqueue Admin Assets ───────────────────────────────────────────────────
 */
add_action('admin_enqueue_scripts', 'rbascs_admin_assets');

function rbascs_admin_assets($hook)
{
    if ('settings_page_rabbitbuilds-anti-spam-comment-shield' !== $hook) {
        return;
    }

    $admin_css_file = plugin_dir_path(__FILE__) . 'admin/css/admin-style.css';
    $admin_js_file = plugin_dir_path(__FILE__) . 'admin/js/admin-script.js';
    $admin_css_ver = file_exists($admin_css_file) ? (string) filemtime($admin_css_file) : RBASCS_VERSION;
    $admin_js_ver = file_exists($admin_js_file) ? (string) filemtime($admin_js_file) : RBASCS_VERSION;

    wp_enqueue_style(
        'rbascs-admin-style',
        RBASCS_URL . 'admin/css/admin-style.css',
        array(),
        $admin_css_ver
    );

    wp_enqueue_script(
        'rbascs-admin-script',
        RBASCS_URL . 'admin/js/admin-script.js',
        array(),
        $admin_js_ver,
        true
    );
}

/**
 * ─── Settings Page Render ───────────────────────────────────────────────────
 */
function rbascs_settings_page()
{
    $options = rbascs_get_options();
    $stats = rbascs_get_stats(true);

    ?>
    <div class="wrap wpasc-wrap">

        <!-- Header -->
        <div class="wpasc-header">
            <div class="wpasc-header-content">
                <div class="wpasc-header-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <path d="M9 12l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h1 class="wpasc-title"><?php esc_html_e('Rabbit Builds Anti-Spam Comment Shield', 'rabbitbuilds-anti-spam-comment-shield'); ?></h1>
                    <p class="wpasc-subtitle">
                        <?php esc_html_e('Advanced spam protection for WordPress comments', 'rabbitbuilds-anti-spam-comment-shield'); ?></p>
                </div>
                <span class="wpasc-version">v<?php echo esc_html(RBASCS_VERSION); ?></span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="wpasc-stats-grid">
            <div class="wpasc-stat-card wpasc-stat-blocked">
                <div class="wpasc-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                    </svg>
                </div>
                <div class="wpasc-stat-info">
                    <span class="wpasc-stat-number" data-count="<?php echo absint($stats['blocked_total']); ?>">0</span>
                    <span class="wpasc-stat-label"><?php esc_html_e('Total Blocked', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                </div>
            </div>
            <div class="wpasc-stat-card wpasc-stat-today">
                <div class="wpasc-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                </div>
                <div class="wpasc-stat-info">
                    <span class="wpasc-stat-number" data-count="<?php echo absint($stats['blocked_today']); ?>">0</span>
                    <span class="wpasc-stat-label"><?php esc_html_e('Blocked Today', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                </div>
            </div>
            <div class="wpasc-stat-card wpasc-stat-status">
                <div class="wpasc-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                </div>
                <div class="wpasc-stat-info">
                    <span class="wpasc-stat-status-text"><?php esc_html_e('Active', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                    <span class="wpasc-stat-label"><?php esc_html_e('Protection', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                </div>
            </div>
            <div class="wpasc-stat-card wpasc-stat-last">
                <div class="wpasc-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </div>
                <div class="wpasc-stat-info">
                    <span class="wpasc-stat-last-time">
                        <?php
                        $last_blocked_ts = !empty($stats['last_blocked_at']) ? strtotime($stats['last_blocked_at']) : false;
                        if ($last_blocked_ts) {
                            echo esc_html(human_time_diff($last_blocked_ts, current_time('timestamp')) . ' ' . __('ago', 'rabbitbuilds-anti-spam-comment-shield'));
                        } else {
                            esc_html_e('No spam yet', 'rabbitbuilds-anti-spam-comment-shield');
                        }
                        ?>
                    </span>
                    <span class="wpasc-stat-label"><?php esc_html_e('Last Blocked', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                </div>
            </div>
        </div>

        <!-- Settings Form -->
        <form method="post" action="options.php" class="wpasc-settings-form">
            <?php settings_fields('rbascs_settings_group'); ?>

            <!-- Protection Modules -->
            <div class="wpasc-card">
                <div class="wpasc-card-header">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <?php esc_html_e('Protection Modules', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                    </h2>
                    <p class="wpasc-card-desc">
                        <?php esc_html_e('Enable or disable individual spam protection layers.', 'rabbitbuilds-anti-spam-comment-shield'); ?></p>
                </div>
                <div class="wpasc-card-body">

                    <!-- Hash-Based Verification -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title" for="enable_hash_check">
                                <?php esc_html_e('Hash-Based Verification', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                                <span
                                    class="wpasc-badge wpasc-badge-recommended"><?php esc_html_e('Core', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                            </label>
                            <p class="wpasc-setting-desc">
                                <?php esc_html_e('Blocks bots by requiring a unique hash token in the comment form action URL — only injected via JavaScript when a real user interacts with the page.', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                            </p>
                        </div>
                        <label class="wpasc-toggle">
                            <input type="checkbox" name="rbascs_settings[enable_hash_check]"
                                id="enable_hash_check" value="1" <?php checked($options['enable_hash_check'], 1); ?> />
                            <span class="wpasc-toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Honeypot Field -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title" for="enable_honeypot">
                                <?php esc_html_e('Honeypot Trap', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                                <span
                                    class="wpasc-badge wpasc-badge-new"><?php esc_html_e('New', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                            </label>
                            <p class="wpasc-setting-desc">
                                <?php esc_html_e('Adds a hidden field to the comment form that only bots will fill out. Human visitors never see it.', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                            </p>
                        </div>
                        <label class="wpasc-toggle">
                            <input type="checkbox" name="rbascs_settings[enable_honeypot]"
                                id="enable_honeypot" value="1" <?php checked($options['enable_honeypot'], 1); ?> />
                            <span class="wpasc-toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Time-Based Check -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title" for="enable_time_check">
                                <?php esc_html_e('Time-Based Check', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                                <span
                                    class="wpasc-badge wpasc-badge-new"><?php esc_html_e('New', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                            </label>
                            <p class="wpasc-setting-desc">
                                <?php
                                echo wp_kses_post(
                                    sprintf(
                                        /* translators: %s is the minimum number of seconds required before a comment can be submitted. */
                                        __('Rejects comments submitted within %s seconds of page load. Real users take at least a few seconds to type.', 'rabbitbuilds-anti-spam-comment-shield'),
                                        '<strong>' . absint($options['min_submit_time']) . '</strong>'
                                    )
                                );
                                ?>
                            </p>
                        </div>
                        <label class="wpasc-toggle">
                            <input type="checkbox" name="rbascs_settings[enable_time_check]"
                                id="enable_time_check" value="1" <?php checked($options['enable_time_check'], 1); ?> />
                            <span class="wpasc-toggle-slider"></span>
                        </label>
                    </div>

                    <!-- REST API Protection -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title" for="enable_rest_protect">
                                <?php esc_html_e('REST API Protection', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                                <span
                                    class="wpasc-badge wpasc-badge-new"><?php esc_html_e('New', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                            </label>
                            <p class="wpasc-setting-desc">
                                <?php esc_html_e('Blocks unauthenticated comment creation through the WordPress REST API endpoint.', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                            </p>
                        </div>
                        <label class="wpasc-toggle">
                            <input type="checkbox" name="rbascs_settings[enable_rest_protect]"
                                id="enable_rest_protect" value="1" <?php checked($options['enable_rest_protect'], 1); ?> />
                            <span class="wpasc-toggle-slider"></span>
                        </label>
                    </div>

                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="wpasc-card">
                <div class="wpasc-card-header">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3" />
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                        <?php esc_html_e('Advanced Settings', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                    </h2>
                    <p class="wpasc-card-desc"><?php esc_html_e('Fine-tune the anti-spam behavior.', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                    </p>
                </div>
                <div class="wpasc-card-body">

                    <!-- Minimum Submit Time -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title"
                                for="min_submit_time"><?php esc_html_e('Minimum Submit Time (seconds)', 'rabbitbuilds-anti-spam-comment-shield'); ?></label>
                            <p class="wpasc-setting-desc">
                                <?php esc_html_e('Comments submitted faster than this threshold will be blocked. Range: 1–30 seconds.', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                            </p>
                        </div>
                        <div class="wpasc-input-wrapper">
                            <input type="number" name="rbascs_settings[min_submit_time]" id="min_submit_time"
                                value="<?php echo absint($options['min_submit_time']); ?>" min="1" max="30"
                                class="wpasc-input-number" />
                            <span class="wpasc-input-suffix"><?php esc_html_e('sec', 'rabbitbuilds-anti-spam-comment-shield'); ?></span>
                        </div>
                    </div>

                    <!-- Custom Blocked Message -->
                    <div class="wpasc-setting-row wpasc-setting-row-full">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title"
                                for="blocked_message"><?php esc_html_e('Custom Blocked Message', 'rabbitbuilds-anti-spam-comment-shield'); ?></label>
                            <p class="wpasc-setting-desc">
                                <?php esc_html_e('The message displayed when a comment is blocked as spam.', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                            </p>
                        </div>
                        <textarea name="rbascs_settings[blocked_message]" id="blocked_message"
                            class="wpasc-textarea"
                            rows="3"><?php echo esc_textarea($options['blocked_message']); ?></textarea>
                    </div>

                </div>
            </div>

            <!-- Save Button -->
            <div class="wpasc-save-bar">
                <?php submit_button(esc_html__('Save Settings', 'rabbitbuilds-anti-spam-comment-shield'), 'primary wpasc-save-btn', 'submit', false); ?>
                <button type="button" class="button wpasc-reset-btn"
                    onclick="if(confirm('<?php echo esc_js(__('Reset all settings to default?', 'rabbitbuilds-anti-spam-comment-shield')); ?>')) { document.getElementById('wpasc-reset-form').submit(); }">
                    <?php esc_html_e('Reset to Defaults', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                </button>
            </div>

        </form>

        <!-- Reset Form -->
        <form id="wpasc-reset-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="rbascs_reset" />
            <?php wp_nonce_field('rbascs_reset_nonce', '_wpnonce_reset'); ?>
        </form>

        <!-- How It Works Section -->
        <div class="wpasc-card wpasc-card-info">
            <div class="wpasc-card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    <?php esc_html_e('How It Works', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                </h2>
                <p class="wpasc-card-desc"><?php esc_html_e('Your comments are protected through a 4-step defense pipeline.', 'rabbitbuilds-anti-spam-comment-shield'); ?></p>
            </div>
            <div class="wpasc-card-body wpasc-how-it-works">

                <div class="wpasc-how-tree">
                    <div class="wpasc-tree-level wpasc-tree-level-root">
                        <div class="wpasc-step wpasc-step-root">
                            <div class="wpasc-step-indicator">
                                <div class="wpasc-step-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                        <line x1="1" y1="1" x2="23" y2="23" />
                                    </svg>
                                </div>
                            </div>
                            <div class="wpasc-step-content">
                                <h3>
                                    <span class="wpasc-step-number">1</span>
                                    <?php esc_html_e('Action URL Hidden', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                                </h3>
                                <p><?php esc_html_e('The comment form\'s action URL is stripped from the HTML source — bots scanning raw HTML find nothing to target.', 'rabbitbuilds-anti-spam-comment-shield'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="wpasc-tree-branches">
                        <div class="wpasc-tree-branch wpasc-tree-left">
                            <div class="wpasc-step wpasc-step-left">
                                <div class="wpasc-step-indicator">
                                    <div class="wpasc-step-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                                            <line x1="12" y1="2" x2="12" y2="4" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="wpasc-step-content">
                                    <h3>
                                        <span class="wpasc-step-number">2</span>
                                        <?php esc_html_e('Human Interaction Detected', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                                    </h3>
                                    <p><?php esc_html_e('Real user activity — scrolling, mouse movement, or focus — triggers JavaScript to restore the form action with a unique hash token.', 'rabbitbuilds-anti-spam-comment-shield'); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="wpasc-tree-branch wpasc-tree-right">
                            <div class="wpasc-step wpasc-step-right">
                                <div class="wpasc-step-indicator">
                                    <div class="wpasc-step-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                            <path d="M9 12l2 2 4-4" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="wpasc-step-content">
                                    <h3>
                                        <span class="wpasc-step-number">3</span>
                                        <?php esc_html_e('Multi-Layer Validation', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                                    </h3>
                                    <p><?php esc_html_e('Hash token, honeypot field, and submission timing are all verified server-side before any comment passes through.', 'rabbitbuilds-anti-spam-comment-shield'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wpasc-tree-level wpasc-tree-level-final">
                        <div class="wpasc-step wpasc-step-final">
                            <div class="wpasc-step-indicator">
                                <div class="wpasc-step-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                </div>
                            </div>
                            <div class="wpasc-step-content">
                                <h3>
                                    <span class="wpasc-step-number">4</span>
                                    <?php esc_html_e('Spam Eliminated', 'rabbitbuilds-anti-spam-comment-shield'); ?>
                                </h3>
                                <p><?php esc_html_e('Failed submissions get an instant 403 response. Zero spam reaches your database — your comments stay clean.', 'rabbitbuilds-anti-spam-comment-shield'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <div class="wpasc-footer">
            <p>
                <?php
                echo wp_kses_post(
                    sprintf(
                        __('Made with ❤️ by %s • GDPR Compliant • No External Requests • ~200 Bytes Inline JS', 'rabbitbuilds-anti-spam-comment-shield'),
                        '<a href="https://rabbitbuilds.com/" target="_blank" rel="noopener">Rabbit Builds</a>'
                    )
                );
                ?>
            </p>
        </div>

    </div>
    <?php
}

/**
 * ─── Handle Settings Reset ──────────────────────────────────────────────────
 */
add_action('admin_post_rbascs_reset', 'rbascs_handle_reset');

function rbascs_handle_reset()
{
    if (!current_user_can('manage_options')) {
        wp_die(
            esc_html__('Unauthorized', 'rabbitbuilds-anti-spam-comment-shield'),
            esc_html__('Error', 'rabbitbuilds-anti-spam-comment-shield'),
            array('response' => 403)
        );
    }

    check_admin_referer('rbascs_reset_nonce', '_wpnonce_reset');

    update_option('rbascs_settings', rbascs_get_defaults());

    wp_safe_redirect(admin_url('options-general.php?page=rabbitbuilds-anti-spam-comment-shield&reset=1'));
    exit;
}

/**
 * ─── Reset Notice ───────────────────────────────────────────────────────────
 */
add_action('admin_notices', 'rbascs_reset_notice');

function rbascs_reset_notice()
{
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $reset = isset($_GET['reset']) ? sanitize_text_field(wp_unslash($_GET['reset'])) : '';

    if ('rabbitbuilds-anti-spam-comment-shield' === $page && '1' === $reset) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings have been reset to defaults.', 'rabbitbuilds-anti-spam-comment-shield'); ?></p>
        </div>
        <?php
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * FRONTEND ANTI-SPAM MECHANISMS
 * ═══════════════════════════════════════════════════════════════════════════
 */

$rbascs_options = rbascs_get_options();

/**
 * ─── 1. Remove Comment Action URL from HTML ─────────────────────────────────
 */
if ($rbascs_options['enable_hash_check']) {
    add_filter('comment_form_defaults', 'rbascs_remove_action_url');
}

function rbascs_remove_action_url($defaults)
{
    $defaults['action'] = '';
    return $defaults;
}

/**
 * ─── 2. Inject JavaScript to Restore Action URL on User Interaction ─────────
 */
if ($rbascs_options['enable_hash_check']) {
    add_action('wp_enqueue_scripts', 'rbascs_enqueue_frontend_js');
}

function rbascs_enqueue_frontend_js()
{
    if (!is_singular() || !comments_open()) {
        return;
    }

    $options = rbascs_get_options();
    $action_url = wp_make_link_relative(site_url('/wp-comments-post.php')) . '?' . RBASCS_UNIQUE_KEY;
    $min_time = absint($options['min_submit_time']);

    // Register a virtual script handle (no external file needed)
    wp_register_script('rbascs-frontend', false, array(), RBASCS_VERSION, true);

    $js = "(function(){\n";
    $js .= "var f=document.querySelector(\"#commentform,#ast-commentform,#fl-comment-form,#ht-commentform,#wpd-comm-form,.comment-form\");\n";
    $js .= "if(!f)return;\n";
    $js .= "var d=0;\n";

    // Inject timestamp hidden field for time-based check
    if ($options['enable_time_check']) {
        $js .= "var t=" . $min_time . ";\n";
        $js .= "var ts=document.createElement('input');ts.type='hidden';ts.name='_wpasc_ts';ts.value=Date.now();f.appendChild(ts);\n";
    }

    $js .= "function u(){if(d)return;d=1;f.action=\"" . esc_js($action_url) . "\";}\n";
    $js .= "document.addEventListener('scroll',u,{once:true,passive:true});\n";
    $js .= "document.addEventListener('mousemove',u,{once:true,passive:true});\n";
    $js .= "document.addEventListener('touchstart',u,{once:true,passive:true});\n";
    $js .= "f.addEventListener('focusin',u,{once:true});\n";

    // Time-based: prevent form submit if too fast
    if ($options['enable_time_check']) {
        $js .= "f.addEventListener('submit',function(e){\n";
        $js .= "if(ts.value&&(Date.now()-parseInt(ts.value,10))<t*1000){e.preventDefault();alert('" . esc_js(__('Please wait a moment before submitting your comment.', 'rabbitbuilds-anti-spam-comment-shield')) . "');}\n";
        $js .= "});\n";
    }

    $js .= "})();";

    wp_add_inline_script('rbascs-frontend', $js);
    wp_enqueue_script('rbascs-frontend');
}

/**
 * ─── 3. Honeypot Field ──────────────────────────────────────────────────────
 */
if ($rbascs_options['enable_honeypot']) {
    add_action('comment_form_after_fields', 'rbascs_honeypot_field');
    add_action('comment_form_logged_in_after', 'rbascs_honeypot_field');
}

function rbascs_honeypot_field()
{
    echo '<p style="position:absolute;left:-9999px;height:0;width:0;overflow:hidden;" aria-hidden="true">';
    echo '<label for="wpasc_website_url">' . esc_html__('Website URL', 'rabbitbuilds-anti-spam-comment-shield') . '</label>';
    echo '<input type="hidden" name="_wpasc_nonce" value="' . esc_attr(wp_create_nonce('wpasc_comment_nonce')) . '" />';
    echo '<input type="text" name="wpasc_website_url" id="wpasc_website_url" value="" tabindex="-1" autocomplete="off" />';
    echo '</p>';
}

/**
 * ─── 4. Validate Honeypot on Comment Pre-Process ────────────────────────────
 */
if ($rbascs_options['enable_honeypot']) {
    add_filter('preprocess_comment', 'rbascs_check_honeypot', 1);
}

function rbascs_check_honeypot($commentdata)
{
    $nonce = isset($_POST['_wpasc_nonce']) ? sanitize_text_field(wp_unslash($_POST['_wpasc_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'wpasc_comment_nonce')) {
        return $commentdata;
    }

    $honeypot_value = isset($_POST['wpasc_website_url'])
        ? sanitize_text_field(trim((string) wp_unslash($_POST['wpasc_website_url'])))
        : '';

    if ('' !== $honeypot_value) {
        rbascs_record_block();
        rbascs_block_response();
    }
    return $commentdata;
}

/**
 * ─── 5. Validate Time-Based Check ───────────────────────────────────────────
 */
if ($rbascs_options['enable_time_check']) {
    add_filter('preprocess_comment', 'rbascs_check_time', 2);
}

function rbascs_check_time($commentdata)
{
    $options = rbascs_get_options();
    $nonce = isset($_POST['_wpasc_nonce']) ? sanitize_text_field(wp_unslash($_POST['_wpasc_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'wpasc_comment_nonce')) {
        return $commentdata;
    }

    if (!isset($_POST['_wpasc_ts'])) {
        return $commentdata;
    }

    $submitted_ts = absint(wp_unslash($_POST['_wpasc_ts']));

    if ($submitted_ts <= 0) {
        rbascs_record_block();
        rbascs_block_response();
    }

    $current_ts = (int) round(microtime(true) * 1000);
    $elapsed_secs = ($current_ts - $submitted_ts) / 1000;

    if ($elapsed_secs < $options['min_submit_time']) {
        rbascs_record_block();
        rbascs_block_response();
    }

    return $commentdata;
}

/**
 * ─── 6. REST API Comment Protection ─────────────────────────────────────────
 */
if ($rbascs_options['enable_rest_protect']) {
    add_filter('rest_pre_insert_comment', 'rbascs_rest_protect', 10, 2);
}

function rbascs_rest_protect($prepared_comment, $_request)
{
    if (!is_user_logged_in()) {
        rbascs_record_block();
        return new WP_Error(
            'rest_comment_spam_blocked',
            __('Comment blocked by Rabbit Builds Anti-Spam Comment Shield.', 'rabbitbuilds-anti-spam-comment-shield'),
            array('status' => 403)
        );
    }
    return $prepared_comment;
}

/**
 * ─── 7. Hash-Based Verification on POST ─────────────────────────────────────
 */
if ($rbascs_options['enable_hash_check']) {
    add_action('pre_comment_on_post', 'rbascs_validate_hash_request');
}

function rbascs_validate_hash_request()
{
    $query_string = isset($_SERVER['QUERY_STRING']) ? sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING'])) : '';
    $has_key = hash_equals(RBASCS_UNIQUE_KEY, $query_string);

    $referrer = wp_get_raw_referer();
    $home_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $ref_host = $referrer ? wp_parse_url($referrer, PHP_URL_HOST) : '';

    $has_valid_referrer = !empty($home_host)
        && !empty($ref_host)
        && strtolower((string) $home_host) === strtolower((string) $ref_host);

    if (!$has_key || !$has_valid_referrer) {
        rbascs_record_block();
        rbascs_block_response();
    }
}

/**
 * ─── Block Response ─────────────────────────────────────────────────────────
 */
function rbascs_block_response()
{
    $options = rbascs_get_options();
    $message = !empty($options['blocked_message'])
        ? $options['blocked_message']
        : __('Your comment was blocked by our anti-spam protection.', 'rabbitbuilds-anti-spam-comment-shield');

    $html_message = '<h1>' . esc_html__('Spam Blocked', 'rabbitbuilds-anti-spam-comment-shield') . '</h1>'
        . '<p>' . esc_html($message) . '</p>'
        . '<p>' . esc_html__('If you are a site admin, please clear your page cache after activating the plugin.', 'rabbitbuilds-anti-spam-comment-shield') . '</p>';

    wp_die(
        wp_kses_post($html_message),
        esc_html__('Blocked', 'rabbitbuilds-anti-spam-comment-shield'),
        array('response' => 403, 'back_link' => true)
    );
}

/**
 * ─── Record Blocked Spam ────────────────────────────────────────────────────
 */
function rbascs_record_block()
{
    $stats = rbascs_get_stats();

    // Reset daily counter if new day
    if ($stats['blocked_date'] !== current_time('Y-m-d')) {
        $stats['blocked_today'] = 0;
        $stats['blocked_date'] = current_time('Y-m-d');
    }

    $stats['blocked_total']++;
    $stats['blocked_today']++;
    $stats['last_blocked_at'] = current_time('mysql');

    update_option('rbascs_stats', $stats);
}

/**
 * ─── Admin Bar Spam Counter ─────────────────────────────────────────────────
 */
add_action('admin_bar_menu', 'rbascs_admin_bar', 999);

function rbascs_admin_bar($wp_admin_bar)
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $stats = rbascs_get_stats(true);

    $wp_admin_bar->add_node(array(
        'id' => 'rabbitbuilds-anti-spam-comment-shield',
        'title' => '🛡️ ' . number_format_i18n(absint($stats['blocked_total'])) . ' ' . esc_html__('spam blocked', 'rabbitbuilds-anti-spam-comment-shield'),
        'href' => admin_url('options-general.php?page=rabbitbuilds-anti-spam-comment-shield'),
        'meta' => array(
            'title' => esc_html__('Rabbit Builds Anti-Spam Comment Shield — Total spam blocked', 'rabbitbuilds-anti-spam-comment-shield'),
        ),
    ));
}
