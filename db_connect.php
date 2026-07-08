<?php
// db_connect.php

// 【設定】XREAのデータベース情報を入力してください（※実際のパスワード等はここに書き込まないよう注意！）
$host     = ''; // XREAの場合、localhostが多いです
$dbname   = ''; 
$username = '';
$password = '';

// 文字コードセット
$charset = 'utf8mb4';

// PDOの接続オプション設定（方針4：エラーを厳密に処理するための設定）
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // エラー時は例外(Exception)を投げる
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // データを連想配列として取得する
    PDO::ATTR_EMULATE_PREPARES   => false,                  // SQLインジェクション対策（静的プレースホルダ）
];

// データソースネーム（DSN）の組み立て
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

try {
    // データベースへの接続を実行
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // 接続に失敗した場合のエラーハンドリング
    // ※実際の開発では $e->getMessage() をそのまま画面に出すとセキュリティリスクになるため、汎用メッセージにします
    exit('データベース接続エラー: 設定やパスワードを見直してください。');
}
?>