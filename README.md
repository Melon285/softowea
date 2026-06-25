ユースケース

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

クラス図

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

シーケンス図
学生ログイン
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

QRコード生成
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

出席登録
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

出席履歴確認
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


状態遷移図
学生セッション
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

QRコード
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

出席記録
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

出席
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
