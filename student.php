<?php
// セッションを開始
session_start();

// ログイン検証
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: index.php?error=unauthorized_access');
    exit;
}

// セッションデータの取得
$studentId   = $_SESSION['student_id'] ?? '';
$studentName = $_SESSION['user_name'] ?? '';
$studentEmail = $_SESSION['user_email'] ?? '';

$errorMessage = '';

// バリデーション（学籍番号が必須）
if (empty($studentId)) {
    $errorMessage = '学籍番号が取得できなかったため、QRコードを生成できません。';
}

// 画面表示用にHTMLエスケープを適用
$safeStudentId    = htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8');
$safeStudentName  = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
$safeStudentEmail = htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出席用QRコード表示 - ログイン成功</title>
    <!-- 1. ダウンロードしたJavaScriptライブラリの読み込み -->
    <script src="qrcode.min.js"></script>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #e8f5e9; color: #2e7d32; margin: 0; }
        .success-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 500px; margin: 30px auto; color: #333; text-align: center; }
        h1 { color: #2e7d32; margin-top: 0; font-size: 24px; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left; }
        .info-table th, .info-table td { padding: 10px; border: 1px solid #ddd; }
        .info-table th { background-color: #f5f5f5; width: 40%; }
        .qr-container { margin: 25px auto; padding: 15px; border: 2px dashed #2e7d32; background-color: #fafafa; display: inline-block; min-width: 200px; min-height: 200px; }
        .error-msg { background-color: #ffebee; color: #c62828; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; text-align: left; border: 1px solid #ef9a9a; }
    </style>
</head>
<body>

<div class="success-box">
    <h1>🎉 ログイン成功・出席受付</h1>
    <p>授業の受付端末、または教員に以下のQRコードを提示してください。</p>
    
    <?php if (!empty($errorMessage)): ?>
        <div class="error-msg"><?= $errorMessage; ?></div>
    <?php endif; ?>

    <!-- 2. QRコードが描画されるエリア -->
    <?php if (empty($errorMessage)): ?>
        <div class="qr-container">
            <div id="qrcode"></div>
        </div>
    <?php endif; ?>
    
    <table class="info-table">
        <tr><th>学籍番号</th><td><strong><?= $safeStudentId; ?></strong></td></tr>
        <tr><th>氏名</th><td><?= $safeStudentName; ?></td></tr>
        <tr><th>メールアドレス</th><td><?= $safeStudentEmail; ?></td></tr>
    </table>
</div>

<!-- 3. JavaScriptでQRコードを生成する処理（シンプル版） -->
<?php if (empty($errorMessage)): ?>
<script>
    try {
        // PHPから学籍番号（半角英数字）のみを文字列として受け取る
        const qrData = "<?= $safeStudentId; ?>";
        
        console.log("埋め込むデータ:", qrData);

        // QRコードの生成を実行
        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 200,
            height: 200,
            correctLevel : QRCode.CorrectLevel.M
        });
        
        console.log("QRコードの生成に成功しました！");
    } catch (error) {
        console.error("QRコード生成スクリプト内でエラーが発生しました:", error);
    }
</script>
<?php endif; ?>

</body>
</html>