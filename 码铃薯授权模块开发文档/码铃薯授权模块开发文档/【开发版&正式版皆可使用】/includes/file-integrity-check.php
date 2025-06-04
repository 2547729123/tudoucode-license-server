<?php
// includes/file-integrity-check.php

if (!defined('ABSPATH')) exit;

// 避免重复加载本文件
if (!defined('MDL_FILE_INTEGRITY_CHECK_LOADED')) {
    define('MDL_FILE_INTEGRITY_CHECK_LOADED', true);
	
    // 避免重复加载函数
    if (!function_exists('mdl_verify_plugin_integrity')) {

    add_action('admin_init', 'mdl_verify_plugin_integrity');

    function mdl_verify_plugin_integrity() {
        $hash_file = plugin_dir_path(__FILE__) . '/.hash';
        $base_path = plugin_dir_path(__DIR__) . '/';

        if (!file_exists($hash_file)) {
            mdl_plugin_kill('插件完整性校验失败：缺少 <code>.hash</code> 文件，请重新安装插件。');
            return;
        }

        $hashes = file($hash_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$hashes || !is_array($hashes)) {
            mdl_plugin_kill('插件完整性校验失败：哈希文件格式错误，请重新下载官方版本。');
            return;
        }

        foreach ($hashes as $line) {
            if (strpos($line, ':') === false) continue;

            list($filename, $expected_hash) = explode(':', $line, 2);
            $full_path = $base_path . $filename;

            if (!file_exists($full_path)) {
                mdl_plugin_kill('插件完整性校验失败：文件缺失 <code>' . esc_html($filename) . '</code>，请重新安装插件。');
                return;
            }

            $actual_hash = hash_file('sha256', $full_path);
            if (trim($actual_hash) !== trim($expected_hash)) {
                mdl_plugin_kill('插件完整性校验失败：文件被修改 <code>' . esc_html($filename) . '</code>，请使用原始版本。');
                return;
            }
        }
    }

    /**
     * 自动停用插件 + 后台显示提示（不跳白页）
     */
    function mdl_plugin_kill($message) {
        $plugin_dir = plugin_dir_path(__DIR__);
        $plugin_files = scandir($plugin_dir);

        $plugin_main_file = null;
        foreach ($plugin_files as $file) {
            if (substr($file, -4) === '.php') {
                $full_path = $plugin_dir . $file;
                $contents = file_get_contents($full_path);
                if (strpos($contents, 'Plugin Name:') !== false) {
                    $plugin_main_file = plugin_basename($full_path);
                    break;
                }
            }
        }

        if ($plugin_main_file && is_admin() && current_user_can('activate_plugins')) {
            if (is_plugin_active($plugin_main_file)) {
                deactivate_plugins($plugin_main_file);
            }
            set_transient('mdl_plugin_kill_notice', $message, 30);
        }
    }

    /**
     * 后台顶部显示错误提示
     */
    add_action('admin_notices', function () {
        if ($notice = get_transient('mdl_plugin_kill_notice')) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<h2>🚫 插件已被自动停用</h2>';
            echo '<p>' . $notice . '</p>';
            echo '<p>👉 请重新安装插件或下载原始版本解决问题。</p>';
            echo '<p><a href="https://www.tudoucode.cn" target="_blank">前往码铃薯官网获取帮助</a></p>';
            echo '</div>';

            delete_transient('mdl_plugin_kill_notice');
        }
    });

    }
}
