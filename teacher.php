<?php
// teacher.php
session_start();

// ※本来はここに「教員権限チェック」の検証を入れますが、
// 今回は読み取りテストを優先するため一旦コメントアウトしておきます。
/*
if (!isset($_SESSION['is_teacher']) || $_SESSION['is_teacher'] !== true) {
    exit('教員専用画面です。');
}
*/
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出席受付スキャナー（教員用）</title>
    <!-- 1. ダウンロードしたカメラ用ライブラリの読み込み -->
    <script src="html5-qrcode.min.js"></script>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f3e5f5; color: #4a148c; text-align: center; margin: 0; }
        .scanner-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        h1 { font-size: 22px; margin-top: 0; }
        #reader { width: 100%; margin-bottom: 20px; }
        
        /* 読み取り結果の表示エリア */
        #result-box { display: none; padding: 15px; border-radius: 4px; background-color: #e8f5e9; border: 2px solid #4caf50; color: #2e7d32; font-size: 18px; font-weight: bold; margin-top: 10px; }
        
        .hint { font-size: 13px; color: #666; margin-top: 10px; }
    </style>
</head>
<body>

<div class="scanner-box">
    <h1>📷 出席受付スキャナー</h1>
    <p>学生のQRコードをカメラにかざしてください</p>

    <!-- 2. カメラの映像が映し出されるエリア -->
    <div id="reader"></div>

    <!-- 3. 読み取った学籍番号を表示するエリア（最初は非表示） -->
    <div id="result-box">
        読取完了: <span id="scanned-id"></span>
    </div>

    <p class="hint">※「Request Camera Permissions」というボタンが出たら許可してください。</p>
</div>

<script>
    // 連続読み取りを防止するための変数
    let lastScannedId = "";
    // 処理中（通信中）かどうかを判定するフラグ
    let isProcessing = false; 

    // QRコードの読み取りに成功した時に実行される処理
    function onScanSuccess(decodedText, decodedResult) {
        // 処理中、または同じ学籍番号を連続で読み取った場合は無視する
        if (isProcessing || decodedText === lastScannedId) {
            return;
        }
        
        isProcessing = true;
        lastScannedId = decodedText;

        // 画面の表示エリアをリセットして「通信中」にする
        const resultBox = document.getElementById('result-box');
        const scannedIdSpan = document.getElementById('scanned-id');
        
        resultBox.style.display = 'block';
        resultBox.style.borderColor = '#ff9800'; // オレンジ色
        resultBox.style.backgroundColor = '#fff3e0';
        resultBox.style.color = '#e65100';
        scannedIdSpan.innerText = decodedText + " (通信中...)";

        // 裏側（record_attendance.php）へデータを送信する（AJAX通信）
        const formData = new FormData();
        formData.append('student_id', decodedText);

        fetch('record_attendance.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // PHPから返ってきた結果（data）に応じて表示を変える
            if (data.status === 'success') {
                // 成功時（緑色）
                resultBox.style.borderColor = '#4caf50';
                resultBox.style.backgroundColor = '#e8f5e9';
                resultBox.style.color = '#2e7d32';
                scannedIdSpan.innerText = data.message;
            } else {
                // エラー時・二重登録時（赤色）
                resultBox.style.borderColor = '#f44336';
                resultBox.style.backgroundColor = '#ffebee';
                resultBox.style.color = '#c62828';
                scannedIdSpan.innerText = "エラー: " + data.message;
            }
        })
        .catch(error => {
            console.error('通信エラー:', error);
            resultBox.style.borderColor = '#f44336';
            scannedIdSpan.innerText = "通信に失敗しました。";
        })
        .finally(() => {
            // 通信が終わったら、3秒後に次の読み取りができるようにリセット
            setTimeout(() => {
                isProcessing = false;
                lastScannedId = "";
                resultBox.style.display = 'none';
            }, 3000);
        });
    }

    // スキャナーの設定と起動
    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", 
        { fps: 10, qrbox: { width: 250, height: 250 } }, 
        false
    );
    html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>