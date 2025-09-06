<?php
// 自动加载 Composer 依赖
require __DIR__ . '/vendor/autoload.php';

// 加载 .env 文件中的环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 加载配置文件
$config = include(__DIR__ . '/config.php');

// 启用或禁用错误报告
if ($config['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// 启动会话
session_start();    

// 生成 CSRF 令牌（如果不存在）
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes($config['csrf_token_length']));
}

// 加载翻译文件
if (isset($_GET['language']) && in_array($_GET['language'], $config['allowed_languages'])) {
    $_SESSION['language'] = $_GET['language'];
}
$language = isset($_SESSION['language']) ? $_SESSION['language'] : $config['default_language'];
$lang_file = __DIR__ . "/lang/{$language}.php";
if (file_exists($lang_file)) {
    $lang = include($lang_file);
} else {
    $lang = include(__DIR__ . "/lang/zh-cn.php");
}

// 验证 Cloudflare Turnstile 的函数
function verify_turnstile($response, $secret) {
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => $secret,
        'response' => $response
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    if ($result === FALSE) {
        return false;
    } else {
        $turnstile = json_decode($result);
        return $turnstile->success;
    }
}

// 获取版本信息的函数
function fetch_versions($url) {
    $json = @file_get_contents($url);
    if ($json === FALSE) {
        return false;
    } else {
        return json_decode($json, true);
    }
}
require_once __DIR__ . '/includes/bing_bg.php';
$bing_bg = get_bing_daily_image();
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['title']; ?></title>
    <meta name="Description" content="Minecraft Resources Download Tools">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.4.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        body {
            background-image: url('<?php echo $bing_bg; ?>');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }
        .main-card {
            background: rgba(255,255,255,0.92);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            margin-top: 40px;
            margin-bottom: 40px;
            padding: 32px 24px;
        }
        .center {
            text-align: center;
        }
        .form-control, .btn {
            border-radius: 8px;
        }
        .modal-content {
            border-radius: 12px;
        }
        * {
            font-family: "微软雅黑", "Microsoft YaHei", Arial, sans-serif;
        }
        @media (max-width: 576px) {
            .main-card {
                padding: 16px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-12">
                <div class="main-card">
                    <div class="center mb-4">
                        <h1><?php echo $lang['title']; ?></h1>
                        <small class="text-muted"><?php echo $lang['subtitle'] ?? ''; ?></small>
                    </div>
                    <form method="get" action="" class="mb-3">
                        <div class="form-group">
                            <label><?php echo $lang['language']; ?></label>
                            <select class="form-control" name="language" onchange="this.form.submit()">
                                <?php foreach ($config['allowed_languages'] as $lang_code): ?>
                                    <option value="<?php echo $lang_code; ?>" <?php if ($language == $lang_code) echo 'selected'; ?>>
                                        <?php echo $lang[$lang_code]; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <label><?php echo $lang['version']; ?></label>
                            <input type="text" class="form-control" name="version" placeholder="如 1.20.1" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo $lang['type']; ?></label>
                            <select class="form-control" name="select" required>
                                <option value="forge"><?php echo $lang['forge']; ?></option>
                                <option value="optifine"><?php echo $lang['optifine']; ?></option>
                                <option value="minecraft_client"><?php echo $lang['minecraft_client']; ?></option>
                                <option value="minecraft_server"><?php echo $lang['minecraft_server']; ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="cf-turnstile" data-sitekey="<?php echo $config['turnstile_sitekey']; ?>"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block"><?php echo $lang['submit']; ?></button>
                    </form>
                    <div id="hitokoto" class="text-center text-muted my-3" style="font-size:1.1em;"></div>
                    <script>
                        fetch('<?php echo addslashes($config['hitokoto_api']); ?>')
                            .then(response => response.text())
                            .then(text => {
                                document.getElementById('hitokoto').innerText = text;
                            });
                    </script>
                    <div class="mt-4">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cf-turnstile-response'])) {
                            // 验证 CSRF 令牌
                            if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                                echo "<div class=\"alert alert-danger\"><strong>Invalid CSRF token.</strong></div>";
                                exit;
                            }

                            // 验证 Cloudflare Turnstile
                            $turnstile_response = $_POST['cf-turnstile-response'];
                            if (verify_turnstile($turnstile_response, $config['turnstile_secret'])) {
                                // 处理 Forge 下载
                                if (!empty($_POST["version"]) && $_POST["select"] == 'forge') {
                                    $verlist = $config['bmclapi_base_url'] . "/forge/minecraft/" . $_POST["version"];
                                    $dejson = fetch_versions($verlist);
                                    if ($dejson === FALSE) {
                                        echo "<div class=\"alert alert-danger\"><strong>" . $lang['query_failed'] . "</strong></div>";
                                    } else {
                                        echo '<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>' . $lang['attention'] . '</strong></div>';
                                        echo "<table class=\"table table-bordered center\">";
                                        echo "<tr><th>" . $lang['version'] . "</th><th>时间</th><th>build</th><th>" . $lang['download'] . "</th></tr>";
                                        foreach ($dejson as $key => $value) {
                                            echo "<tr><td width=\"20%\">" . $value['version'] . "</td><td width=\"40%\">" . $value['modified'] . "</td><td width=\"20%\">" . $value['build'] . "</td><td width=\"20%\"><a href=\"" . $config['bmclapi_base_url'] . "/forge/download?mcversion=" . $_POST["version"] . "&version=" . $value['version'] . "&category=universal&format=jar\">" . $lang['download'] . "</a></td></tr>";
                                        }
                                        echo "</table>";
                                    }
                                }
                                // 处理 Optifine 下载
                                if (!empty($_POST["version"]) && $_POST["select"] == 'optifine') {
                                    $verlist = $config['bmclapi_base_url'] . "/optifine/" . $_POST["version"];
                                    $dejson = fetch_versions($verlist);
                                    if ($dejson === FALSE) {
                                        echo "<div class=\"alert alert-danger\"><strong>" . $lang['query_failed'] . "</strong></div>";
                                    } else {
                                        echo '<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>' . $lang['attention'] . '</strong></div>';
                                        echo "<table class=\"table table-bordered center\">";
                                        echo "<tr><th>" . $lang['type'] . "</th><th>" . $lang['version'] . "</th><th>" . $lang['download'] . "</th></tr>";
                                        foreach ($dejson as $key => $value) {
                                            echo "<tr><td width=\"40%\">" . $value['type'] . "</td><td width=\"20%\">" . $value['patch'] . "</td><td width=\"20%\"><a href=\"" . $config['bmclapi_base_url'] . "/optifine/" . $_POST["version"] . "/" . $value['type'] . "/" . $value['patch'] . "\">" . $lang['download'] . "</a></td></tr>";
                                        }
                                        echo "</table>";
                                    }
                                }
                                // 处理 Minecraft 客户端下载
                                if (!empty($_POST["version"]) && $_POST["select"] == 'minecraft_client') {
                                    $download_link = $config['bmclapi_base_url'] . "/version/" . $_POST["version"] . "/client";
                                    echo '<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>' . $lang['auto_download'] . '</strong></div>';
                                    echo '<script>window.location.href="' . $download_link . '"</script>';
                                }
                                // 处理 Minecraft 服务端下载
                                if (!empty($_POST["version"]) && $_POST["select"] == 'minecraft_server') {
                                    $download_link = $config['bmclapi_base_url'] . "/version/" . $_POST["version"] . "/server";
                                    echo '<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>' . $lang['auto_download'] . '</strong></div>';
                                    echo '<script>window.location.href="' . $download_link . '"</script>';
                                }
                            } else {
                                echo "<div class=\"alert alert-danger alert-dismissible fade show\"><strong>" . $lang['robot_warning'] . "</strong></div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="center mb-3">
            <p><?php echo $lang['all_rights_reserved']; ?></p>
            <p><?php echo $lang['resource_provided_by']; ?><a href="https://bmclapidoc.bangbang93.com/" target="_blank">BMCLAPI</a></p>
            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#myModal"><?php echo $lang['sponsor_author']; ?></button>
        </div>
    </div>
    <!-- 赞助弹窗 -->
    <div class="modal fade" id="myModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><?php echo $lang['sponsor_author']; ?></h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body center">
                    <img src="https://bmclapidoc.bangbang93.com/alipay.jpg" width="200px" class="rounded">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo $lang['close']; ?></button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
