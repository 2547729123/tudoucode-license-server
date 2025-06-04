@echo off
title 插件哈希生成工具
echo 正在执行 PHP 哈希生成脚本...
echo.

:: 检查 PHP 是否安装并可用
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ 未检测到 PHP，请确保已正确安装并配置环境变量！
    pause
    exit /b
)

:: 执行 PHP 脚本
php generate-hash.php

echo.
echo ✅ 执行完毕。你可以检查 includes/.hash 文件！
pause
