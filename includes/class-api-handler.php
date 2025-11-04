<?php
/**
 * API通信を処理するクラス
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * API Handler クラス
 */
class WST_API_Handler {

	/**
	 * API設定
	 *
	 * @var array
	 */
	private $api_settings;

	/**
	 * タイムアウト時間（秒）
	 *
	 * @var int
	 */
	private $timeout = 30;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->load_api_settings();
	}

	/**
	 * API設定を読み込む
	 */
	private function load_api_settings() {
		$settings = get_option( 'wst_api_settings', array() );
		$this->api_settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * スラッグを翻訳する
	 *
	 * @param string $title タイトル
	 * @param string $prompt_template プロンプトテンプレート
	 * @return array 結果（success, slug, api_used, execution_time, error）
	 */
	public function translate_slug( $title, $prompt_template = '' ) {
		$start_time = microtime( true );

		// プロンプトテンプレートの取得
		if ( empty( $prompt_template ) ) {
			$prompt_template = get_option( 'wst_prompt_template', $this->get_default_prompt() );
		}

		// プロンプトの生成
		$prompt = str_replace( '{title}', $title, $prompt_template );

		// 優先順位に従ってAPIを試行
		$apis = $this->get_priority_apis();
		$last_error = '';

		foreach ( $apis as $api_config ) {
			if ( empty( $api_config['provider'] ) || empty( $api_config['api_key'] ) ) {
				continue;
			}

			$result = $this->call_api( $api_config, $prompt );

			if ( $result['success'] ) {
				$execution_time = microtime( true ) - $start_time;
				return array(
					'success'        => true,
					'slug'           => $result['slug'],
					'api_used'       => $api_config['provider'],
					'execution_time' => $execution_time,
					'error'          => '',
				);
			}

			$last_error = $result['error'];
		}

		$execution_time = microtime( true ) - $start_time;
		return array(
			'success'        => false,
			'slug'           => '',
			'api_used'       => '',
			'execution_time' => $execution_time,
			'error'          => $last_error ? $last_error : 'すべてのAPIが失敗しました',
		);
	}

	/**
	 * 優先順位に従ってAPI設定を取得
	 *
	 * @return array API設定の配列
	 */
	private function get_priority_apis() {
		$apis = array();

		for ( $priority = 1; $priority <= 3; $priority++ ) {
			$provider_key = "provider_{$priority}";
			$api_key_key  = "api_key_{$priority}";
			$model_key    = "model_{$priority}";

			if ( ! empty( $this->api_settings[ $provider_key ] ) && ! empty( $this->api_settings[ $api_key_key ] ) ) {
				$decrypted_key = $this->decrypt_api_key( $this->api_settings[ $api_key_key ] );
				
				// 復号化されたAPIキーが空でないことを確認
				// 空の場合は復号化に失敗した可能性がある
				if ( ! empty( $decrypted_key ) ) {
					$apis[] = array(
						'provider' => $this->api_settings[ $provider_key ],
						'api_key'  => $decrypted_key,
						'model'    => $this->api_settings[ $model_key ] ?? $this->get_default_model( $this->api_settings[ $provider_key ] ),
					);
				}
			}
		}

		return $apis;
	}

	/**
	 * APIを呼び出す
	 *
	 * @param array  $api_config API設定
	 * @param string $prompt プロンプト
	 * @return array 結果（success, slug, error）
	 */
	private function call_api( $api_config, $prompt ) {
		$provider = $api_config['provider'];
		$method   = "call_{$provider}_api";

		if ( method_exists( $this, $method ) ) {
			return $this->$method( $api_config, $prompt );
		}

		return array(
			'success' => false,
			'slug'    => '',
			'error'   => "未対応のプロバイダー: {$provider}",
		);
	}

	/**
	 * OpenAI APIを呼び出す
	 *
	 * @param array  $api_config API設定
	 * @param string $prompt プロンプト
	 * @return array 結果
	 */
	private function call_openai_api( $api_config, $prompt ) {
		$api_key = $api_config['api_key'];
		$model   = $api_config['model'] ?? 'gpt-4o-mini';

		$url = 'https://api.openai.com/v1/chat/completions';

		$body = array(
			'model'    => $model,
			'messages' => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'max_tokens' => 50,
			'temperature' => 0.3,
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => $this->timeout,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'slug'    => '',
				'error'   => $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			return array(
				'success' => false,
				'slug'    => '',
				'error'   => "HTTP {$status_code}: " . substr( $body, 0, 200 ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
			return array(
				'success' => false,
				'slug'    => '',
				'error'   => 'APIレスポンスの形式が不正です',
			);
		}

		$slug = trim( $body['choices'][0]['message']['content'] );
		$slug = $this->sanitize_slug( $slug );

		return array(
			'success' => true,
			'slug'    => $slug,
			'error'   => '',
		);
	}

	/**
	 * Google Gemini APIを呼び出す
	 *
	 * @param array  $api_config API設定
	 * @param string $prompt プロンプト
	 * @return array 結果
	 */
	private function call_gemini_api( $api_config, $prompt ) {
		$api_key = $api_config['api_key'];
		$model   = $api_config['model'] ?? 'gemini-1.5-flash';

		$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

		$body = array(
			'contents' => array(
				array(
					'parts' => array(
						array(
							'text' => $prompt,
						),
					),
				),
			),
			'generationConfig' => array(
				'maxOutputTokens' => 50,
				'temperature'     => 0.3,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => $this->timeout,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'slug'    => '',
				'error'   => $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			return array(
				'success' => false,
				'slug'    => '',
				'error'   => "HTTP {$status_code}: " . substr( $body, 0, 200 ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return array(
				'success' => false,
				'slug'    => '',
				'error'   => 'APIレスポンスの形式が不正です',
			);
		}

		$slug = trim( $body['candidates'][0]['content']['parts'][0]['text'] );
		$slug = $this->sanitize_slug( $slug );

		return array(
			'success' => true,
			'slug'    => $slug,
			'error'   => '',
		);
	}

	/**
	 * Anthropic Claude APIを呼び出す
	 *
	 * @param array  $api_config API設定
	 * @param string $prompt プロンプト
	 * @return array 結果
	 */
	private function call_claude_api( $api_config, $prompt ) {
		$api_key = $api_config['api_key'];
		$model   = $api_config['model'] ?? 'claude-3-5-sonnet-latest';

		$url = 'https://api.anthropic.com/v1/messages';

		$body = array(
			'model'     => $model,
			'max_tokens' => 50,
			'messages' => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => $this->timeout,
				'headers' => array(
					'x-api-key'      => $api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'   => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'slug'    => '',
				'error'   => $response->get_error_message(),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			return array(
				'success' => false,
				'slug'    => '',
				'error'   => "HTTP {$status_code}: " . substr( $body, 0, 200 ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['content'][0]['text'] ) ) {
			return array(
				'success' => false,
				'slug'    => '',
				'error'   => 'APIレスポンスの形式が不正です',
			);
		}

		$slug = trim( $body['content'][0]['text'] );
		$slug = $this->sanitize_slug( $slug );

		return array(
			'success' => true,
			'slug'    => $slug,
			'error'   => '',
		);
	}

	/**
	 * スラッグをサニタイズ
	 *
	 * @param string $slug スラッグ
	 * @return string サニタイズされたスラッグ
	 */
	private function sanitize_slug( $slug ) {
		// 改行や余分な空白を削除
		$slug = preg_replace( '/\s+/', '-', trim( $slug ) );
		// 英数字とハイフンのみを許可
		$slug = preg_replace( '/[^a-z0-9\-]/i', '', $slug );
		// 複数のハイフンを1つに
		$slug = preg_replace( '/-+/', '-', $slug );
		// 先頭・末尾のハイフンを削除
		$slug = trim( $slug, '-' );
		// 小文字に変換
		$slug = strtolower( $slug );

		return $slug;
	}

	/**
	 * APIキーを暗号化
	 *
	 * @param string $api_key 平文のAPIキー
	 * @return string 暗号化されたAPIキー
	 */
	public function encrypt_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return '';
		}

		// WordPressのsaltを使用してシンプルな暗号化（本番環境ではより強力な暗号化を推奨）
		$key  = wp_salt( 'auth' );
		$data = base64_encode( $api_key );
		return base64_encode( $data . '|' . hash_hmac( 'sha256', $data, $key ) );
	}

	/**
	 * APIキーを復号化
	 *
	 * @param string $encrypted_api_key 暗号化されたAPIキー
	 * @return string 平文のAPIキー
	 */
	public function decrypt_api_key( $encrypted_api_key ) {
		if ( empty( $encrypted_api_key ) ) {
			return '';
		}

		$key = wp_salt( 'auth' );
		
		// base64デコードを試行
		$decoded = base64_decode( $encrypted_api_key, true );
		if ( $decoded === false ) {
			// base64デコードに失敗した場合、プレーンテキストの可能性
			// または既に復号化済みの可能性がある
			return '';
		}

		$parts = explode( '|', $decoded );
		if ( count( $parts ) !== 2 ) {
			// フォーマットが異なる場合は復号化失敗
			return '';
		}

		$data = $parts[0];
		$hash = $parts[1];

		// ハッシュ検証
		$expected_hash = hash_hmac( 'sha256', $data, $key );
		if ( ! hash_equals( $expected_hash, $hash ) ) {
			// ハッシュ検証失敗（タイミング攻撃対策でhash_equalsを使用）
			return '';
		}

		$decrypted = base64_decode( $data, true );
		if ( $decrypted === false ) {
			return '';
		}

		return $decrypted;
	}

	/**
	 * デフォルトのプロンプトを取得
	 *
	 * @return string デフォルトプロンプト
	 */
	public function get_default_prompt() {
		return "以下のタイトルを英語に翻訳し、URLスラッグに適した形式（小文字、ハイフン区切り、3-5単語程度）で出力してください。\n特殊文字は使用せず、英数字とハイフンのみを使用してください。\n\nタイトル: {title}\n\nスラッグのみを出力してください:";
	}

	/**
	 * デフォルトのモデル名を取得
	 *
	 * @param string $provider プロバイダー名
	 * @return string デフォルトモデル名
	 */
	public function get_default_model( $provider ) {
		$defaults = array(
			'openai' => 'gpt-4o-mini',
			'gemini' => 'gemini-1.5-flash',
			'claude' => 'claude-3-5-sonnet-latest',
		);

		return $defaults[ $provider ] ?? '';
	}

	/**
	 * API接続テスト
	 *
	 * @param string $provider プロバイダー
	 * @param string $api_key APIキー
	 * @param string $model モデル名
	 * @return array 結果（success, message）
	 */
	public function test_api_connection( $provider, $api_key, $model = '' ) {
		if ( empty( $model ) ) {
			$model = $this->get_default_model( $provider );
		}

		$test_prompt = 'test';
		$api_config = array(
			'provider' => $provider,
			'api_key'  => $api_key,
			'model'    => $model,
		);

		$result = $this->call_api( $api_config, $test_prompt );

		if ( $result['success'] ) {
			return array(
				'success' => true,
				'message' => '接続に成功しました',
			);
		}

		return array(
			'success' => false,
			'message' => $result['error'],
		);
	}
}

