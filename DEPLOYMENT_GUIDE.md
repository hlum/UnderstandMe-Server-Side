# Understand Me API - Deployment Guide

## 概要

このガイドでは、新しい自動デプロイスクリプトを使用してUnderstand Me APIをセットアップおよびデプロイする方法を説明します。

## 重要な新機能

このデプロイスクリプトは、**既存データの保護**を最優先に設計されています:

### 既存環境の自動検出

スクリプトは以下を自動的に検出します:
- 既存のMySQLデータボリューム（すべてのデータベースデータ）
- 既存のMySQLコンテナ
- 既存の環境設定ファイル (`.env`)

### 2つの選択肢

既存環境が検出された場合、以下の選択肢が提示されます:

#### [1] 既存のデータを保持する（推奨）
- ✅ 現在のデータベースとユーザーアカウントをそのまま使用
- ✅ データは削除されません
- ✅ 既存の認証情報を自動的に使用
- ✅ `config.php`は既存の`.env`から自動生成

#### [2] 既存のデータを削除して新規セットアップ
- ⚠️ すべてのデータベースデータが削除されます
- ⚠️ 新しい認証情報を設定
- ⚠️ この操作は元に戻せません
- ✅ 古いファイルは自動的にバックアップされます（タイムスタンプ付き）

## 前提条件

- Git
- Docker & Docker Compose
- Composer (オプション、Dockerコンテナ内でも実行可能)

## クイックスタート

### 1. リポジトリのクローン

```bash
git clone <repository-url>
cd sotsusei
```

### 2. Firebase Service Accountファイルの配置

デプロイスクリプトを実行する前に、Firebase Service Accountファイルを準備してください。

#### Firebase Service Accountファイルの取得方法:

1. [Firebase Console](https://console.firebase.google.com/)にアクセス
2. プロジェクトを選択
3. プロジェクト設定（⚙️アイコン）→「サービスアカウント」タブ
4. 「新しい秘密鍵の生成」をクリック
5. ダウンロードしたJSONファイルを `config/service-account.json` として保存

```bash
# 例
mkdir -p config
mv ~/Downloads/understand-me-xxxxx.json ./config/service-account.json
```

⚠️ **セキュリティ警告**: このファイルには機密情報が含まれます。公開リポジトリにコミットしないでください。

### 3. デプロイスクリプトの実行

```bash
./deploy.sh
```

## デプロイフロー

### 初回デプロイ（既存環境なし）

スクリプトは以下の処理を自動的に行います:

1. ✅ 依存関係のチェック（Docker、Git、Composer）
2. ✅ `.gitignore`の設定確認
3. ✅ **既存データの確認**（見つからない場合は新規セットアップ）
4. ✅ `.env`ファイルのセットアップ（データベース認証情報）
5. ✅ `config/config.php`の自動生成
6. ✅ Firebase Service Accountファイルの検証
7. ✅ Composer依存関係のインストール
8. ✅ Dockerコンテナのビルドと起動
9. ✅ デプロイの検証（MySQL接続確認）

### 2回目以降のデプロイ（既存環境あり）

スクリプトは既存環境を検出し、選択肢を提示します:

#### 選択肢1を選んだ場合（既存データを保持）
1. ✅ 既存の`.env`ファイルを読み込み
2. ✅ 既存の`config.php`を使用（または`.env`から再生成）
3. ✅ **MySQLボリュームは削除されません**
4. ✅ コンテナを再起動（データ保持）
5. ✅ 既存の認証情報でデータベース接続確認

#### 選択肢2を選んだ場合（既存データを削除）
1. ⚠️ **確認メッセージ**: "本当に削除してもよろしいですか？ (yes/no)"
2. ✅ コンテナを停止・削除
3. ✅ MySQLデータボリュームを削除
4. ✅ `.env`をバックアップして削除（例: `.env.backup.20260127_152030`）
5. ✅ `config.php`をバックアップして削除
6. ✅ 新しい認証情報を入力
7. ✅ 新しい`.env`と`config.php`を作成
8. ✅ すべてを再ビルド

## セットアップ中に入力が必要な情報

### 新規セットアップの場合

スクリプト実行中に、以下の情報の入力を求められます:

#### データベース設定
- **MYSQL_ROOT_PASSWORD**: MySQLのrootパスワード（2回入力で確認）
- **MYSQL_DATABASE**: データベース名（デフォルト: `understand_me`）
- **MYSQL_USER**: データベースユーザー名（デフォルト: `user`）
- **MYSQL_PASSWORD**: データベースユーザーのパスワード（2回入力で確認）

#### アプリケーション設定
- **OLLAMA_ENDPOINT**: OllamaのAPIエンドポイント
  - 例: `http://ollama.hlumaungphyo.site/api/generate`
- **FIREBASE_PROJECT_ID**: FirebaseプロジェクトID
  - 例: `understand-me-675da`

### 既存データを保持する場合

入力は不要です。既存の設定が自動的に使用されます。

## ファイル構成

デプロイ後のファイル構成:

```
sotsusei/
├── deploy.sh                          # デプロイスクリプト
├── .env                               # 環境変数（自動生成、gitignore済み）
├── .env.example                       # 環境変数のテンプレート
├── .env.backup.YYYYMMDD_HHMMSS       # .envのバックアップ（削除時に作成）
├── docker-compose.yml                 # Docker Compose設定（環境変数を使用）
├── config/
│   ├── config.php                    # アプリケーション設定（自動生成、gitignore済み）
│   ├── config.php.example            # 設定ファイルのテンプレート
│   ├── config.php.backup.YYYYMMDD_HHMMSS  # config.phpのバックアップ
│   └── service-account.json          # Firebase認証情報（手動配置、gitignore済み）
└── ...
```

## トラブルシューティング

### エラー: "Dockerがインストールされていません"

**解決方法**: Dockerをインストールしてください

- **macOS**: [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop/)
- **Ubuntu/Debian**: `sudo apt-get install docker.io docker-compose`
- **RHEL/CentOS**: `sudo yum install docker docker-compose`

デプロイスクリプトは、Dockerが見つからない場合、自動的にインストールを提案します。

### エラー: "Dockerデーモンが起動していません"

**解決方法**:

```bash
# Linux
sudo systemctl start docker

# macOS
Docker Desktopアプリを起動
```

### エラー: "Firebase Service Accountファイルが見つかりません"

**解決方法**:

1. Firebaseコンソールから秘密鍵をダウンロード
2. `config/service-account.json`として保存
3. デプロイスクリプトを再実行

### エラー: "Service AccountファイルのJSON形式が不正です"

**解決方法**:

1. `config/service-account.json`をテキストエディタで開く
2. 有効なJSON形式になっているか確認
3. 必要に応じて、Firebaseコンソールから再ダウンロード

### エラー: "MySQLへの接続が確認できません"

**原因**: `.env`と`config.php`の認証情報が不一致、またはMySQLボリュームに古い認証情報が残っている

**解決方法**:

```bash
# 既存データを削除して再セットアップ
./deploy.sh
# → 選択肢2を選択（既存データを削除）
```

### 既存の設定を変更したい

#### データベース認証情報の変更

**方法1**: デプロイスクリプトを使用（推奨）

```bash
./deploy.sh
# → 選択肢2を選択（既存データを削除）
# → 新しい認証情報を入力
```

**方法2**: 手動で変更（データ保持）

```bash
# 1. .envファイルを編集
vim .env

# 2. config.phpを削除（または手動で編集）
rm config/config.php

# 3. デプロイスクリプトを実行
./deploy.sh
# → 選択肢1を選択（既存データを保持）
# config.phpが.envから自動生成されます
```

#### Firebase設定の変更

```bash
# 1. config.phpを編集
vim config/config.php

# 2. 必要に応じてservice-account.jsonを置き換え
mv ~/Downloads/new-service-account.json config/service-account.json

# 3. コンテナを再起動
docker-compose restart
```

## デプロイ後の操作

### APIの確認

```bash
curl http://localhost:8080
```

### ログの確認

```bash
# すべてのサービスのログ
docker-compose logs -f

# 特定のサービスのログ
docker-compose logs -f php-apache
docker-compose logs -f mysql
```

### コンテナの状態確認

```bash
docker-compose ps
```

### データベースへの接続

```bash
# .envから認証情報を確認
cat .env

# データベースに接続
mysql -h 127.0.0.1 -P 13306 -u <MYSQL_USER> -p
# パスワードを入力: <MYSQL_PASSWORD>
```

### コンテナの再起動

```bash
# すべてのコンテナを再起動
docker-compose restart

# 特定のコンテナを再起動
docker-compose restart php-apache
```

### コンテナの停止と削除

```bash
# コンテナを停止・削除（ボリュームは保持）
docker-compose down

# ボリュームも含めて完全削除
docker-compose down -v
```

### コンテナの完全な再ビルド

```bash
# データを保持したまま再ビルド
./deploy.sh
# → 選択肢1を選択

# データを削除して再ビルド
./deploy.sh
# → 選択肢2を選択
```

## セキュリティのベストプラクティス

### 1. 機密ファイルの保護

以下のファイルは`.gitignore`に含まれており、Gitにコミットされません:

- `.env` - データベース認証情報
- `config/` - アプリケーション設定とFirebase認証情報

デプロイスクリプトは自動的に`.gitignore`を確認し、必要に応じて追加します。

### 2. バックアップファイルの管理

既存データを削除する場合、以下のファイルが自動的にバックアップされます:

```
.env.backup.20260127_152030
config/config.php.backup.20260127_152030
```

これらのバックアップファイルも機密情報を含むため、注意して扱ってください。

### 3. 本番環境でのパスワード

本番環境では必ず強力なパスワードを使用してください:

- 最低12文字以上
- 大文字、小文字、数字、記号を含む
- 辞書に載っている単語を避ける
- パスワードマネージャーの使用を推奨

### 4. ポートの公開

本番環境では、必要なポートのみを公開してください:

- APIポート: 8080 → 通常は必要
- MySQLポート: 13306 → 外部アクセスが不要な場合は削除

`docker-compose.yml`で不要なポートマッピングを削除:

```yaml
# MySQLを外部に公開しない場合
mysql:
  # ports:
  #   - "13306:3306"  # この行をコメントアウト
```

## サポート

問題が発生した場合は、以下を確認してください:

1. `docker-compose logs -f`でログを確認
2. `.env`ファイルの設定が正しいか確認
3. `config.php`の認証情報が`.env`と一致しているか確認
4. `config/service-account.json`が存在し、有効なJSON形式か確認
5. Dockerデーモンが起動しているか確認
6. デプロイスクリプトの検証結果を確認

## 更新とメンテナンス

### コードの更新（データ保持）

```bash
git pull
./deploy.sh
# → 選択肢1を選択（既存のデータを保持）
```

デプロイスクリプトが自動的に:
- 最新のコードを取得
- コンテナを再ビルド（必要に応じて）
- 既存データを保持したまま新しいコンテナを起動

### データベースのバックアップ

```bash
# バックアップファイル名を指定
BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"

# データベースをダンプ
docker exec mysql_understand_me mysqldump -u root -p understand_me > $BACKUP_FILE

# 圧縮（オプション）
gzip $BACKUP_FILE
```

### データベースのリストア

```bash
# バックアップから復元
docker exec -i mysql_understand_me mysql -u root -p understand_me < backup.sql

# 圧縮ファイルから復元
gunzip < backup.sql.gz | docker exec -i mysql_understand_me mysql -u root -p understand_me
```

## よくある質問

### Q: 既存データを削除せずに認証情報を変更できますか？

A: いいえ、できません。MySQLの認証情報はデータボリュームの初回作成時に設定され、後から変更することはできません。認証情報を変更するには、データボリュームを削除して再作成する必要があります。

### Q: バックアップファイルはいつまで保存されますか？

A: デプロイスクリプトは自動的にバックアップを削除しません。手動で削除するか、定期的にクリーンアップしてください。

### Q: 本番環境へのデプロイはどうすればよいですか？

A: 本番環境では以下を推奨します:

1. 強力なパスワードを使用
2. 不要なポートマッピングを削除
3. SSL/TLS証明書の設定（リバースプロキシ経由）
4. 定期的なバックアップの自動化
5. ログのモニタリング設定

### Q: デプロイスクリプトが途中で失敗した場合は？

A: デプロイスクリプトは`set -e`を使用しており、エラーが発生すると即座に終了します。エラーメッセージを確認し、問題を解決してから再実行してください。バックアップファイルがある場合は、それを使用して復元できます。
