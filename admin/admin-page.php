<?php
/**
 * 管理画面ページテンプレート
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// 必要なクラスを読み込み
require_once WST_PLUGIN_DIR . 'includes/class-api-handler.php';
require_once WST_PLUGIN_DIR . 'includes/class-logger.php';

$api_settings    = get_option( 'wst_api_settings', array() );
$prompt_template = get_option( 'wst_prompt_template', '' );
$api_handler     = new WST_API_Handler();
$default_prompt  = $api_handler->get_default_prompt();

if ( empty( $prompt_template ) ) {
	$prompt_template = $default_prompt;
}

// タブの処理
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'api';
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="?page=wp-slug-translator&tab=api" class="nav-tab <?php echo 'api' === $active_tab ? 'nav-tab-active' : ''; ?>">
			API設定
		</a>
		<a href="?page=wp-slug-translator&tab=prompt" class="nav-tab <?php echo 'prompt' === $active_tab ? 'nav-tab-active' : ''; ?>">
			翻訳設定
		</a>
		<a href="?page=wp-slug-translator&tab=test" class="nav-tab <?php echo 'test' === $active_tab ? 'nav-tab-active' : ''; ?>">
			テスト機能
		</a>
		<a href="?page=wp-slug-translator&tab=logs" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
			ログ参照
		</a>
	</nav>

	<div class="wst-admin-content">
		<?php if ( 'api' === $active_tab ) : ?>
			<?php include WST_PLUGIN_DIR . 'admin/tabs/api-settings.php'; ?>
		<?php elseif ( 'prompt' === $active_tab ) : ?>
			<?php include WST_PLUGIN_DIR . 'admin/tabs/prompt-settings.php'; ?>
		<?php elseif ( 'test' === $active_tab ) : ?>
			<?php include WST_PLUGIN_DIR . 'admin/tabs/test-function.php'; ?>
		<?php elseif ( 'logs' === $active_tab ) : ?>
			<?php include WST_PLUGIN_DIR . 'admin/tabs/logs.php'; ?>
		<?php endif; ?>
	</div>
</div>

