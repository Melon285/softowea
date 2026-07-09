ユースケース

```mermaid
flowchart LR
    %% Actors
    Student[学生]
    Teacher[教員]

    %% Student Use Cases
    subgraph StudentUC[学生機能]
        UC1(ログイン)
        UC2(QRコード生成)
        UC3(出席履歴確認)
        UC4(出席回数確認)
        UC5(遅刻回数確認)
        UC6(学生情報取得)
    end

    %% Teacher Use Cases
    subgraph TeacherUC[教員機能]
        UC7(ログイン)
        UC8(QRコード読取)
        UC9(出席登録)
        UC10(出席一覧確認)
        UC11(出席データ保存)
        UC12(重複出席チェック)
    end

    %% Actor Associations
    Student --> UC1
    Student --> UC2
    Student --> UC3
    Student --> UC4
    Student --> UC5

    Teacher --> UC7
    Teacher --> UC8
    Teacher --> UC10

    %% Include Relationships
    UC2 -. "<<include>>" .-> UC6
    UC8 -. "<<include>>" .-> UC9
    UC9 -. "<<include>>" .-> UC11
    UC9 -. "<<include>>" .-> UC12

    %% Extend Relationships
    UC4 -. "<<extend>>" .-> UC3
    UC5 -. "<<extend>>" .-> UC3
```
クラス図
```mermaid
classDiagram
    class User {
        <<abstract>>
        +int id
        +string email
        +string passwordHash
        +datetime createdAt
        +login()
        +logout()
    }

    class Student {
        +string studentNumber
        +string name
        +generateQRCode()
        +viewAttendanceHistory()
        +getAttendanceCount()
        +getLateCount()
    }

    class Teacher {
        +string name
        +scanQRCode()
        +viewAttendanceList()
    }

    class QRCode {
        +int id
        +string token
        +datetime issuedAt
        +datetime expiresAt
        +generate()
        +validate()
    }

    class Attendance {
        +int id
        +datetime attendanceTime
        +AttendanceStatus status
        +record()
        +isDuplicate()
    }

    class AttendanceService {
        +registerAttendance()
        +checkDuplicate()
        +getAttendanceHistory()
        +calculateAttendanceCount()
        +calculateLateCount()
    }

    class AuthService {
        +loginWithGoogle()
        +loginWithEmail()
        +validateUser()
    }

    class AttendanceStatus {
        <<enumeration>>
        PRESENT
        LATE
    }

    User <|-- Student
    User <|-- Teacher

    Student "1" *-- "1" QRCode : owns
    Student "1" -- "0..*" Attendance : has

    Teacher "1" --> "0..*" Attendance : manages

    Attendance --> AttendanceStatus

    AttendanceService ..> Attendance : uses
    AttendanceService ..> Student : uses

    AuthService ..> User : authenticates
 ```
シーケンス図


学生ログイン
```mermaid
sequenceDiagram
actor Student as 学生
participant UI as ログイン画面
participant Controller as AuthController
participant DB as UserDB

Student->>UI: メールアドレス入力
UI->>Controller: ログイン要求

Controller->>DB: ユーザー検索(email)

alt ユーザー存在
    DB-->>Controller: ユーザー情報
    Controller-->>UI: ログイン成功
    UI-->>Student: ダッシュボード表示
else ユーザー未登録
    DB-->>Controller: 該当なし
    Controller-->>UI: エラー
    UI-->>Student: ログイン失敗表示
end
```


QRコード生成
```mermaid
sequenceDiagram
actor Student as 学生
participant UI as ダッシュボード
participant Controller as QRController
participant DB as UserDB

Student->>UI: QRコード表示要求

UI->>Controller: QR生成要求

Controller->>DB: 学生情報取得

alt 学生情報取得成功
    DB-->>Controller: 学生番号・氏名

    Controller->>Controller: トークン生成
    Controller->>Controller: QRコード生成

    Controller-->>UI: QR画像返却
    UI-->>Student: QRコード表示

else 学生情報取得失敗
    DB-->>Controller: エラー
    Controller-->>UI: エラー通知
    UI-->>Student: QR生成失敗
end
```


出席登録
```mermaid
sequenceDiagram
actor Teacher as 教員
participant UI as QR読取画面
participant Controller as AttendanceController
participant DB as AttendanceDB

Teacher->>UI: QRコードを読み取る

UI->>Controller: QRデータ送信

Controller->>Controller: QRトークン検証

alt QRトークン有効

    Controller->>DB: 学生情報取得

    alt 学生存在

        DB-->>Controller: 学生情報

        Controller->>DB: 重複出席確認

        alt 未登録

            Controller->>DB: 出席記録保存

            DB-->>Controller: 保存成功

            Controller-->>UI: 出席登録成功
            UI-->>Teacher: 登録完了表示

        else 既に登録済み

            Controller-->>UI: 重複エラー
            UI-->>Teacher: 既に出席済み

        end

    else 学生不存在

        Controller-->>UI: 学生情報なし
        UI-->>Teacher: 読取失敗

    end

else QRトークン無効

    Controller-->>UI: 無効QR
    UI-->>Teacher: 読取失敗

end
```

出席履歴確認
```mermaid
sequenceDiagram
actor Student as 学生
participant UI as ダッシュボード
participant Controller as AttendanceController
participant DB as AttendanceDB

Student->>UI: 出席履歴表示

UI->>Controller: 履歴取得要求

Controller->>DB: 出席履歴検索

DB-->>Controller: 出席データ一覧

loop 出席レコードごと
    Controller->>Controller: 出席率計算
    Controller->>Controller: 遅刻回数集計
end

Controller-->>UI: 履歴・集計結果

UI-->>Student: 履歴一覧表示
```

状態遷移図


学生セッション
```mermaid
stateDiagram-v2
    [*] --> 未認証

    未認証 --> 認証中 : ログイン要求

    認証中 --> 認証済み : 認証成功
    認証中 --> 認証失敗 : 認証失敗

    認証失敗 --> 未認証 : 再ログイン

    認証済み --> ログアウト : ログアウト要求
    ログアウト --> 未認証

    認証済み --> セッション期限切れ : タイムアウト
    セッション期限切れ --> 未認証

    未認証 --> [*]
```


QRコード
```mermaid
stateDiagram-v2
    [*] --> 未生成

    未生成 --> 生成済み : QR生成要求

    生成済み --> 表示中 : QR表示

    表示中 --> 読取済み : 教員が読取

    表示中 --> 期限切れ : 有効期限超過

    読取済み --> 使用済み : 出席登録成功

    期限切れ --> 再生成待ち : QR再生成要求

    使用済み --> [*]
    再生成待ち --> [*]
```

出席記録
```mermaid
stateDiagram-v2
    [*] --> 未登録

    未登録 --> 登録処理中 : QR読取

    登録処理中 --> 出席登録済み : 定刻内登録
    登録処理中 --> 遅刻登録済み : 遅刻判定

    登録処理中 --> 登録失敗 : 無効QR
    登録処理中 --> 重複登録 : 既登録検知

    登録失敗 --> 未登録 : 再試行

    重複登録 --> 出席登録済み : 既存記録参照
    重複登録 --> 遅刻登録済み : 既存記録参照

    出席登録済み --> [*]
    遅刻登録済み --> [*]
```


出席
```mermaid
stateDiagram-v2
    [*] --> 未ログイン

    未ログイン --> ログイン済み : 認証成功

    ログイン済み --> QR生成済み : QR生成

    QR生成済み --> QR表示中 : QR表示

    QR表示中 --> 読取待ち : 教員読取待機

    読取待ち --> 出席処理中 : QR読取

    出席処理中 --> 出席完了 : 出席登録成功
    出席処理中 --> 遅刻完了 : 遅刻登録成功
    出席処理中 --> エラー : 無効QR

    エラー --> QR表示中 : 再試行

    出席完了 --> ログイン済み : ダッシュボード表示
    遅刻完了 --> ログイン済み : ダッシュボード表示

    ログイン済み --> 未ログイン : ログアウト

    未ログイン --> [*]
```


# テスト結果報告書
## プロジェクト情報
- アプリ名: [QR出席]
- 氏名またはチーム名: [明坂青空]
- テスト実施日: [2026/7/8]
- テスト対象: [ログイン、画面、APIなど]

|#|テスト対象|テスト観点(正常/境界/異常)|テスト条件|テスト手順(1行)|期待値(1行)|結果(○/×)|
|1|index.php|正常|許可されたドメインでのログイン|@g.nihon-u.ac.jp のGoogleアカウントを選択してログインする|認証に成功し、student.php 画面に遷移する|〇|
|2|index.php|異常|許可外ドメインでのログイン|@gmail.com 等の許可されていないアカウントを選択してログインする|index.php に戻り「指定された組織のGoogleアカウント以外は...」と表示される|〇|
|3|callback.php|異常|認証コードなしでの直接アクセス|ブラウザのURLバーに直接 callback.php を入力してアクセスする|index.php に戻り「ログイン中にエラーが発生しました（コード: missing_code）」と表示される|〇|
|4|student.php|異常|未ログイン状態でのアクセス|シークレットウィンドウを開き|直接 student.php にアクセスする|index.php に戻り「ログイン中にエラーが発生しました（コード: unauthorized_access）」と表示される|〇|
|5|index.php|正常|ログイン済み状態でのアクセス|student.php 表示後に、URLバーに index.php と入力してアクセスする|自動的に student.php へリダイレクトされる|〇|
|6|index.php|異常|未定義のエラーパラメータ付きアクセス|URLバーに index.php?error=unknown_error と入力してアクセスする|画面に「ログイン中にエラーが発生しました（コード: unknown_error）」と表示される|〇|
|7|callback.php|異常|Google認証画面でのアクセス拒否|ログイン時のGoogleの同意画面でキャンセル（または戻る）を行う|index.php に戻り、エラーメッセージが表示される|〇|
|8|callback.php|境界|短い文字数の学籍番号の抽出|メールのローカルパートが1文字（例:a@g.nihon-u.ac.jp）のアカウントでログインする|学籍番号として「a」が正しく抽出され、画面に表示される|〇|
|9|student.php|正常|学生情報の正しい画面表示|ログイン後の student.php の表部分を確認する|自身の学籍番号、氏名、メールアドレスが正しく表示されている|〇|
|10|student.php|正常|QRコードの正常な描画|student.php の画面中央の点線枠内を確認する|学籍番号のデータが埋め込まれたQRコード画像が描画されている|〇|
|11|student.php|境界|学籍番号が空の状態|セッションまたはDBの学籍番号を空にした状態で student.php を表示する|「学籍番号が取得できなかったため、QRコードを生成できません。」と表示され、QR枠が出ない|〇|
|12|student.php|異常|プロフィール名にHTMLタグが含まれる場合|Googleアカウントの名前を <script>alert(1)</script> に変更してログインする|スクリプトは実行されず、文字としてそのままエスケープ表示される|〇|
|13|student.php|境界|極端に長い氏名やメールアドレスの表示|長い文字列の氏名が登録されたアカウントでログインする|表のレイアウトが大きく崩れることなく|折り返される等で適切に表示される|〇|
|14|teacher.php|正常|スキャナー起動とカメラの許可|teacher.php にアクセスし、ブラウザのカメラ許可ダイアログで「許可」を押す|カメラの映像が画面の枠内にリアルタイムで表示される|〇|
|15|teacher.php|異常|カメラの拒否|teacher.php にアクセスし、ブラウザのカメラ許可ダイアログで「ブロック」を押す|映像は表示されないが、アプリ全体がクラッシュすることなく待機状態になる|〇|
|16|teacher.php|正常|正常なQRコードの読み取り|学生用画面に表示されたQRコードを教員用カメラにかざす|枠が緑色になり「（学籍番号）の出席を受け付けました！」と表示される|〇|
|17|teacher.php|境界|同一QRコードの連続スキャン（通信中）|QRを読み取り「通信中...」と表示されている間に、全く同じQRコードを動かしながらかざし続ける|連続して通信が発生せず、1回分の処理だけが行われる|〇|
|18|teacher.php|境界|別QRコードの連続スキャン（通信中）|1つ目のQRを読み取り「通信中...」の表示中に、すかさず別の学生のQRコードをかざす|処理中フラグによりブロックされ、2つ目のQRの通信は発生しない|〇|
|19|teacher.php|異常|同一学生の二重登録（3秒経過後）|1回目の読み取り成功から3秒以上経過し画面がリセットされた後、同じQRを再度かざす|枠が赤色になり「すでに本日の出席が登録されています。」と表示される|〇|
|20|teacher.php|正常|別の学生の連続読み取り（3秒経過後）|1人目の読み取り成功から3秒以上経過した後、別の学生のQRをかざす|枠が緑色になり2人目の学生の出席も正常に受け付けられる|〇|
|21|teacher.php|異常|スキャン後の通信エラー（オフライン）|teacher.php 表示後に端末を機内モードにし、QRコードをかざす|枠が赤色になり「通信に失敗しました。」と表示され、クラッシュしない|〇|
|22|record_attendance.php|異常|GETメソッドでの不正アクセス|ブラウザのURLバーに直接 record_attendance.php を入力してアクセスする"画面に {""status"":""error"",""message"":""不正なアクセスです。""} のJSONが表示される"|〇|
|23|record_attendance.php|異常|学籍番号が空のPOSTリクエスト|開発者ツール等を使用し、student_id を空にしてPOST送信する|"{""status"":""error"",""message"":""学籍番号が読み取れませんでした。""} のJSONが返る"|〇|
|24|record_attendance.php|異常|SQLインジェクションを狙った入力|student_id に ' OR 1=1 -- のような文字列を入れてPOST送信する,DBエラーや不正ログインは起きず、そのままの文字列として安全に処理（またはエラー）される|〇|

## サマリー
- 合計: 24件
- 合格: [24]
- 不合格:[0]
- 通過率: [100%]
