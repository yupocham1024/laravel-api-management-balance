# Laravel 開発ガイド

## 目次

1. [ローカル環境の構成](#1-ローカル環境の構成)
2. [Laravel の構成要素](#2-laravel-の構成要素)
3. [Eloquent ORM](#3-eloquent-orm)

---

## 1. ローカル環境の構成

### 登場人物の役割

```
┌─────────────────────────────────────────┐
│           Docker Desktop                │
│  （Docker エンジンを動かすアプリ）         │
│                                         │
│  ┌─────────────────────────────────┐    │
│  │       Docker Engine             │    │
│  │  （コンテナを実際に動かす核心部分）│    │
│  │                                 │    │
│  │  ┌──────────────┐  ┌─────────┐ │    │
│  │  │laravel.test  │  │  mysql  │ │    │
│  │  │（PHP + Nginx）│  │（MySQL）│ │    │
│  │  └──────────────┘  └─────────┘ │    │
│  └─────────────────────────────────┘    │
└─────────────────────────────────────────┘

./vendor/bin/sail → Docker Engine への命令をラップしたコマンド
compose.yaml     → 「どんなコンテナを作るか」の設計書
```

| 名前 | 役割 |
|---|---|
| Docker Desktop | Mac 上で Docker Engine を動かすアプリ。これが起動していないと何も動かない |
| Docker Engine | コンテナを実際に作成・起動・管理する核心部分 |
| compose.yaml | どんなコンテナをいくつ立ち上げるかの設計書 |
| Sail | `docker compose` コマンドを Laravel 向けにラップしたツール |

### 環境が使用可能になるまでのチェックリスト

トラブル時はこの順番に上から確認する。

```
① Docker Desktop が起動している
        ↓ 確認: メニューバーにクジラアイコンが静止している

② Docker Engine が動いている
        ↓ 確認: docker ps がエラーなく返ってくる

③ コンテナが起動している
        ↓ 確認: docker ps に laravel.test と mysql が Up で表示される
        （./vendor/bin/sail up -d で起動）

④ MySQL が初期化済みである
        ↓ 確認: sail artisan migrate が成功している

⑤ この状態で http://localhost へのリクエストが通る
```

### よく使うコマンド

| コマンド | 説明 |
|---|---|
| `./vendor/bin/sail up -d` | コンテナをバックグラウンドで起動 |
| `./vendor/bin/sail stop` | コンテナを停止 |
| `./vendor/bin/sail down` | コンテナを停止して削除 |
| `./vendor/bin/sail down -v` | コンテナ・ボリューム（DBデータ）も削除 |
| `./vendor/bin/sail artisan migrate` | マイグレーション実行 |
| `./vendor/bin/sail artisan migrate:status` | マイグレーションの実行状態を確認 |
| `./vendor/bin/sail artisan migrate:rollback` | 直前のマイグレーションを元に戻す |
| `./vendor/bin/sail artisan tinker` | 対話的に PHP / Eloquent を試す REPL |
| `./vendor/bin/sail shell` | コンテナ内に bash で入る |
| `docker ps` | 起動中のコンテナ一覧を表示 |

### docker ps の見方

```
NAMES                    STATUS         PORTS
laravel-mysql-1          Up 2 hours     0.0.0.0:3307->3306/tcp
```

- **STATUS**: `Up` = 起動中、`Exited` = 停止済み、`Restarting` = 再起動を繰り返している（エラーあり）
- **PORTS**: `ホスト側のポート -> コンテナ側のポート` という読み方をする。MySQL はコンテナ内では 3306 で動いているが、外から接続するときは 3307 を使う

---

## 2. Laravel の構成要素

### 全体の流れ

```
リクエスト
    ↓
public/index.php（すべてのリクエストが最初に届く唯一の入口）
    ↓
bootstrap/app.php（アプリの起動設定・ルートファイルの登録）
    ↓
routes/api.php（URLとControllerの対応を定義）
    ↓
Controller（リクエストを受け取り処理する）
    ↓
Model（DBを操作する）
    ↓
JSONレスポンスを返す
```

### Migration

テーブルの設計図を PHP で書いたもの。SQL の `CREATE TABLE` を直接叩く代わりに使う。

```php
return new class extends Migration
{
    public function up(): void   // migrate 実行時に呼ばれる（テーブル作成）
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();                          // 自動連番の主キー
            $table->decimal('balance', 15, 2)      // 最大15桁・小数点2桁
                  ->default(0);
            $table->timestamps();                  // created_at / updated_at を自動追加
        });
    }

    public function down(): void  // migrate:rollback 実行時に呼ばれる（テーブル削除）
    {
        Schema::dropIfExists('accounts');
    }
};
```

**`Schema::create` と `Schema::table` の違い**

| メソッド | 用途 |
|---|---|
| `Schema::create()` | テーブルを新規作成する |
| `Schema::table()` | 既存テーブルにカラムを追加・変更する |

**Migration ファイルを編集してはいけない理由**

Laravel は `migrations` テーブルに「どのファイルを実行済みか」を記録している。一度実行されたファイルは二度と実行されないため、既存ファイルを書き換えても他の環境には反映されない。変更は必ず新しいファイルとして追加する。

**外部キー制約**

```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
```

- `foreignId('user_id')`: `user_id` カラムを作成
- `constrained()`: カラム名からテーブル名（`users`）を自動推測して外部キー制約を設定
- `cascadeOnDelete()`: 親レコードが削除されたとき子レコードも連動して削除

`ON DELETE` の選択肢:

| オプション | 挙動 |
|---|---|
| `CASCADE` | 親が消えたら子も消す |
| `RESTRICT` | 子が存在する限り親を削除できない |
| `SET NULL` | 親が消えたら子の外部キーを NULL にする |
| `NO ACTION` | 何もしない（実質 RESTRICT と同じ） |

外部キー制約の検知と実行は MySQL が担う。Laravel は「制約を DB に登録する」ところまでが仕事で、実際の検知と削除は DB が行う。

### Model

DB のテーブルと 1 対 1 で対応する PHP クラス。`Model` を継承するだけで SQL を書かずにレコードの作成・取得・更新・削除ができる。

```php
class Account extends Model
{
    protected $fillable = ['user_id', 'branch_id', 'balance'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];
}
```

**`$fillable`**

外から一括セットできるカラムのホワイトリスト。セキュリティ対策（Mass Assignment 対策）として必要。

**`$casts`**

DB と PHP の間でデータを変換する設定。双方向に機能する。

```
DB → PHP（読み取り時）: "5000.00"（文字列）→ 5000.00（decimal）
PHP → DB（書き込み時）: 5000.00（decimal）→ 適切な形式に変換して保存
```

よく使うキャストの例:

| 型 | 用途 |
|---|---|
| `decimal:2` | 小数点2桁の数値 |
| `boolean` | `1` / `0` ↔ `true` / `false` |
| `datetime` | 文字列 ↔ Carbon オブジェクト |
| `hashed` | 書き込み時に自動でハッシュ化（パスワード用） |
| `json` | 文字列 ↔ PHP の配列 |

**`$timestamps = false`**

Migration で `timestamps()` を定義しなかったテーブルには必ず設定する。これがないと Eloquent がレコード作成時に存在しないカラムへ書き込もうとしてエラーになる。

### Route

「どの URL に来たリクエストを、どの Controller のどのメソッドに渡すか」を定義する地図。

```php
// routes/api.php
Route::post('/accounts', [AccountController::class, 'store']);
Route::get('/accounts/{account}/balance', [AccountController::class, 'balance']);
```

- `{account}`: URL の動的な部分。Controller 側の引数名と一致させることでルートモデルバインディングが機能する
- Laravel 11 では `routes/api.php` はデフォルトで存在しないため、`bootstrap/app.php` に明示的に登録する必要がある

```php
// bootstrap/app.php
->withRouting(
    api: __DIR__.'/../routes/api.php',
)
```

### Controller

リクエストを受け取って処理する司令塔。バリデーション・Model への操作・レスポンスの返却を担う。

```php
public function deposit(Request $request, Account $account): JsonResponse
{
    $data = $request->validate([
        'amount' => ['required', 'numeric', 'min:0.01'],
    ]);
    // バリデーション失敗時は自動で 422 エラーを返す

    DB::transaction(function () use ($account, $data) {
        $account->increment('balance', $data['amount']);
        $account->transactions()->create([...]);
    });
    // どちらかが失敗したら両方なかったことにする（原子性）

    return response()->json([...]);
}
```

**ルートモデルバインディング**

`{account}` という URL パラメータに対して `Account $account` と型を書くだけで、Laravel が自動的に `Account::find(id)` を実行して渡してくれる機能。ID が存在しなければ自動で 404 を返す。

---

## 3. Eloquent ORM

### ORM とは

「DB のテーブルを PHP のオブジェクトとして扱えるようにする仕組み」。Eloquent は Laravel における ORM の実装名。

```
accountsテーブルの1行  ←→  Account クラスの1インスタンス
カラム名               ←→  オブジェクトのプロパティ
```

```php
// SQL で書く場合
SELECT * FROM accounts WHERE id = 1;

// Eloquent で書く場合
Account::find(1);
```

### リレーション

テーブル同士のつながりを PHP のオブジェクトの世界に写し取ったもの。DB 上の外部キーの関係を Laravel に教えることで、関連データをオブジェクトとしてたどれるようになる。

| メソッド | 関係 |
|---|---|
| `hasOne` | 1対1 |
| `hasMany` | 1対多 |
| `belongsTo` | 多対1（hasMany の逆方向） |
| `belongsToMany` | 多対多 |

`hasMany` と `belongsTo` は常にセット。

```php
// Branch 側
public function accounts(): HasMany
{
    return $this->hasMany(Account::class);
}

// Account 側
public function branch(): BelongsTo
{
    return $this->belongsTo(Branch::class);
}

// 双方向からたどれる
$branch->accounts; // 支店から口座一覧へ
$account->branch;  // 口座から支店へ
```

**リレーションを定義することで使えるようになること**

```php
// 関連データをシンプルにたどれる
$branch->accounts;

// 条件を追加できる
$branch->accounts()->where('balance', '>', 10000)->get();

// ネストした関連データを一度に取得できる
Branch::with('accounts.transactions')->find(1);
```

**N+1 問題と Eager Loading**

```php
// N+1 問題（支店が100件あれば101回クエリが走る）
$branches = Branch::all();
foreach ($branches as $branch) {
    $branch->accounts; // ループのたびに SQL が発行される
}

// with() で解決（2回のクエリで済む）
$branches = Branch::with('accounts')->get();
```

**`HasMany` オブジェクトについて**

`hasMany()` は SQL を即座に実行するのではなく、「どう取得するか」という設定を持ったオブジェクト（`HasMany`）を返す。実際に SQL が実行されるのは `get()` や `first()` を呼んだとき。

```php
$branch->accounts();        // HasMany オブジェクトを返すだけ（SQL未実行）
$branch->accounts()->get(); // ここで初めて SELECT が走る
$branch->accounts;          // () なしでアクセスすると自動で get() される
```

### トレイト

クラスに機能を追加する部品。PHP には多重継承がないため、複数のクラスからメソッドを取り込みたいときに使われる。

```php
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    // HasFactory と Notifiable のメソッドが User クラスに直接生える
}
```

合成（Composition）との違い:

```php
// 合成: オブジェクトを「持つ」
class User {
    private Notifier $notifier;
}

// トレイト: メソッドを「もらう」
class User {
    use Notifiable;
}
```

### オートローダー

クラスが初めて使われた瞬間に、対応するファイルを自動で読み込む仕組み。Composer が `vendor/autoload.php` を生成することで実現している。これにより `require` や `include` を手動で書かなくてもクラスが使えるようになる。
