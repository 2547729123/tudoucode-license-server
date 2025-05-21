<?php
/**
 * Plugin Name: 码铃薯授权中心 - 云授权服务器
 * Description: 提供云端授权验证、授权码管理、接口验证、日志统计等完整功能。
 * Version: 2.0
 * Author: 码铃薯
 * Author URI: https://www.malingshu.com
 * License: GPL2
 */


if (!defined('ABSPATH')) {
    exit;
}

// 自动加载 includes 文件夹内类
require_once plugin_dir_path(__FILE__) . 'includes/class-license-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-license-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-license-logger.php';

// 加载后台管理页面
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/admin-license-page.php';
}
//加载css
function enqueue_admin_assets() {
    wp_enqueue_style('tudoucode-admin-style', plugins_url('assets/style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'enqueue_admin_assets');
