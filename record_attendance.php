<?php
// record_attendance.php

// 画面を切り替えずに裏側で通信するため、JSON形式で結果を返す設定
header('Content-Type: application/json; charset=utf-8');

// POST通信（データ送信）以外は弾く
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => '不正なアクセスです。']);
    exit;
}

// 送られてきた学籍番号を取得（空の場合は弾く）
$studentId = $_POST['student_id'] ?? '';

if (empty($studentId)) {
    echo json_encode(['status' => 'error', 'message' => '学籍番号が読み取れませんでした。']);
    exit;
}

// データベース接続ファイルの読み込み
require_once __DIR__ . '/db_connect.php';

try {
    // タイムゾーンを日本に設定し、今日の日付を取得
    date_default_timezone_set('Asia/Tokyo');
    $classDate = date('Y-m-d'); // 例: 2026-07-08

    // DBにデータを挿入するSQLの準備
    $sql = "INSERT INTO attendances (student_id, class_date) VALUES (:student_id, :class_date)";
    $stmt = $pdo->prepare($sql);
    
    // 値を安全にバインドして実行（SQLインジェクション対策）
    $stmt->bindValue(':student_id', $studentId, PDO::PARAM_STR);
    $stmt->bindValue(':class_date', $classDate, PDO::PARAM_STR);
    $stmt->execute();

    // 成功した場合のレスポンス
    echo json_encode([
        'status'  => 'success',
        'message' => htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8') . ' の出席を受け付けました！'
    ]);

} catch (PDOException $e) {
    // 方針4: 二重登録エラーのハンドリング
    // SQLステート23000 は「UNIQUE制約違反（すでに同じデータがある）」のエラーコードです
    if ($e->getCode() == '23000') {
        echo json_encode([
            'status'  => 'error',
            'message' => htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8') . ' はすでに本日の出席が登録されています。'
        ]);
    } else {
        // その他のDBエラー
        echo json_encode([
            'status'  => 'error',
            'message' => 'データベースエラーが発生しました。'
        ]);
    }
}
?>