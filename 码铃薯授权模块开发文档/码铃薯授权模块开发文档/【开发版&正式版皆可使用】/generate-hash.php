<?php
/**
 * 自动生成插件目录内全部文件的 sha256 哈希，并保存为 includes/.hash 文件。
 * - 遍历插件所有文件（包含子目录）
 * - 排除 .hash 文件、当前哈希生成脚本自身，以及指定扩展名和辅助文件
 * - 智能识别插件主文件（优先同名，其次查找含 Author 的 PHP 文件）
 */

$plugin_root = __DIR__; // 插件根目录
$hashes = [];

/**
 * 递归扫描目录，记录所有文件的相对路径及其 SHA256 哈希
 */
function scanFiles($dir, $base) {
    global $hashes;

    // 排除规则：扩展名与文件名
    $excluded_extensions = ['bat', 'sh', 'cmd'];
    $excluded_files = ['README.md', '.gitignore'];

    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            scanFiles($path, $base);
        } else {
            // 标准化路径格式
            $base = str_replace('\\', '/', $base);
            $path = str_replace('\\', '/', $path);
            $relative = str_replace($base . '/', '', $path);

            // 当前脚本路径（标准化）
            $current_script = str_replace('\\', '/', __FILE__);
            $current_relative = str_replace($base . '/', '', $current_script);

            // 获取扩展名和文件名
            $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
            $basename = basename($relative);

            // 排除 .hash 文件 和 当前脚本自身，以及指定扩展名和文件名
            if (
                $relative === 'includes/.hash'
                || $relative === $current_relative
                || in_array($ext, $excluded_extensions)
                || in_array($basename, $excluded_files)
            ) {
                continue;
            }

            // 生成哈希
            $hashes[$relative] = hash_file('sha256', $path);
        }
    }
}

/**
 * 智能识别插件主文件（优先同名，其次查找含 Author 注释的 PHP 文件）
 */
function findPluginMainFile($plugin_root) {
    $plugin_slug = basename($plugin_root);
    $expected_file = $plugin_root . '/' . $plugin_slug . '.php';

    if (file_exists($expected_file)) {
        return $expected_file;
    }

    // 否则查找根目录含 Author 字样的 PHP 文件
    $files = scandir($plugin_root);
    foreach ($files as $file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') continue;

        $full_path = $plugin_root . '/' . $file;
        $content = file_get_contents($full_path);

        // 简单匹配 Author 字样
        if (stripos($content, 'Author') !== false) {
            return $full_path;
        }
    }

    return null;
}

// 1. 扫描插件目录所有文件（含子目录）
scanFiles($plugin_root, $plugin_root);

// 2. 查找插件主文件，并加入哈希（如未已包含）
$main_file_path = findPluginMainFile($plugin_root);
if ($main_file_path) {
    $main_relative = str_replace(str_replace('\\', '/', $plugin_root) . '/', '', str_replace('\\', '/', $main_file_path));
    if (!isset($hashes[$main_relative])) {
        $hashes[$main_relative] = hash_file('sha256', $main_file_path);
    }
} else {
    echo "⚠️ 未能识别插件主文件（未找到同名文件或带 Author 的 PHP 文件）\n";
}

// 3. 排序（可选，美观）
ksort($hashes);

// 4. 写入 includes/.hash
$file_content = '';
foreach ($hashes as $file => $hash) {
    $file_content .= $file . ':' . $hash . PHP_EOL;
}

$hash_file_path = $plugin_root . '/includes/.hash';
if (!is_dir(dirname($hash_file_path))) {
    mkdir(dirname($hash_file_path), 0755, true);
}
file_put_contents($hash_file_path, $file_content);

echo "✅ 哈希生成完成，文件总数：" . count($hashes) . PHP_EOL;
