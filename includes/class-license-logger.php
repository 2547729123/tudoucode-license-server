<?php
class Tudoucode_License_Logger {

    private $option_name = 'tudoucode_license_logs';
    private $limit = 1000; // 最多保留1000条日志

    // 新增插件ID参数，方便日志记录
    public function log_request($license_key, $domain, $success, $message, $plugin_id = '') {
        $logs = get_option($this->option_name, []);
        $logs[] = [
            'time'      => current_time('mysql'),
            'ip'        => $this->get_client_ip(),
            'key'       => $license_key,
            'domain'    => $domain,
            'plugin_id' => $plugin_id,
            'status'    => $success ? '成功' : '失败',
            'msg'       => $message,
        ];

        if (count($logs) > $this->limit) {
            $logs = array_slice($logs, -$this->limit);
        }

        update_option($this->option_name, $logs);
    }

    public function get_logs() {
        return get_option($this->option_name, []);
    }

    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
        return $_SERVER['REMOTE_ADDR'] ?? '未知IP';
    }
}
