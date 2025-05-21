<?php
class Tudoucode_License_Manager {

    private $option_name = 'tudoucode_licenses';

    public function get_all() {
        return get_option($this->option_name, []);
    }

    public function save_all($licenses) {
        update_option($this->option_name, $licenses);
    }

    public function add_license($key, $domain, $expire, $product = '', $type = 'standard', $plugin_id = '') {
        $licenses = $this->get_all();
        $licenses[$key] = [
            'domain' => $domain,
            'plugin_id' => $plugin_id, // 新增插件ID字段
            'expire' => $expire,
            'active' => true,
            'created' => current_time('mysql'),
            'product' => $product,
            'type' => $type,
        ];
        $this->save_all($licenses);
    }

    public function delete_license($key) {
        $licenses = $this->get_all();
        unset($licenses[$key]);
        $this->save_all($licenses);
    }

    public function toggle_status($key, $status) {
        $licenses = $this->get_all();
        if (isset($licenses[$key])) {
            $licenses[$key]['active'] = $status;
            $this->save_all($licenses);
        }
    }

    public function get_license($key) {
        $licenses = $this->get_all();
        return $licenses[$key] ?? null;
    }

    // 保存单个授权码数据（更新某条授权记录）
    public function save_license($key, $license_data) {
        $licenses = $this->get_all();
        $licenses[$key] = $license_data;
        $this->save_all($licenses);
    }
}
