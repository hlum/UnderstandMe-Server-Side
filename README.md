# KnowYourCode API - Deployment Guide

## 概要

このガイドでは、新しい自動デプロイスクリプトを使用してUnderstand Me APIをセットアップおよびデプロイする方法を説明します。

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
mv ~/Downloads/understand-me-xxxxx.json ./config/service-account.json
```

⚠️ **セキュリティ警告**: このファイルには機密情報が含まれます。公開リポジトリにコミットしないでください。

### 3. デプロイスクリプトの実行

```bash
./deploy.sh
```

デプロイスクリプトは以下の処理を自動的に行います:

1. ✅ 依存関係のチェック（Docker、Git、Composer）
2. ✅ `.env`ファイルのセットアップ（データベース認証情報）
3. ✅ `config/config.php`の生成
4. ✅ Firebase Service Accountファイルの検証
5. ✅ Composer依存関係のインストール
6. ✅ Dockerコンテナのビルドと起動

### 4. セットアップ中に入力が必要な情報

スクリプト実行中に、以下の情報の入力を求められます:

#### データベース設定

- **MYSQL_ROOT_PASSWORD**: MySQLのrootパスワード
- **MYSQL_DATABASE**: データベース名（デフォルト: `understand_me`）
- **MYSQL_USER**: データベースユーザー名（デフォルト: `user`）
- **MYSQL_PASSWORD**: データベースユーザーのパスワード

#### アプリケーション設定

- **OLLAMA_ENDPOINT**: OllamaのAPIエンドポイント
    - 例: `http://ollama.hlumaungphyo.site/api/generate`
- **FIREBASE_PROJECT_ID**: FirebaseプロジェクトID
    - 例: `understand-me-675da`

## ファイル構成

デプロイ後のファイル構成:

```
sotsusei/
├── deploy.sh                    # デプロイスクリプト
├── .env                         # 環境変数（自動生成、gitignore済み）
├── .env.example                 # 環境変数のテンプレート
├── docker-compose.yml           # Docker Compose設定
├── config/
│   ├── config.php              # アプリケーション設定（自動生成、gitignore済み）
│   ├── config.php.example      # 設定ファイルのテンプレート
│   └── service-account.json    # Firebase認証情報（手動配置、gitignore済み）
└── ...
```

## トラブルシューティング

### エラー: "Dockerがインストールされていません"

**解決方法**: Dockerをインストールしてください

- **macOS**: [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop/)
- **Ubuntu/Debian**: `sudo apt-get install docker.io docker-compose`
- **RHEL/CentOS**: `sudo yum install docker docker-compose`

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

### 既存の設定を変更したい

#### データベース認証情報の変更

1. `.env`ファイルを編集
2. `config/config.php`を削除（または編集）
3. `./deploy.sh`を再実行

または、直接`.env`を編集して`docker-compose up -d`を実行:

```bash
vim .env
docker-compose down
docker-compose up -d
```

#### Firebase設定の変更

1. `config/config.php`を編集
2. 必要に応じて`config/service-account.json`を置き換え
3. コンテナを再起動: `docker-compose restart`

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
docker-compose down
```

### コンテナの完全な再ビルド

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## セキュリティのベストプラクティス

### 1. 機密ファイルの保護

以下のファイルは`.gitignore`に含まれており、Gitにコミットされません:

- `.env` - データベース認証情報
- `config/` - アプリケーション設定とFirebase認証情報

### 2. 本番環境でのパスワード

本番環境では必ず強力なパスワードを使用してください:

- 最低12文字以上
- 大文字、小文字、数字、記号を含む
- 辞書に載っている単語を避ける

### 3. ポートの公開

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
3. `config/service-account.json`が存在し、有効なJSON形式か確認
4. Dockerデーモンが起動しているか確認

## 更新とメンテナンス

### コードの更新

```bash
git pull
./deploy.sh
```

デプロイスクリプトが自動的に:

- 最新のコードを取得
- コンテナを再ビルド
- 新しいコンテナを起動

### データベースのバックアップ

```bash
docker exec mysql_understand_me mysqldump -u root -p understand_me > backup.sql
```

### データベースのリストア

```bash
docker exec -i mysql_understand_me mysql -u root -p understand_me < backup.sql
```
