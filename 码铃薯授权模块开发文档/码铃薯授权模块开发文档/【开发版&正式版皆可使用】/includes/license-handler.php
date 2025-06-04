<?php
// license-handler.php

// 避免重复加载本文件
if (!defined('MDL_LICENSE_HANDLER_LOADED')) {
    define('MDL_LICENSE_HANDLER_LOADED', true);
    // 始终加载完整性校验
    require_once plugin_dir_path(__FILE__) . 'file-integrity-check.php';
    // 避免重复加载函数
    if (!function_exists('get_all_mdl_plugins')) {
        // ========== 1. 获取所有作者为“码铃薯”的插件 ==========
        function get_all_mdl_plugins() {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $all_plugins = get_plugins();
            $active_plugins = get_option('active_plugins', []);
            $plugin_list = [];

            foreach ($all_plugins as $plugin_path => $plugin_data) {
                if (
                    isset($plugin_data['Author']) &&
                    $plugin_data['Author'] === '码铃薯' &&
                    in_array($plugin_path, $active_plugins)  // 只取激活的插件
                ) {
                    $plugin_slug = dirname($plugin_path); // 文件夹名做ID
                    $plugin_list[$plugin_slug] = $plugin_data['Name'];
                }
            }

            return $plugin_list;
        }
    }

    // ========== 2. 添加后台菜单和页面 ==========
    add_action('admin_menu', function() {
        add_options_page('插件授权激活', '插件授权管理', 'manage_options', 'mdl-plugin-license', function() {
            mdl_plugin_license_page();
        });
    });


    // ========== 3. 页面内容 ==========
    function mdl_plugin_license_page() {
        if (!current_user_can('manage_options')) return;

        $plugins = get_all_mdl_plugins();
        $all_licenses = get_option('my_plugin_licenses', []);
        $current_domain = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);

        ?>
        <div class="wrap">
            <h1>插件授权激活 - 码铃薯所有插件</h1>
            <div id="mdl-plugin-notice"></div>
			<div style="padding:12px 16px; margin:20px 0; border-left: 4px solid #0073aa; background:#f1f1f1;">
        	<strong>📌 提示：</strong>还没有授权码？请前往
        	<a href="https://www.tudoucode.cn/" target="_blank" style="color:#0073aa; font-weight:bold;">码铃薯授权中心</a>获取授权码。
        	获取后填入对应插件下方输入框并点击“保存授权”即可完成激活。
            </div>
            <form id="mdl-plugin-license-form" method="post" action="">
                <?php wp_nonce_field('mdl_plugin_license_action', 'mdl_plugin_license_nonce'); ?>
                <table class="form-table" style="max-width:600px;">
                    <thead>
                        <tr>
                            <th style="width:180px;">插件名称</th>
                            <th style="width:300px;">授权码</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($plugins as $plugin_id => $plugin_name): 
                        $license_data = $all_licenses[$plugin_id] ?? ['license_key' => '', 'domain' => ''];
                        $license_key = $license_data['license_key'] ?? '';
                        $bound_domain = !empty($license_data['domain']) ? $license_data['domain'] : $current_domain;

                        // 授权状态检测
                        $is_authorized = false;
                        if (function_exists('my_plugin_check_pro_license')) {
                            $is_authorized = my_plugin_check_pro_license($plugin_id);
                        }
                        $status_text = $is_authorized ? '✅ 已授权' : '❌ 未授权';
                        $status_color = $is_authorized ? 'green' : 'red';
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($plugin_name); ?></strong></td>
                            <td>
                                <input type="text" name="license_keys[<?php echo esc_attr($plugin_id); ?>]" value="<?php echo esc_attr($license_key); ?>" style="width: 100%;" required>
                                <br>
                                <small>
                                    已绑定域名：<code><?php echo esc_html($bound_domain); ?></code><br>
                                    授权状态：<span style="color: <?php echo $status_color; ?>;"><?php echo $status_text; ?></span>
                                </small>
                            </td>
                            <td>
                                <button type="submit" name="generate_license" value="<?php echo esc_attr($plugin_id); ?>" class="button">保存授权</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <script>
        (function($){
            $('#mdl-plugin-license-form').on('submit', function(e){
                e.preventDefault();

                var $btn = $(document.activeElement);
                var pluginId = $btn.val();
                var licenseKey = $('input[name="license_keys[' + pluginId + ']"]').val();
                var nonce = $('input[name="mdl_plugin_license_nonce"]').val();

                $('#mdl-plugin-notice').html('');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mdl_plugin_save_license',
                        license_key: licenseKey,
                        plugin_id: pluginId,
                        _wpnonce: nonce
                    },
                    success: function(response) {
                        if(response.success){
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                            } else {
                                location.reload();
                            }
                        } else {
                            $('#mdl-plugin-notice').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function(){
                        $('#mdl-plugin-notice').html('<div class="notice notice-error"><p>请求失败，请稍后重试。</p></div>');
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    // ========== 4. AJAX 处理 ==========
    add_action('wp_ajax_mdl_plugin_save_license', function() {
        $plugin_id = sanitize_text_field($_POST['plugin_id'] ?? '');
        mdl_plugin_save_license_callback($plugin_id);
    });

    function mdl_plugin_save_license_callback($plugin_id) {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '无权限操作']);
        }

        if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'mdl_plugin_license_action')) {
            wp_send_json_error(['message' => '安全验证失败，请刷新页面后重试']);
        }

        if (empty($plugin_id)) {
            wp_send_json_error(['message' => '插件ID不能为空']);
        }

        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        if (empty($license_key)) {
            wp_send_json_error(['message' => '授权码不能为空']);
        }

        $current_domain = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);

        $response = wp_remote_post('https://www.tudoucode.cn/wp-json/tudoucode-license/v1/verify', [
            'body' => json_encode([
                'license_key' => $license_key,
                'domain' => $current_domain,
                'plugin_id' => $plugin_id,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => '无法连接授权服务器，请稍后重试']);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code === 200 && !empty($data['success'])) {
            $all_licenses = get_option('my_plugin_licenses', []);
            $all_licenses[$plugin_id] = [
                'license_key' => $license_key,
                'domain' => $current_domain,
            ];
            update_option('my_plugin_licenses', $all_licenses);

            wp_send_json_success([
                'message' => '授权验证并绑定域名成功！插件Pro功能已激活。',
                'domain' => $current_domain,
                'redirect' => admin_url('options-general.php?page=mdl-plugin-license&mdl_license_success=1'),
            ]);
        } else {
            $msg = $data['message'] ?? '未知错误';
            wp_send_json_error(['message' => '授权验证失败：' . $msg . '。请前往 <a href="https://www.tudoucode.cn/" target="_blank">码铃薯授权页面</a> 获取有效授权码。']);
        }
    }

    // ========== 5. 授权检查函数 ==========
    function my_plugin_check_pro_license($plugin_id) {
        $all_licenses = get_option('my_plugin_licenses', []);
        if (!isset($all_licenses[$plugin_id])) return false;

        $license_key = $all_licenses[$plugin_id]['license_key'] ?? '';
        $domain = $all_licenses[$plugin_id]['domain'] ?? '';

        if (empty($domain)) {
            $domain = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
        }

        if (empty($license_key) || empty($domain)) {
            return false;
        }

        $cache_key = 'my_plugin_license_check_' . md5($license_key . '_' . $domain . '_' . $plugin_id);
        $cached_result = get_transient($cache_key);
        if ($cached_result !== false) return $cached_result;

        $url = 'https://www.tudoucode.cn/wp-json/tudoucode-license/v1/verify';
        $response = wp_remote_post($url, [
            'body' => json_encode([
                'license_key' => $license_key,
                'domain' => $domain,
                'plugin_id' => $plugin_id,
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) return false;

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $success = !empty($data['success']);

        set_transient($cache_key, $success, 10 * MINUTE_IN_SECONDS);
        return $success;
    }

    // ========== 6. 后台全局提示授权状态 ==========
    add_action('admin_init', function() {
        if (!current_user_can('manage_options')) return;
        if (isset($_GET['mdl_license_success']) && $_GET['mdl_license_success'] === '1') {
            set_transient('mdl_license_success_notice', true, 60);
        }
    });

    add_action('admin_notices', function() {
        if (!current_user_can('manage_options')) return;

        $plugins = get_all_mdl_plugins();
        $all_licenses = get_option('my_plugin_licenses', []);
        $unauthorized_plugins = [];

        foreach ($plugins as $plugin_id => $plugin_name) {
            if (!my_plugin_check_pro_license($plugin_id)) {
                $unauthorized_plugins[] = $plugin_name;
            }
        }

        if (!empty($unauthorized_plugins)) {
            $plugin_list = implode('、', array_map('esc_html', $unauthorized_plugins));
            $settings_url = admin_url('options-general.php?page=mdl-plugin-license');
            echo '<div class="notice notice-error"><p>';
            echo '⚠️ <strong>授权提示：</strong>以下插件尚未授权：' . $plugin_list . '。';
            echo '请尽快前往 <a href="' . esc_url($settings_url) . '">插件授权管理</a> 页面完成授权。';
            echo '</p></div>';
        }

        if (get_transient('mdl_license_success_notice')) {
            delete_transient('mdl_license_success_notice');
            echo '<div class="notice notice-success is-dismissible"><p>✅ 插件授权成功！Pro功能已激活。</p></div>';
         }
      });
	  
	  // ========== 7. 前端功能锁辅助函数（UI可灰化+跳转） ==========
	  function mdl_is_pro($plugin_id) {
	      return my_plugin_check_pro_license($plugin_id);
	  }

	  function mdl_pro_attr($plugin_id, $return_type = 'attr') {
	      if (mdl_is_pro($plugin_id)) {
	          return ''; // 已授权，不加限制
	      }

	      $auth_page_url = admin_url('options-general.php?page=mdl-plugin-license'); // 授权管理页面URL

	      switch ($return_type) {
	          case 'disabled':
	              // 灰化并禁用input
	              return 'disabled style="opacity:0.5; cursor:not-allowed;"';
	          case 'class':
	              return 'mdl-disabled';
	          case 'text':
	              // 显示点击激活的醒目标识，带跳转链接
	              return ' <a href="' . esc_url($auth_page_url) . '" style="opacity:0.5; cursor:pointer;" title="点击激活Pro功能" target="_blank">（🚀 Pro功能，点击激活）</a>';
	          case 'attr': 
	              // 让元素灰化，点击跳转授权页（备用）
	              return 'onclick="window.location.href=\'' . esc_url($auth_page_url) . '\'" style="opacity:0.5; cursor:pointer;" title="请先授权后使用"';
	          default:
	              return '';
	      }
	  }

}
