<?php
// セッション（ログイン状態の記憶用）を開始
session_start();

// ステップ1で準備したGoogleライブラリの読み込み
require_once __DIR__ . '/google-api-client/vendor/autoload.php';

// 【設定】Google Cloud Consoleで取得した鍵情報をここに貼り付けてください
$clientId     = ''; 
$clientSecret = '';
$redirectUri  = ''; // XREAのURL


// すでにログイン済みの場合は、学生用トップ画面に自動転送
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: student.php');
    exit;
}

// Googleクライアントの設定
$client = new Google\Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// Googleに要求する情報の範囲（メールアドレスとプロフィール情報）
$client->addScope("email");
$client->addScope("profile");

// Googleのログイン画面へのURLを生成
$authUrl = $client->createAuthUrl();

// 他の画面やGoogleからエラーが送られてきた場合の検証と処理（方針4）
$errorMessage = '';
if (isset($_GET['error'])) {
    // 不正なスクリプトを埋め込まれないよう、HTMLエスケープを徹底
    $errorKey = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
    
    // エラー内容に応じた分かりやすい日本語メッセージの定義
    if ($errorKey === 'invalid_domain') {
        $errorMessage = '指定された組織のGoogleアカウント以外はログインできません。';
    } else {
        $errorMessage = 'ログイン中にエラーが発生しました（コード: ' . $errorKey . '）';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出席管理システム - ログイン</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f5f5f5; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 100%; }
        .btn-google { display: inline-block; background-color: #4285F4; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 20px; }
        .error-msg { background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; text-align: left; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>出席管理システム</h2>
    <p>組織のアカウントでログインしてください</p>

    <?php if (!empty($errorMessage)): ?>
        <div class="error-msg">
            <?= $errorMessage; ?>
        </div>
    <?php   endif; ?>

    <a href="<?= htmlspecialchars($authUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-google">
        Googleアカウントでログイン
    </a>
</div>

</body>
</html>