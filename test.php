<?php
// セッションを開始
session_start();

// 入力・状態検証（方針4）：ログインしていない場合は、アクセスを拒否してログイン画面へ強制送還
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // 直接アクセスされた場合のエラーコードを付与
    header('Location: index.php?error=unauthorized_access');
    exit;
}

// ログイン中であれば、セッションから安全に情報を取得（HTMLエスケープを徹底）
$studentId   = htmlspecialchars($_SESSION['student_id'], ENT_QUOTES, 'UTF-8');
$studentName = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
$studentEmail = htmlspecialchars($_SESSION['user_email'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>テスト画面 - ログイン成功</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #e8f5e9; color: #2e7d32; }
        .success-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 500px; margin: 50px auto; color: #333; }
        h1 { color: #2e7d32; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .info-table th, .info-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .info-table th { background-color: #f5f5f5; }
    </style>
</head>
<body>

<div class="success-box">
    <h1>🎉 ログインテスト成功！</h1>
    <p>Google認証および組織ドメインのチェックを正常に通過しました。</p>
    
    <table class="info-table">
        <tr>
            <th>抽出された学籍番号</th>
            <td><strong><?= $studentId; ?></strong></td>
        </tr>
        <tr>
            <th>Google上の氏名</th>
            <td><?= $studentName; ?></td>
        </tr>
        <tr>
            <th>メールアドレス</th>
            <td><?= $studentEmail; ?></td>
        </tr>
    </table>
    
    <p style="margin-top: 30px; font-size: 14px; color: #666;">
        ※これが確認できれば、Googleログインの連携テストは**完全合格**です！
    </p>
</div>

</body>
</html>