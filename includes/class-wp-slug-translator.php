<?php
/**
 * プラグインのメインクラス
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * メインクラス
 */
class WP_Slug_Translator {

	/**
	 * ローダー
	 *
	 * @var WST_Loader
	 */
	protected $loader;

	/**
	 * プラグインの初期化
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * 依存関係の読み込み
	 */
	private function load_dependencies() {
		require_once WST_PLUGIN_DIR . 'includes/class-api-handler.php';
		require_once WST_PLUGIN_DIR . 'includes/class-slug-generator.php';
		require_once WST_PLUGIN_DIR . 'includes/class-logger.php';
		require_once WST_PLUGIN_DIR . 'includes/class-translator.php';
		require_once WST_PLUGIN_DIR . 'admin/class-admin-menu.php';
	}

	/**
	 * ロケールの設定
	 */
	private function set_locale() {
		// 将来的に翻訳機能を追加する場合に使用
	}

	/**
	 * 管理画面用フックの定義
	 */
	private function define_admin_hooks() {
		$admin_menu = new WST_Admin_Menu();
		add_action( 'admin_menu', array( $admin_menu, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $admin_menu, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $admin_menu, 'enqueue_admin_assets' ) );
		
		// AJAX処理
		add_action( 'wp_ajax_wst_test_api_connection', array( $admin_menu, 'ajax_test_api_connection' ) );
		add_action( 'wp_ajax_wst_test_translation', array( $admin_menu, 'ajax_test_translation' ) );
		
		// ログクリア処理
		add_action( 'admin_post_wst_clear_logs', array( $admin_menu, 'handle_clear_logs' ) );
	}

	/**
	 * 公開側用フックの定義
	 */
	private function define_public_hooks() {
		$translator = new WST_Translator();

		// 投稿/ページの保存時
		add_action( 'save_post', array( $translator, 'handle_save_post' ), 10, 2 );
		add_action( 'wp_insert_post', array( $translator, 'handle_insert_post' ), 10, 3 );

		// 公開時
		add_action( 'publish_post', array( $translator, 'handle_publish_post' ), 10, 2 );
		add_action( 'publish_page', array( $translator, 'handle_publish_page' ), 10, 2 );
	}

	/**
	 * プラグインの実行
	 */
	public function run() {
		// フックは既に定義済み
	}
}

