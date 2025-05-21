<?php
class Tudoucode_License_API {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('tudoucode-license/v1', '/verify', [
            'methods' => 'POST',
            'callback' => [$this, 'verify_license'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function verify_license($request) {
        $params = $request->get_json_params();
        $key = sanitize_text_field($params['license_key'] ?? '');
        $domain = sanitize_text_field($params['domain'] ?? '');
        $plugin_id = sanitize_text_field($params['plugin_id'] ?? '');

        // 取客户端IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip';
        $transient_key = 'tudoucode_api_limit_' . md5($ip);
        $count = (int) get_transient($transient_key);

        if ($count >= 5) {
            return new WP_REST_Response([
                'success' => false,
                'message' => '请求过于频繁，请稍后再试'
            ], 429);
        }

        set_transient($transient_key, $count + 1, 60); // 60秒过期

        $manager = new Tudoucode_License_Manager();
        $logger = new Tudoucode_License_Logger();
        $license = $manager->get_license($key);

        if (!$license) {
            $logger->log_request($key, $domain, false, '授权码不存在', $plugin_id);
            return new WP_REST_Response(['success' => false, 'message' => '授权码不存在'], 404);
        }

        $now = current_time('Y-m-d');

        if (empty($license['domain'])) {
            $license['domain'] = $domain;
            $license['plugin_id'] = $plugin_id;
            $manager->save_license($key, $license);
            $logger->log_request($key, $domain, true, '授权验证成功（首次绑定域名和插件ID）', $plugin_id);
        }

        if ($license['domain'] !== $domain) {
            $logger->log_request($key, $domain, false, '域名不匹配', $plugin_id);
            return new WP_REST_Response(['success' => false, 'message' => '域名不匹配'], 403);
        }

        if (($license['plugin_id'] ?? '') !== $plugin_id) {
            $logger->log_request($key, $domain, false, '插件ID不匹配', $plugin_id);
            return new WP_REST_Response(['success' => false, 'message' => '插件ID不匹配'], 403);
        }

        if ($license['expire'] < $now) {
            $logger->log_request($key, $domain, false, '授权已过期', $plugin_id);
            return new WP_REST_Response(['success' => false, 'message' => '授权已过期'], 403);
        }

        if (!$license['active']) {
            $logger->log_request($key, $domain, false, '授权被封禁', $plugin_id);
            return new WP_REST_Response(['success' => false, 'message' => '授权被封禁'], 403);
        }

        $logger->log_request($key, $domain, true, '授权验证成功', $plugin_id);

        return new WP_REST_Response([
            'success' => true,
            'message' => '授权验证成功',
            'data' => [
                'license_key' => $key,
                'domain' => $license['domain'],
                'plugin_id' => $license['plugin_id'] ?? '',
                'expire' => $license['expire'],
                'product' => $license['product'] ?? '',
                'type' => $license['type'] ?? '',
            ]
        ]);
    }
}

new Tudoucode_License_API();
