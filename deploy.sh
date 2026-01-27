#!/bin/bash

# Understand Me API デプロイスクリプト
# 依存関係のチェック、環境設定、Dockerでのデプロイを行います

set -e  # コマンドが失敗した場合は即座に終了

PROJECT_NAME="Understand Me API"
CONFIG_DIR="config"
CONFIG_FILE="$CONFIG_DIR/config.php"
CONFIG_EXAMPLE="$CONFIG_DIR/config.php.example"
ENV_FILE=".env"
ENV_EXAMPLE=".env.example"
FIREBASE_SERVICE_ACCOUNT="$CONFIG_DIR/service-account.json"
MYSQL_VOLUME_NAME="mysql_data"
MYSQL_CONTAINER_NAME="mysql_understand_me"

# グローバル変数：既存データを保持するかどうか
KEEP_EXISTING_DATA=false

# 出力用の色
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_step() {
    echo -e "${CYAN}▶️  $1${NC}"
}

print_important() {
    echo -e "${MAGENTA}⚡ $1${NC}"
}

# OSを検出
detect_os() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        if [ -f /etc/debian_version ]; then
            echo "debian"
        elif [ -f /etc/redhat-release ]; then
            echo "redhat"
        else
            echo "linux"
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    else
        echo "unknown"
    fi
}

# コマンドが存在するか確認
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Docker Composeコマンドを取得
get_compose_command() {
    if command_exists docker-compose; then
        echo "docker-compose"
    else
        echo "docker compose"
    fi
}

# 既存のMySQLボリュームが存在するか確認
check_existing_mysql_volume() {
    if docker volume inspect "$MYSQL_VOLUME_NAME" >/dev/null 2>&1; then
        return 0  # ボリュームが存在する
    else
        return 1  # ボリュームが存在しない
    fi
}

# 既存のMySQLコンテナが存在するか確認
check_existing_mysql_container() {
    if docker ps -a --format '{{.Names}}' | grep -q "^${MYSQL_CONTAINER_NAME}$"; then
        return 0  # コンテナが存在する
    else
        return 1  # コンテナが存在しない
    fi
}

# 既存の.envファイルから認証情報を読み込む
read_existing_env_credentials() {
    if [ -f "$ENV_FILE" ]; then
        source "$ENV_FILE"

        # 必要な変数がすべて存在するか確認
        if [ -n "$MYSQL_ROOT_PASSWORD" ] && [ -n "$MYSQL_DATABASE" ] && \
           [ -n "$MYSQL_USER" ] && [ -n "$MYSQL_PASSWORD" ] && \
           [ -n "$OLLAMA_ENDPOINT" ] && [ -n "$FIREBASE_PROJECT_ID" ]; then
            return 0  # すべての変数が存在
        fi
    fi
    return 1  # 変数が不足している
}

# 既存データの処理を決定
handle_existing_data() {
    print_header "既存データの確認"

    local has_volume=false
    local has_container=false
    local has_env=false

    # 既存のボリュームを確認
    if check_existing_mysql_volume; then
        has_volume=true
        print_warning "既存のMySQLデータボリュームが見つかりました: $MYSQL_VOLUME_NAME"
    fi

    # 既存のコンテナを確認
    if check_existing_mysql_container; then
        has_container=true
        print_warning "既存のMySQLコンテナが見つかりました: $MYSQL_CONTAINER_NAME"
    fi

    # 既存の.envファイルを確認
    if read_existing_env_credentials; then
        has_env=true
        print_warning "既存の.envファイルが見つかりました"
    fi

    # どれか1つでも存在する場合、ユーザーに選択を促す
    if [ "$has_volume" = true ] || [ "$has_container" = true ] || [ "$has_env" = true ]; then
        echo ""
        print_important "既存のデータベース環境が検出されました！"
        echo ""
        echo -e "${YELLOW}以下の既存リソースが見つかりました:${NC}"
        [ "$has_volume" = true ] && echo "  • MySQLデータボリューム (データベースの全データ)"
        [ "$has_container" = true ] && echo "  • MySQLコンテナ"
        [ "$has_env" = true ] && echo "  • 環境設定ファイル (.env)"
        echo ""

        echo -e "${CYAN}選択肢:${NC}"
        echo -e "${GREEN}[1] 既存のデータを保持する（推奨）${NC}"
        echo "    • 現在のデータベースとユーザーアカウントをそのまま使用"
        echo "    • データは削除されません"
        echo "    • 既存の認証情報を使用"
        echo ""
        echo -e "${RED}[2] 既存のデータを削除して新規セットアップ${NC}"
        echo "    • すべてのデータベースデータが削除されます"
        echo "    • 新しい認証情報を設定"
        echo "    • ⚠️  この操作は元に戻せません！"
        echo ""

        while true; do
            read -p "選択してください (1/2): " choice
            case $choice in
                1)
                    KEEP_EXISTING_DATA=true
                    print_success "既存のデータを保持します"
                    break
                    ;;
                2)
                    echo ""
                    print_warning "⚠️  警告: この操作はすべてのデータベースデータを削除します！"
                    read -p "本当に削除してもよろしいですか？ (yes/no): " confirm
                    if [[ "$confirm" == "yes" ]]; then
                        KEEP_EXISTING_DATA=false
                        print_success "既存のデータを削除して新規セットアップを行います"

                        # 既存環境を削除
                        cleanup_existing_environment
                        break
                    else
                        print_info "削除がキャンセルされました。既存のデータを保持します。"
                        KEEP_EXISTING_DATA=true
                        break
                    fi
                    ;;
                *)
                    print_error "無効な選択です。1または2を入力してください。"
                    ;;
            esac
        done
    else
        print_success "既存データが見つかりません。新規セットアップを行います。"
        KEEP_EXISTING_DATA=false
    fi
}

# 既存環境をクリーンアップ
cleanup_existing_environment() {
    print_header "既存環境のクリーンアップ"

    local compose_cmd=$(get_compose_command)

    # コンテナを停止・削除
    if check_existing_mysql_container || docker ps -a --format '{{.Names}}' | grep -q "php_apache_understand_me\|sotsusei-cron"; then
        print_step "コンテナを停止・削除しています..."
        $compose_cmd down 2>/dev/null || true
        print_success "コンテナを削除しました"
    fi

    # ボリュームを削除
    if check_existing_mysql_volume; then
        print_step "MySQLデータボリュームを削除しています..."
        docker volume rm "$MYSQL_VOLUME_NAME" 2>/dev/null || true
        print_success "データボリュームを削除しました"
    fi

    # .envファイルをバックアップして削除
    if [ -f "$ENV_FILE" ]; then
        print_step ".envファイルをバックアップしています..."
        cp "$ENV_FILE" "${ENV_FILE}.backup.$(date +%Y%m%d_%H%M%S)"
        rm "$ENV_FILE"
        print_success ".envファイルをバックアップして削除しました"
    fi

    # config.phpファイルをバックアップして削除
    if [ -f "$CONFIG_FILE" ]; then
        print_step "config.phpファイルをバックアップしています..."
        cp "$CONFIG_FILE" "${CONFIG_FILE}.backup.$(date +%Y%m%d_%H%M%S)"
        rm "$CONFIG_FILE"
        print_success "config.phpファイルをバックアップして削除しました"
    fi

    echo ""
    print_success "クリーンアップが完了しました"
}

# OSに応じてDockerをインストール
install_docker() {
    local os=$(detect_os)

    print_header "Dockerのインストール"

    case $os in
        debian)
            print_info "Debian/Ubuntuシステムを検出しました"
            echo "以下のコマンドを実行します:"
            echo "  sudo apt-get update"
            echo "  sudo apt-get install -y docker.io docker-compose"
            echo "  sudo systemctl start docker"
            echo "  sudo systemctl enable docker"
            echo "  sudo usermod -aG docker \$USER"
            echo ""
            read -p "Dockerのインストールを続行しますか？ (y/n): " confirm
            if [[ $confirm == [yY] ]]; then
                sudo apt-get update
                sudo apt-get install -y docker.io docker-compose
                sudo systemctl start docker
                sudo systemctl enable docker
                sudo usermod -aG docker $USER
                print_success "Dockerのインストールが完了しました"
                print_warning "グループの変更を反映するには、ログアウトして再度ログインする必要があります"
                print_warning "または次のコマンドを実行: newgrp docker"
            else
                print_error "Dockerのインストールがキャンセルされました"
                exit 1
            fi
            ;;
        redhat)
            print_info "RHEL/CentOS/Fedoraシステムを検出しました"
            echo "以下のコマンドを実行します:"
            echo "  sudo yum install -y docker docker-compose"
            echo "  sudo systemctl start docker"
            echo "  sudo systemctl enable docker"
            echo "  sudo usermod -aG docker \$USER"
            echo ""
            read -p "Dockerのインストールを続行しますか？ (y/n): " confirm
            if [[ $confirm == [yY] ]]; then
                sudo yum install -y docker docker-compose
                sudo systemctl start docker
                sudo systemctl enable docker
                sudo usermod -aG docker $USER
                print_success "Dockerのインストールが完了しました"
                print_warning "グループの変更を反映するには、ログアウトして再度ログインする必要があります"
            else
                print_error "Dockerのインストールがキャンセルされました"
                exit 1
            fi
            ;;
        macos)
            print_info "macOSを検出しました"
            if command_exists brew; then
                echo "実行するコマンド: brew install --cask docker"
                read -p "HomebrewでDockerをインストールしますか？ (y/n): " confirm
                if [[ $confirm == [yY] ]]; then
                    brew install --cask docker
                    print_success "Dockerのインストールが完了しました"
                    print_warning "セットアップを完了するにはDocker Desktopを開いてください"
                else
                    print_error "Dockerのインストールがキャンセルされました"
                    exit 1
                fi
            else
                print_error "Homebrewが見つかりません。Docker Desktopを手動でインストールしてください:"
                echo "  https://www.docker.com/products/docker-desktop/"
                exit 1
            fi
            ;;
        *)
            print_error "不明なOSです。Dockerを手動でインストールしてください:"
            echo "  https://docs.docker.com/get-docker/"
            exit 1
            ;;
    esac
}

# 依存関係のチェックとインストール
check_dependencies() {
    print_header "依存関係のチェック"

    local missing_deps=0

    # Gitのチェック
    if command_exists git; then
        print_success "Gitがインストールされています ($(git --version | head -1))"
    else
        print_error "Gitがインストールされていません"
        missing_deps=1
    fi

    # Dockerのチェック
    if command_exists docker; then
        print_success "Dockerがインストールされています ($(docker --version))"

        # Dockerデーモンが起動しているか確認
        if docker info >/dev/null 2>&1; then
            print_success "Dockerデーモンが起動しています"
        else
            print_warning "Dockerデーモンが起動していません"
            print_info "Dockerの起動を試みます..."

            local os=$(detect_os)
            if [[ $os == "macos" ]]; then
                print_info "Docker Desktopを手動で起動してください"
                exit 1
            else
                sudo systemctl start docker 2>/dev/null || {
                    print_error "Dockerデーモンを起動できませんでした"
                    print_info "次のコマンドを試してください: sudo systemctl start docker"
                    exit 1
                }
                print_success "Dockerデーモンが起動しました"
            fi
        fi
    else
        print_warning "Dockerがインストールされていません"
        read -p "今すぐDockerをインストールしますか？ (y/n): " install_confirm
        if [[ $install_confirm == [yY] ]]; then
            install_docker
        else
            print_error "デプロイにはDockerが必要です"
            exit 1
        fi
    fi

    # Docker Composeのチェック
    if command_exists docker-compose || docker compose version >/dev/null 2>&1; then
        if command_exists docker-compose; then
            print_success "Docker Composeがインストールされています ($(docker-compose --version))"
        else
            print_success "Docker Composeがインストールされています (docker compose プラグイン)"
        fi
    else
        print_error "Docker Composeがインストールされていません"
        print_info "Docker Composeをインストールしてください: https://docs.docker.com/compose/install/"
        missing_deps=1
    fi

    # Composerのチェック（PHPの依存関係管理）
    if command_exists composer; then
        print_success "Composerがインストールされています ($(composer --version 2>/dev/null | head -1))"
    else
        print_warning "Composerがインストールされていません"
        print_info "Firebase Admin SDKを使用する場合、Composerが必要です"
        print_info "インストール手順: https://getcomposer.org/download/"
    fi

    if [ $missing_deps -eq 1 ]; then
        print_error "不足している依存関係をインストールしてから再度実行してください"
        exit 1
    fi
}

# .envファイルのセットアップ
setup_env_file() {
    print_header "環境変数の設定"

    # 既存データを保持する場合
    if [ "$KEEP_EXISTING_DATA" = true ]; then
        if [ -f "$ENV_FILE" ]; then
            print_success "既存の.envファイルを使用します"

            # .envファイルを読み込む
            source "$ENV_FILE"

            echo ""
            echo "現在の設定:"
            echo "  データベース名: $MYSQL_DATABASE"
            echo "  ユーザー名: $MYSQL_USER"
            echo "  Ollamaエンドポイント: $OLLAMA_ENDPOINT"
            echo "  Firebase Project ID: $FIREBASE_PROJECT_ID"
            echo ""

            return
        else
            print_error ".envファイルが見つかりません"
            print_info "新規セットアップモードに切り替えます"
            KEEP_EXISTING_DATA=false
        fi
    fi

    # 新規セットアップの場合
    if [ -f "$ENV_FILE" ]; then
        print_warning ".envファイルが既に存在します"
        read -p "既存の.envファイルを上書きしますか？ (y/n): " overwrite
        if [[ ! $overwrite == [yY] ]]; then
            print_info "既存の.envファイルを使用します"
            return
        fi
    fi

    print_info "データベースとアプリケーションの設定を入力してください"
    echo ""

    # MySQLルートパスワード
    echo -e "${CYAN}MySQLルートパスワードを入力してください:${NC}"
    read -s -p "MYSQL_ROOT_PASSWORD: " mysql_root_password
    echo ""

    # 確認入力
    read -s -p "MYSQL_ROOT_PASSWORD (確認): " mysql_root_password_confirm
    echo ""

    if [ "$mysql_root_password" != "$mysql_root_password_confirm" ]; then
        print_error "パスワードが一致しません。スクリプトを再実行してください。"
        exit 1
    fi

    # データベース名
    echo -e "${CYAN}データベース名を入力してください (デフォルト: understand_me):${NC}"
    read -p "MYSQL_DATABASE: " mysql_database
    mysql_database=${mysql_database:-understand_me}

    # データベースユーザー
    echo -e "${CYAN}データベースユーザー名を入力してください (デフォルト: user):${NC}"
    read -p "MYSQL_USER: " mysql_user
    mysql_user=${mysql_user:-user}

    # データベースパスワード
    echo -e "${CYAN}データベースパスワードを入力してください:${NC}"
    read -s -p "MYSQL_PASSWORD: " mysql_password
    echo ""

    # 確認入力
    read -s -p "MYSQL_PASSWORD (確認): " mysql_password_confirm
    echo ""

    if [ "$mysql_password" != "$mysql_password_confirm" ]; then
        print_error "パスワードが一致しません。スクリプトを再実行してください。"
        exit 1
    fi

    # Ollamaエンドポイント
    echo -e "${CYAN}OllamaエンドポイントのURLを入力してください:${NC}"
    echo -e "${YELLOW}例: http://ollama.example.com/api/generate${NC}"
    read -p "OLLAMA_ENDPOINT: " ollama_endpoint

    # Firebase Project ID
    echo -e "${CYAN}Firebase Project IDを入力してください:${NC}"
    read -p "FIREBASE_PROJECT_ID: " firebase_project_id

    # .envファイルを作成
    cat > "$ENV_FILE" << EOF
# MySQL Database Configuration
MYSQL_ROOT_PASSWORD=$mysql_root_password
MYSQL_DATABASE=$mysql_database
MYSQL_USER=$mysql_user
MYSQL_PASSWORD=$mysql_password

# Application Configuration
OLLAMA_ENDPOINT=$ollama_endpoint
FIREBASE_PROJECT_ID=$firebase_project_id
EOF

    print_success ".envファイルを作成しました"
}

# config.phpのセットアップ
setup_config_file() {
    print_header "config.php の設定"

    # configディレクトリが存在しない場合は作成
    if [ ! -d "$CONFIG_DIR" ]; then
        print_info "$CONFIG_DIR ディレクトリを作成しています..."
        mkdir -p "$CONFIG_DIR"
    fi

    # 既存データを保持する場合
    if [ "$KEEP_EXISTING_DATA" = true ]; then
        if [ -f "$CONFIG_FILE" ]; then
            # プレースホルダーの値が残っているか確認
            if grep -q "YOUR_DATABASE_NAME\|YOUR_DATABASE_USER\|YOUR_DATABASE_PASSWORD\|YOUR_OLLAMA_ENDPOINT\|YOUR_FIREBASE_PROJECT_ID" "$CONFIG_FILE"; then
                print_warning "config.phpにプレースホルダーの値が含まれています"
                print_info "既存の.envファイルから再生成します"
            else
                print_success "既存のconfig.phpを使用します"

                # 設定内容を表示
                echo ""
                echo "現在の設定:"
                grep "define('DB_NAME'" "$CONFIG_FILE" | sed "s/.*'\(.*\)'.*/  データベース名: \1/"
                grep "define('DB_USER'" "$CONFIG_FILE" | sed "s/.*'\(.*\)'.*/  ユーザー名: \1/"
                grep "define('OLLAMA_ENDPOINT'" "$CONFIG_FILE" | sed "s/.*'\(.*\)'.*/  Ollamaエンドポイント: \1/"
                grep "define('FIREBASE_PROJECT_ID'" "$CONFIG_FILE" | sed "s/.*'\(.*\)'.*/  Firebase Project ID: \1/"
                echo ""

                return
            fi
        else
            print_warning "config.phpが見つかりません"
            print_info "既存の.envファイルから生成します"
        fi
    fi

    # config.phpが存在する場合の処理（新規セットアップモード）
    if [ -f "$CONFIG_FILE" ] && [ "$KEEP_EXISTING_DATA" = false ]; then
        # プレースホルダーの値が残っているか確認
        if grep -q "YOUR_DATABASE_NAME\|YOUR_DATABASE_USER\|YOUR_DATABASE_PASSWORD\|YOUR_OLLAMA_ENDPOINT\|YOUR_FIREBASE_PROJECT_ID" "$CONFIG_FILE"; then
            print_warning "config.phpにプレースホルダーの値が含まれています"
            read -p "config.phpを再生成しますか？ (y/n): " regenerate
            if [[ ! $regenerate == [yY] ]]; then
                print_error "config.phpの設定が不完全です。手動で編集するか、スクリプトを再実行してください。"
                exit 1
            fi
        else
            print_success "config.phpが既に存在し、設定されています"
            read -p "既存のconfig.phpを上書きしますか？ (y/n): " overwrite
            if [[ ! $overwrite == [yY] ]]; then
                print_info "既存のconfig.phpを使用します"
                return
            fi
        fi
    fi

    # .envファイルから値を読み込む
    if [ -f "$ENV_FILE" ]; then
        print_info ".envファイルから設定を読み込んでいます..."
        source "$ENV_FILE"

        # config.phpを生成
        cat > "$CONFIG_FILE" << EOF
<?php
namespace config;

// DB接続情報
define('DB_HOST', 'mysql');
define('DB_NAME', '$MYSQL_DATABASE');
define('DB_USER', '$MYSQL_USER');
define('DB_PASS', '$MYSQL_PASSWORD');

define('OLLAMA_ENDPOINT', '$OLLAMA_ENDPOINT');

define('FIREBASE_PROJECT_ID', '$FIREBASE_PROJECT_ID');
define('FIREBASE_SERVICE_ACCOUNT_PATH', __DIR__ . '/service-account.json');


// Snippet Extractor Settings
define('MIN_FILE_LINES', 30);
EOF

        print_success "config.phpを作成しました"
    else
        print_error ".envファイルが見つかりません"
        print_info "先に.envファイルをセットアップする必要があります"
        exit 1
    fi
}

# Firebase Service Accountの確認
check_firebase_service_account() {
    print_header "Firebase Service Account の確認"

    if [ -f "$FIREBASE_SERVICE_ACCOUNT" ]; then
        print_success "Firebase Service Accountファイルが見つかりました"
        print_info "ファイル: $FIREBASE_SERVICE_ACCOUNT"

        # JSONファイルの妥当性をチェック
        if command_exists python3; then
            if python3 -c "import json; json.load(open('$FIREBASE_SERVICE_ACCOUNT'))" 2>/dev/null; then
                print_success "Service AccountファイルのJSON形式が正しいです"
            else
                print_error "Service AccountファイルのJSON形式が不正です"
                echo ""
                echo "ファイルを確認してください: $FIREBASE_SERVICE_ACCOUNT"
                exit 1
            fi
        fi
    else
        print_error "Firebase Service Accountファイルが見つかりません！"
        echo ""
        echo -e "${RED}必要なファイル: $FIREBASE_SERVICE_ACCOUNT${NC}"
        echo ""
        echo "Firebase Service Accountファイルの取得手順:"
        echo ""
        echo "  1. Firebase Consoleにアクセス"
        echo "     https://console.firebase.google.com/"
        echo ""
        echo "  2. プロジェクトを選択"
        echo ""
        echo "  3. プロジェクト設定（歯車アイコン）→ 「サービスアカウント」タブをクリック"
        echo ""
        echo "  4. 「新しい秘密鍵の生成」ボタンをクリック"
        echo ""
        echo "  5. ダウンロードしたJSONファイルを以下の場所に配置:"
        echo "     $FIREBASE_SERVICE_ACCOUNT"
        echo ""
        echo -e "${YELLOW}セキュリティ警告:${NC}"
        echo "  - このファイルには機密情報が含まれます"
        echo "  - .gitignoreに追加されていることを確認してください"
        echo "  - 公開リポジトリにコミットしないでください"
        echo ""

        read -p "Service Accountファイルを配置しましたか？ (y/n): " placed
        if [[ $placed == [yY] ]]; then
            if [ -f "$FIREBASE_SERVICE_ACCOUNT" ]; then
                print_success "Service Accountファイルが確認できました"
            else
                print_error "ファイルがまだ見つかりません: $FIREBASE_SERVICE_ACCOUNT"
                exit 1
            fi
        else
            print_error "Firebase Service Accountファイルが必要です"
            exit 1
        fi
    fi
}

# Composer依存関係のインストール
install_composer_dependencies() {
    print_header "Composer依存関係のインストール"

    if [ -f "composer.json" ]; then
        if command_exists composer; then
            print_info "Composer依存関係をインストールしています..."
            composer install --no-dev --optimize-autoloader
            print_success "Composer依存関係のインストールが完了しました"
        else
            print_warning "Composerがインストールされていません"
            print_info "Dockerコンテナ内でcomposer installを実行します"
        fi
    else
        print_warning "composer.jsonが見つかりません"
    fi
}

# Docker Composeでデプロイ
deploy_with_docker() {
    print_header "Dockerでデプロイ"

    local compose_cmd=$(get_compose_command)

    # 既存データを保持する場合は、コンテナを再起動するだけ
    if [ "$KEEP_EXISTING_DATA" = true ]; then
        print_info "既存データを保持したままコンテナを再起動します..."

        print_step "コンテナを停止しています..."
        $compose_cmd down 2>/dev/null || true

        print_step "コンテナを起動しています..."
        $compose_cmd up -d
    else
        # 新規セットアップの場合は、すべてをビルドし直す
        print_step "既存のコンテナを停止しています..."
        $compose_cmd down 2>/dev/null || true

        print_step "Dockerイメージをビルドしています..."
        $compose_cmd build --no-cache

        print_step "コンテナを起動しています..."
        $compose_cmd up -d
    fi

    # MySQLの初期化を待つ
    print_step "MySQLの初期化を待っています..."
    sleep 10

    echo ""
    print_success "デプロイが正常に完了しました！"
    echo ""
    print_info "サービスの状態を確認: $compose_cmd ps"
    print_info "ログを確認: $compose_cmd logs -f"
    echo ""
    print_info "APIエンドポイント: http://localhost:8080"
    print_info "MySQLポート: localhost:13306"
}

# .gitignoreのチェック
check_gitignore() {
    print_header ".gitignoreの確認"

    local needs_update=0

    if [ -f ".gitignore" ]; then
        # .envがignoreされているか確認
        if ! grep -q "^\.env$\|^\.env \|/\.env$" .gitignore; then
            print_warning ".envが.gitignoreに含まれていません"
            echo ".env" >> .gitignore
            print_success ".envを.gitignoreに追加しました"
            needs_update=1
        fi

        # config/ディレクトリがignoreされているか確認
        if ! grep -q "^config/$\|^config/\|/config/$" .gitignore; then
            print_warning "config/が.gitignoreに含まれていません"
            echo "config/" >> .gitignore
            print_success "config/を.gitignoreに追加しました"
            needs_update=1
        fi

        if [ $needs_update -eq 0 ]; then
            print_success ".gitignoreが適切に設定されています"
        fi
    else
        print_warning ".gitignoreが見つかりません"
        print_info ".gitignoreを作成しています..."
        cat > .gitignore << EOF
.env
config/
.DS_Store
/vendor/
.vscode/
db/
curltest
curltest.txt
EOF
        print_success ".gitignoreを作成しました"
    fi
}

# デプロイ後の検証
verify_deployment() {
    print_header "デプロイの検証"

    # .envから認証情報を読み込む
    source "$ENV_FILE"

    print_step "MySQLへの接続を確認しています..."

    # 最大30秒待機
    local max_attempts=6
    local attempt=1

    while [ $attempt -le $max_attempts ]; do
        if docker exec "$MYSQL_CONTAINER_NAME" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "SELECT 1;" >/dev/null 2>&1; then
            print_success "MySQLへの接続が確認できました"

            # データベースの存在を確認
            if docker exec "$MYSQL_CONTAINER_NAME" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "USE $MYSQL_DATABASE; SHOW TABLES;" >/dev/null 2>&1; then
                print_success "データベース '$MYSQL_DATABASE' が確認できました"

                # テーブル数を表示
                local table_count=$(docker exec "$MYSQL_CONTAINER_NAME" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$MYSQL_DATABASE';" 2>/dev/null)
                print_info "テーブル数: $table_count"
            else
                print_warning "データベース '$MYSQL_DATABASE' にアクセスできません"
            fi

            return 0
        fi

        print_info "接続を再試行しています... ($attempt/$max_attempts)"
        sleep 5
        ((attempt++))
    done

    print_warning "MySQLへの接続確認がタイムアウトしました"
    print_info "コンテナが完全に起動するまで時間がかかる場合があります"
    print_info "ログを確認: docker logs $MYSQL_CONTAINER_NAME"
}

# メイン実行
main() {
    print_header "$PROJECT_NAME デプロイ"

    check_dependencies
    check_gitignore
    handle_existing_data
    setup_env_file
    setup_config_file
    check_firebase_service_account
    install_composer_dependencies
    deploy_with_docker
    verify_deployment

    echo ""
    print_header "デプロイ完了"
    echo ""
    echo -e "${GREEN}🎉 すべてのセットアップが完了しました！${NC}"
    echo ""

    # .envから認証情報を読み込んで表示
    source "$ENV_FILE"

    echo "データベース接続情報:"
    echo "  ホスト: localhost:13306"
    echo "  データベース名: $MYSQL_DATABASE"
    echo "  ユーザー名: $MYSQL_USER"
    echo ""
    echo "次のステップ:"
    echo "  1. APIをテスト: curl http://localhost:8080"
    echo "  2. ログを確認: docker-compose logs -f"
    echo "  3. データベースに接続:"
    echo "     mysql -h 127.0.0.1 -P 13306 -u $MYSQL_USER -p"
    echo "     (パスワード: 設定したMYSQL_PASSWORD)"
    echo ""

    if [ "$KEEP_EXISTING_DATA" = true ]; then
        print_info "既存のデータは保持されています"
    else
        print_info "新しいデータベースが作成されました"
    fi
}

# メイン関数を実行
main
