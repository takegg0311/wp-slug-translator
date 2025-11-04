# WordPress Slug Translator プラグイン開発

以下の要件に従って、WordPressプラグイン「WordPress Slug Translator」を作成してください。

## 基本情報
- プラグイン名: WordPress Slug Translator
- ディレクトリ構造: wp-slug-translator/
- メインファイル: wp-slug-translator.php

## 機能要件

### 1. コア機能
ページ/投稿のタイトルから英数字のスラッグを自動生成します。
- スラッグに英数字以外が含まれる場合、大規模言語モデルAPIで翻訳
- 既存スラッグとの重複チェック
- 重複時は末尾に数字を付与（例: "sample-slug-2"）

### 2. 対応API
以下のAPIに対応し、フォールバック機能を実装:
1. OpenAI API (gpt-4o-mini を推奨)
2. Google Gemini API (gemini-1.5-flash を推奨)
3. Anthropic Claude API (claude-3-5-sonnet-latest を推奨)

- 最大3つのAPIを優先順位付きで設定可能
- 1つ目が失敗したら2つ目、2つ目が失敗したら3つ目を自動実行

### 3. 自動実行タイミング
以下のタイミングでスラッグを自動生成:
- 新規ページ/投稿の保存時（スラッグが空の場合）
- 新規ページ/投稿の公開時（スラッグが空の場合）
- 既存ページ/投稿の編集時（スラッグに英数字以外が含まれる場合）

WordPressのアクションフック:
- `save_post`
- `wp_insert_post`
- `publish_post`
- `publish_page`

### 4. 管理画面 (WordPress管理メニュー)
設定ページを「設定 > Slug Translator」に追加:

#### 4.1 API設定タブ
- APIプロバイダー選択（OpenAI/Gemini/Claude）と実行優先順位（1位、2位、3位）
- 各APIのAPIキー入力フィールド
- モデル名入力フィールド（デフォルト値あり）
- 接続テストボタン

#### 4.2 翻訳設定タブ
- カスタムプロンプト入力（textarea）
- デフォルトプロンプト:
```
  以下のタイトルを英語に翻訳し、URLスラッグに適した形式（小文字、ハイフン区切り、3-5単語程度）で出力してください。
  特殊文字は使用せず、英数字とハイフンのみを使用してください。
  
  タイトル: {title}
  
  スラッグのみを出力してください:
```
- プロンプトプレースホルダー: `{title}` を実際のタイトルで置換

#### 4.3 テスト機能タブ
- テスト用タイトル入力フィールド
- 「翻訳テスト実行」ボタン
- 結果表示エリア（生成されたスラッグ、使用API、実行時間）

#### 4.4 ログ参照タブ
- 直近100件の翻訳ログをテーブル表示
  - 日時
  - 元のタイトル
  - 生成されたスラッグ
  - 使用API
  - ステータス（成功/失敗）
  - 実行時間
- ログのクリアボタン

### 5. データベース設計
wp_optionsテーブルに以下のオプションを保存:
- `wst_api_settings`: API設定（JSON形式）
- `wst_prompt_template`: プロンプトテンプレート
- `wst_translation_logs`: 翻訳ログ（JSON配列、最大100件）

## 技術仕様

### ファイル構造
```
wp-slug-translator/
├── wp-slug-translator.php          # メインプラグインファイル
├── includes/
│   ├── class-translator.php        # 翻訳処理クラス
│   ├── class-api-handler.php       # API通信クラス
│   ├── class-slug-generator.php    # スラッグ生成クラス
│   └── class-logger.php            # ログ記録クラス
├── admin/
│   ├── class-admin-menu.php        # 管理画面クラス
│   ├── admin-page.php              # 管理画面テンプレート
│   ├── css/admin-style.css         # 管理画面CSS
│   └── js/admin-script.js          # 管理画面JavaScript
└── languages/                      # 翻訳ファイル（将来対応）
```

### セキュリティ
- nonce検証を全フォームに実装
- APIキーはWordPress optionsに暗号化して保存
- 入力値のサニタイゼーション
- 出力値のエスケープ
- 権限チェック（manage_options）

### エラーハンドリング
- API通信失敗時のリトライロジック
- タイムアウト設定（30秒）
- エラーログの記録
- ユーザーへのエラー通知（admin_notices）

## 実装手順

ステップバイステップで実装してください:

1. プラグインのメインファイル（wp-slug-translator.php）と基本構造を作成
2. API Handler クラスを実装（OpenAI/Gemini/Claude対応）
3. Slug Generator クラスを実装（重複チェック含む）
4. WordPress フックとの統合（save_post等）
5. 管理画面の実装
6. ログ機能の実装
7. テスト機能の実装

## コーディング規約
- WordPress Coding Standards に準拠
- クラスベースの実装
- PHPDoc コメントを記述
- セキュリティベストプラクティスに従う

上記の要件に従って、まずプラグインの基本構造とメインファイルから作成を開始してください。