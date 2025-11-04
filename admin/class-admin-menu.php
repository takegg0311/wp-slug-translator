<?php
/**
 * 管理画面メニューを処理するクラス
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Admin Menu クラス
 */
class WST_Admin_Menu {

	/**
	 * 管理画面メニューを追加
	 */
	public function add_admin_menu() {
		add_options_page(
			'Slug Translator',
			'Slug Translator',
			'manage_options',
			'wp-slug-translator',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * 設定を登録
	 */
	public function register_settings() {
		// API設定
		register_setting( 'wst_api_settings', 'wst_api_settings', array( $this, 'sanitize_api_settings' ) );
		// プロンプトテンプレート
		register_setting( 'wst_prompt_settings', 'wst_prompt_template' );
	}

	/**
	 * API設定をサニタイズ
	 *
	 * @param array $settings 設定配列
	 * @return array サニタイズされた設定
	 */
	public function sanitize_api_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		$api_handler = new WST_API_Handler();
		$sanitized   = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$provider_key = "provider_{$i}";
			$api_key_key  = "api_key_{$i}";
			$model_key    = "model_{$i}";

			// プロバイダー
			if ( isset( $settings[ $provider_key ] ) ) {
				$provider = sanitize_text_field( $settings[ $provider_key ] );
				if ( in_array( $provider, array( 'openai', 'gemini', 'claude' ), true ) ) {
					$sanitized[ $provider_key ] = $provider;
				}
			}

			// APIキー（暗号化して保存）
			if ( isset( $settings[ $api_key_key ] ) && ! empty( $settings[ $api_key_key ] ) ) {
				$api_key = sanitize_text_field( $settings[ $api_key_key ] );
				$sanitized[ $api_key_key ] = $api_handler->encrypt_api_key( $api_key );
			} elseif ( isset( $settings[ $api_key_key ] ) && empty( $settings[ $api_key_key ] ) ) {
				// 空の場合は既存の値を保持
				$existing = get_option( 'wst_api_settings', array() );
				if ( isset( $existing[ $api_key_key ] ) ) {
					$sanitized[ $api_key_key ] = $existing[ $api_key_key ];
				}
			}

			// モデル名
			if ( isset( $settings[ $model_key ] ) ) {
				$sanitized[ $model_key ] = sanitize_text_field( $settings[ $model_key ] );
			}
		}

		return $sanitized;
	}

	/**
	 * 管理画面のアセットを読み込む
	 *
	 * @param string $hook フック名
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_wp-slug-translator' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wst-admin-style',
			WST_PLUGIN_URL . 'admin/css/admin-style.css',
			array(),
			WST_VERSION
		);

		wp_enqueue_script(
			'wst-admin-script',
			WST_PLUGIN_URL . 'admin/js/admin-script.js',
			array( 'jquery' ),
			WST_VERSION,
			true
		);

		wp_localize_script(
			'wst-admin-script',
			'wstAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wst_admin_nonce' ),
			)
		);
	}

	/**
	 * 管理画面ページを表示
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'このページにアクセスする権限がありません。' );
		}

		require_once WST_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/**
	 * API接続テストのAJAX処理
	 */
	public function ajax_test_api_connection() {
		check_ajax_referer( 'wst_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => '権限がありません' ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : '';
		$api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';
		$model    = isset( $_POST['model'] ) ? sanitize_text_field( $_POST['model'] ) : '';

		if ( empty( $provider ) || empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => 'プロバイダーとAPIキーを入力してください' ) );
		}

		$api_handler = new WST_API_Handler();
		$result      = $api_handler->test_api_connection( $provider, $api_key, $model );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * 翻訳テストのAJAX処理
	 */
	public function ajax_test_translation() {
		check_ajax_referer( 'wst_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error' => '権限がありません' ) );
		}

		$title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'error' => 'タイトルを入力してください' ) );
		}

		require_once WST_PLUGIN_DIR . 'includes/class-translator.php';
		$translator = new WST_Translator();
		$result     = $translator->test_translation( $title );

		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'slug'           => $result['slug'],
					'api_used'       => $result['api_used'],
					'execution_time' => $result['execution_time'],
				)
			);
		} else {
			wp_send_json_error(
				array(
					'error' => $result['error'],
				)
			);
		}
	}

	/**
	 * ログクリア処理
	 */
	public function handle_clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'このページにアクセスする権限がありません。' );
		}

		check_admin_referer( 'wst_clear_logs' );

		require_once WST_PLUGIN_DIR . 'includes/class-logger.php';
		$logger = new WST_Logger();
		$logger->clear_logs();

		wp_safe_redirect( add_query_arg( 'cleared', '1', admin_url( 'options-general.php?page=wp-slug-translator&tab=logs' ) ) );
		exit;
	}
}

