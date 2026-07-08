<?php
// セッションを開始
session_start();

// Googleライブラリの読み込み
require_once __DIR__ . '/google-api-client/vendor/autoload.php';

// 【設定1】index.phpと同じ鍵情報を入力してください
$clientId = '';
$clientSecret = '';
$redirectUri  = '';


// 【設定2】許可したい組織のドメイン（@以降）を入力してください（例: 'sample.ac.jp'）
$allowedDomain = 'g.nihon-u.ac.jp'; 

// Googleクライアントの設定
$client = new Google\Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// 入力検証（方針4）：Googleからの認証コード（code）が届いていない場合は不正アクセスとして弾く
if (!isset($_GET['code'])) {
    header('Location: index.php?error=missing_code');
    exit;
}

try {
    // 認証コードを使って、Googleからアクセストークンを取得
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // ログインしたユーザーのプロフィール情報を取得
    $googleService = new Google\Service\Oauth2($client);
    $userInfo = $googleService->userinfo->get();

    $email = $userInfo->getEmail(); // メールアドレスを取得
    $name  = $userInfo->getName();  // 名前を取得

    // 入力検証（方針4）：メールアドレスのドメインが指定のものと一致するかチェック
    if (str_ends_with($email, '@' . $allowedDomain)) {
        
        // 認証成功：セッションにユーザー情報を保存（既存の要件定義の「学生情報取得」に対応）
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_email']     = $email;
        $_SESSION['user_name']      = $name;
        
        // 学籍番号の抽出（例: s12345@school.ac.jp から 's12345' を取り出す）
        $_SESSION['student_id']     = strstr($email, '@', true);

        // 学生トップ画面へ自動転送
        header('Location: student.php');
        exit;
        
    } else {
        // ドメインが異なる場合はエラー画面へ（方針4）
        header('Location: index.php?error=invalid_domain');
        exit;
    }

} catch (Exception $e) {
    // Googleとの通信エラーなどの例外処理（方針4）
    header('Location: index.php?error=auth_failed');
    exit;
}