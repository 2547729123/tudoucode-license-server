<?php
add_action('admin_menu', function () {
    add_menu_page(
        '码铃薯授权中心',
        '授权中心',
        'manage_options',
        'tudoucode-license-center',
        'tudoucode_render_admin_page',
        'dashicons-shield-alt',
        75
    );
});

function tudoucode_render_admin_page() {
    $manager = new Tudoucode_License_Manager();
    $logger  = new Tudoucode_License_Logger();
    $licenses = $manager->get_all();
    $logs = $logger->get_logs();

    // 单个添加
    if (isset($_POST['add_license']) && check_admin_referer('add_license_action')) {
        $manager->add_license(
            sanitize_text_field($_POST['license_key']),
            sanitize_text_field($_POST['license_domain']),
            sanitize_text_field($_POST['license_expire']),
            sanitize_text_field($_POST['license_product']),
            sanitize_text_field($_POST['license_type']),
            sanitize_text_field($_POST['license_plugin']) // 插件ID
        );
        echo '<div class="notice notice-success"><p>添加成功！</p></div>';
    }

    // 批量添加
    if (isset($_POST['batch_generate']) && check_admin_referer('batch_generate_action')) {
        $count = intval($_POST['batch_count']);
        $domain = sanitize_text_field($_POST['batch_domain']);
        $expire = sanitize_text_field($_POST['batch_expire']);
        $product = sanitize_text_field($_POST['batch_product']);
        $type = sanitize_text_field($_POST['batch_type']);
        $plugin = sanitize_text_field($_POST['batch_plugin']);

        $generated = 0;
        for ($i = 0; $i < $count; $i++) {
            $key = 'TDC-' . strtoupper(bin2hex(random_bytes(8)));
            $manager->add_license($key, $domain, $expire, $product, $type, $plugin);
            $generated++;
        }
        echo '<div class="notice notice-success"><p>成功生成 ' . $generated . ' 个授权码！</p></div>';
    }

    // 删除
    if (isset($_POST['delete_license']) && check_admin_referer('delete_license_action')) {
        $manager->delete_license(sanitize_text_field($_POST['license_key']));
        echo '<div class="notice notice-warning"><p>已删除授权码！</p></div>';
    }

    // 启用/封禁
    if (isset($_POST['toggle_license']) && check_admin_referer('toggle_license_action')) {
        $manager->toggle_status(
            sanitize_text_field($_POST['license_key']),
            ($_POST['toggle_action'] === 'enable')
        );
    }
    ?>

    <div class="wrap">
        <h1>码铃薯授权中心</h1>

        <h2>添加新授权</h2>
        <form method="post">
            <?php wp_nonce_field('add_license_action'); ?>
            <table class="form-table">
                <tr><th><label>授权码</label></th><td><input name="license_key" required /></td></tr>
                <tr><th><label>绑定域名</label></th><td><input name="license_domain" placeholder="可留空" /></td></tr>
                <tr><th><label>到期时间</label></th><td><input type="date" name="license_expire" required /></td></tr>
                <tr><th><label>产品ID</label></th><td><input name="license_product" /></td></tr>
                <tr><th><label>插件ID</label></th><td><input name="license_plugin" /></td></tr>
                <tr><th><label>授权类型</label></th><td><input name="license_type" value="standard" /></td></tr>
            </table>
            <p><input type="submit" class="button button-primary" value="添加授权码" /></p>
            <input type="hidden" name="add_license" value="1" />
        </form>

        <h2>批量生成授权码</h2>
        <form method="post">
            <?php wp_nonce_field('batch_generate_action'); ?>
            <table class="form-table">
                <tr><th><label>生成数量</label></th><td><input type="number" name="batch_count" min="1" max="1000" required /></td></tr>
                <tr><th><label>绑定域名</label></th><td><input name="batch_domain" placeholder="可留空" /></td></tr>
                <tr><th><label>到期时间</label></th><td><input type="date" name="batch_expire" required /></td></tr>
                <tr><th><label>产品ID</label></th><td><input name="batch_product" /></td></tr>
                <tr><th><label>插件ID</label></th><td><input name="batch_plugin" /></td></tr>
                <tr><th><label>授权类型</label></th><td><input name="batch_type" value="standard" /></td></tr>
            </table>
            <p><input type="submit" class="button button-secondary" value="批量生成授权码" /></p>
            <input type="hidden" name="batch_generate" value="1" />
        </form>

<h2>所有授权码</h2>
<form method="get" style="margin-bottom: 20px;">
    <input type="hidden" name="page" value="tudoucode-license-center" />
    <input type="text" name="search_domain" placeholder="按域名搜索" value="<?php echo esc_attr($_GET['search_domain'] ?? ''); ?>" />
    <input type="text" name="search_product" placeholder="按产品ID搜索" value="<?php echo esc_attr($_GET['search_product'] ?? ''); ?>" />
    <input type="text" name="search_plugin" placeholder="按插件ID搜索" value="<?php echo esc_attr($_GET['search_plugin'] ?? ''); ?>" />
    <input type="text" name="search_type" placeholder="按类型搜索" value="<?php echo esc_attr($_GET['search_type'] ?? ''); ?>" />
    <button type="submit" class="button">🔍 搜索</button>
    <a href="<?php echo admin_url('admin.php?page=tudoucode-license-center'); ?>" class="button">🔄 重置</a>
</form>

<?php
$search_domain = sanitize_text_field($_GET['search_domain'] ?? '');
$search_product = sanitize_text_field($_GET['search_product'] ?? '');
$search_plugin = sanitize_text_field($_GET['search_plugin'] ?? '');
$search_type = sanitize_text_field($_GET['search_type'] ?? '');

$filtered_licenses = array_filter($licenses, function ($data) use ($search_domain, $search_product, $search_plugin, $search_type) {
    $match = true;
    if ($search_domain && stripos($data['domain'], $search_domain) === false) $match = false;
    if ($search_product && stripos($data['product'] ?? '', $search_product) === false) $match = false;
    if ($search_plugin && stripos($data['plugin_id'] ?? '', $search_plugin) === false) $match = false; // 改为 plugin_id
    if ($search_type && stripos($data['type'] ?? '', $search_type) === false) $match = false;
    return $match;
});
?>

<table class="widefat striped">
    <thead>
        <tr>
            <th>授权码</th><th>域名</th><th>过期时间</th><th>产品</th><th>插件ID</th><th>类型</th><th>状态</th><th>操作</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($filtered_licenses as $key => $data): ?>
            <tr>
                <td><?php echo esc_html($key); ?></td>
                <td><?php echo esc_html($data['domain']); ?></td>
                <td><?php echo esc_html($data['expire']); ?></td>
                <td><?php echo esc_html($data['product'] ?? '-'); ?></td>
                <td><?php echo esc_html($data['plugin_id'] ?? '-'); ?></td> <!-- 这里改为 plugin_id -->
                <td><?php echo esc_html($data['type'] ?? '-'); ?></td>
                <td><?php echo ($data['active'] ? '✅ 有效' : '⛔ 已封禁'); ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <?php wp_nonce_field('toggle_license_action'); ?>
                        <input type="hidden" name="toggle_license" value="1" />
                        <input type="hidden" name="license_key" value="<?php echo esc_attr($key); ?>" />
                        <input type="hidden" name="toggle_action" value="<?php echo $data['active'] ? 'disable' : 'enable'; ?>" />
                        <button class="button"><?php echo $data['active'] ? '封禁' : '启用'; ?></button>
                    </form>
                    <form method="post" onsubmit="return confirm('确认删除此授权码？');" style="display:inline;">
                        <?php wp_nonce_field('delete_license_action'); ?>
                        <input type="hidden" name="delete_license" value="1" />
                        <input type="hidden" name="license_key" value="<?php echo esc_attr($key); ?>" />
                        <button class="button button-danger">删除</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($filtered_licenses)): ?>
            <tr><td colspan="8">暂无符合条件的授权码</td></tr>
        <?php endif; ?>
    </tbody>
</table>

        <?php
        // 日志分页
        $page = (isset($_GET['log_page']) && is_numeric($_GET['log_page'])) ? max(1, intval($_GET['log_page'])) : 1;
        $per_page = 50;
        $all_logs = array_reverse($logs);
        $total_logs = min(100, count($all_logs));
        $logs_to_show = array_slice($all_logs, ($page - 1) * $per_page, $per_page);
        $total_pages = ceil($total_logs / $per_page);
        ?>

        <h2>授权验证日志（最多显示最近 100 条，每页 50 条）</h2>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width: 160px;">时间</th>
                    <th>IP</th>
                    <th>授权码</th>
                    <th>域名</th>
                    <th>状态</th>
                    <th>信息</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs_to_show)) : ?>
                    <?php foreach ($logs_to_show as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log['time']); ?></td>
                            <td><?php echo esc_html($log['ip']); ?></td>
                            <td><code><?php echo esc_html($log['key']); ?></code></td>
                            <td><?php echo esc_html($log['domain']); ?></td>
                            <td>
                                <?php if ($log['status'] === '成功') : ?>
                                    <span style="color: green;">✔ 成功</span>
                                <?php else : ?>
                                    <span style="color: red;">✘ 失败</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($log['status'] !== '成功') {
                                    $reason = strtolower(trim($log['msg']));
                                    if (strpos($reason, 'expired') !== false || strpos($reason, '过期') !== false) echo '已过期，拒绝访问';
                                    elseif (strpos($reason, 'domain') !== false || strpos($reason, '域名') !== false) echo '域名不匹配';
                                    elseif (strpos($reason, 'not found') !== false || strpos($reason, '不存在') !== false) echo '授权码不存在';
                                    elseif (strpos($reason, 'banned') !== false || strpos($reason, '封禁') !== false) echo '授权码已封禁';
                                    elseif (strpos($reason, 'invalid') !== false || strpos($reason, '无效') !== false) echo '无效的授权码';
                                    else echo '其他错误：' . esc_html($log['msg']);
                                } else {
                                    echo '验证通过';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6">暂无日志</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php
                        $current = ($i === $page);
                        $url = add_query_arg(['page' => 'tudoucode-license-center', 'log_page' => $i]);
                        ?>
                        <a href="<?php echo esc_url($url); ?>" class="button <?php echo $current ? 'button-primary' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>

        <h3>📘 开发文档入口</h3>
        <p>
            <a href=/wp-content/plugins/tudoucode-license-server/dev-doc.html target="_blank">👉 进阶版开发文档（不含用户插件端授权模块调用教程）详细的去插件文件夹根目录查看吧（限管理员哦）！</a>
     </p>
    </div>
<?php
}
