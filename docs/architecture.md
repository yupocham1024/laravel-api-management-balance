# Laravel アーキテクチャ全体図

---

## 1. リクエストが届いてからレスポンスが返るまで

```mermaid
flowchart TD
    Client([クライアント])
    Client -->|HTTP Request| Route

    subgraph Laravel
        Route[routes/api.php\nURLとControllerの対応表]
        MW[Middleware\nauth:sanctum\n認証チェック]
        Controller[Controller\nリクエストの司令塔]
        Policy[AccountPolicy\n認可チェック\n自分の口座か？]
        Validate[バリデーション\nrequest->validate]
        Model[Model\nEloquent ORM]
    end

    DB[(MySQL)]

    Route -->|対応するControllerへ| MW
    MW -->|トークンが無効| Client
    MW -->|認証OK| Controller
    Controller -->|authorize| Policy
    Policy -->|他人の口座| Client
    Policy -->|認可OK| Controller
    Controller -->|入力値チェック| Validate
    Validate -->|バリデーション失敗| Client
    Validate -->|OK| Model
    Model <-->|SQL| DB
    Model -->|データ| Controller
    Controller -->|JSON Response| Client

    style MW fill:#f9c74f
    style Policy fill:#f9c74f
    style Validate fill:#f9c74f
```

黄色はリクエストが「弾かれる可能性のある関所」。

| 関所 | 弾かれたときのステータス | 担当 |
|------|------|------|
| Middleware | 401 Unauthorized | Sanctum |
| Policy | 403 Forbidden | AccountPolicy |
| バリデーション | 422 Unprocessable | Controller内の validate() |

---

## 2. クラスの依存関係

```mermaid
classDiagram
    class AccountController {
        +store(Request)
        +balance(Account)
        +deposit(Request, Account)
        +withdraw(Request, Account)
        +transactions(Account)
    }

    class AccountPolicy {
        +access(User, Account) bool
    }

    class Account {
        #fillable: user_id, branch_id, balance
        #casts: balance→decimal
        +user() BelongsTo
        +branch() BelongsTo
        +transactions() HasMany
    }

    class User {
        #fillable: name, email ...
        +accounts() HasMany
    }

    class Branch {
        #fillable: name, prefecture
        +accounts() HasMany
    }

    class Transaction {
        #fillable: account_id, type, amount
        +account() BelongsTo
    }

    AccountController --> AccountPolicy : authorize('access', account)
    AccountController --> Account : create / find / increment / decrement

    Account --> User : belongsTo（口座の所有者）
    Account --> Branch : belongsTo（管理支店）
    Account --> Transaction : hasMany（取引履歴）
    User --> Account : hasMany
    Branch --> Account : hasMany
    Transaction --> Account : belongsTo
```

---

## 3. 認証（ログイン〜APIアクセス）の流れ

```mermaid
sequenceDiagram
    participant C as クライアント
    participant AC as AuthController
    participant Auth as Auth::attempt()
    participant DB as MySQL

    Note over C, DB: ログイン

    C->>AC: POST /login { email, password }
    AC->>Auth: attempt(credentials)
    Auth->>DB: SELECT * FROM users WHERE email=...
    DB-->>Auth: User レコード
    Auth-->>AC: 一致 true / 不一致 false
    AC->>DB: INSERT INTO personal_access_tokens
    DB-->>AC: トークン文字列
    AC-->>C: { token: "1|xxxxxxxxxxxx" }

    Note over C, DB: 認証が必要なAPIへのアクセス

    C->>DB: Authorization: Bearer 1|xxxxxxxxxxxx
    Note right of DB: auth:sanctum Middleware が\npersonal_access_tokens を検索
    DB-->>C: 見つからない → 401
    DB-->>AC: 見つかった → Controller へ通す
    AC-->>C: JSON Response
```

---

## 4. テストの構造

```mermaid
flowchart LR
    subgraph テストコード
        Test[Feature Test\nAccountTest.php]
        RD[RefreshDatabase\nテストごとにDBをリセット]
        AS[actingAs user\n認証済み状態を再現]
        FC[Factory\nUser / Branch / Account]
    end

    subgraph Laravelアプリ本体
        App[HTTP Layer\nroutes → middleware → controller]
        DB2[(Test DB)]
    end

    subgraph 検証
        Assert[アサーション\nassertStatus\nassertJsonFragment\nassertJsonCount ...]
    end

    Test -- use --> RD
    Test -- use --> AS
    Test -- create --> FC
    FC -->|INSERT| DB2
    Test -->|postJson / getJson| App
    App <-->|SQL| DB2
    App -->|Response| Assert
    Assert -->|PASS / FAIL| Test
```

---

## 5. ファイル構成と役割の対応

```mermaid
flowchart LR
    subgraph routes
        R[api.php\nURL定義・Middleware設定]
    end

    subgraph app/Http/Controllers
        CC[AccountController\nBranchController\nUserController\nAuthController]
    end

    subgraph app/Policies
        P[AccountPolicy\n認可ロジック]
    end

    subgraph app/Models
        M[Account\nUser\nBranch\nTransaction]
    end

    subgraph database
        MG[migrations/\nテーブル定義]
        FA[factories/\nテストデータ生成]
    end

    subgraph tests/Feature
        T[AccountTest\nBranchTest\nUserTest]
    end

    R -->|振り分け| CC
    CC -->|認可チェック| P
    CC -->|DB操作| M
    M -->|テーブル定義参照| MG
    T -->|テストデータ| FA
    T -->|HTTPシミュレート| R
```

---

## 6. 一言まとめ

| コンポーネント | 一言で言うと |
|---|---|
| `routes/api.php` | URL と Controller の対応表。Middleware もここで設定 |
| Middleware (`auth:sanctum`) | Controller の手前にある「認証の関所」 |
| Controller | リクエストを受け取り、Policy・Model・レスポンスを調整する司令塔 |
| Policy | 「誰が何をしていいか」のルールブック |
| Model | DB のテーブルを PHP オブジェクトとして扱う窓口。リレーションも定義 |
| Migration | テーブルの設計図（PHP で書いた CREATE TABLE） |
| Factory | テスト用ダミーデータの生成器 |
| Feature Test | HTTP リクエストをシミュレートして API の動作を検証する |
| `RefreshDatabase` | テストごとに DB をリセットするトレイト |
| `actingAs()` | テスト内で「このユーザーでログイン済み」状態を作るメソッド |
