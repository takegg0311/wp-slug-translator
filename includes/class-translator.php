<?php
/**
 * 翻訳処理を統合するクラス
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Translator クラス
 */
class WST_Translator {

	/**
	 * API Handler
	 *
	 * @var WST_API_Handler
	 */
	private $api_handler;

	/**
	 * Slug Generator
	 *
	 * @var WST_Slug_Generator
	 */
	private $slug_generator;

	/**
	 * Logger
	 *
	 * @var WST_Logger
	 */
	private $logger;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->api_handler    = new WST_API_Handler();
		$this->slug_generator = new WST_Slug_Generator();
		$this->logger         = new WST_Logger();
	}

	/**
	 * 投稿保存時の処理
	 *
	 * @param int $post_id 投稿ID
	 * @param WP_Post $post 投稿オブジェクト
	 * @return void
	 */
	public function handle_save_post( $post_id, $post ) {
		// リビジョンや自動保存をスキップ
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// 権限チェック
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 自動保存をスキップ
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$this->process_slug_translation( $post_id, $post );
	}

	/**
	 * 投稿挿入時の処理
	 *
	 * @param int $post_id 投稿ID
	 * @param WP_Post $post 投稿オブジェクト
	 * @param bool $update 更新かどうか
	 * @return void
	 */
	public function handle_insert_post( $post_id, $post, $update ) {
		// リビジョンや自動保存をスキップ
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// 自動保存をスキップ
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$this->process_slug_translation( $post_id, $post );
	}

	/**
	 * 投稿公開時の処理
	 *
	 * @param int $post_id 投稿ID
	 * @param WP_Post $post 投稿オブジェクト
	 * @return void
	 */
	public function handle_publish_post( $post_id, $post ) {
		$this->process_slug_translation( $post_id, $post );
	}

	/**
	 * ページ公開時の処理
	 *
	 * @param int $post_id 投稿ID
	 * @param WP_Post $post 投稿オブジェクト
	 * @return void
	 */
	public function handle_publish_page( $post_id, $post ) {
		$this->process_slug_translation( $post_id, $post );
	}

	/**
	 * スラッグ翻訳を処理
	 *
	 * @param int $post_id 投稿ID
	 * @param WP_Post $post 投稿オブジェクト
	 * @return void
	 */
	private function process_slug_translation( $post_id, $post ) {
		// 投稿タイプがpostまたはpage以外はスキップ
		if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		// タイトルが空の場合はスキップ
		if ( empty( $post->post_title ) ) {
			return;
		}

		$current_slug = get_post_field( 'post_name', $post_id );

		// ケース1: スラッグが空の場合
		if ( empty( $current_slug ) ) {
			$this->generate_slug_for_new_post( $post_id, $post );
			return;
		}

		// ケース2: スラッグに英数字以外が含まれている場合
		if ( ! $this->slug_generator->is_alphanumeric_only( $current_slug ) ) {
			$this->translate_existing_slug( $post_id, $post, $current_slug );
			return;
		}
	}

	/**
	 * 新規投稿のスラッグを生成
	 *
	 * @param int $post_id 投稿ID
	 * @param WP_Post $post 投稿オブジェクト
	 * @return void
	 */
	private function generate_slug_for_new_post( $post_id, $post ) {
		$title = $post->post_title;

		// WordPress標準の方法でスラッグを生成
		$slug = $this->slug_generator->generate_slug_from_title( $title );

		// 英数字のみの場合はそのまま使用
		if ( $this->slug_generator->is_alphanumeric_only( $slug ) ) {
			$unique_slug = $this->slug_generator->generate_unique_slug( $slug, $post_id, $post->post_type );
			$this->update_post_slug( $post_id, $unique_slug );
			return;
		}

		// 英数字以外が含まれる場合はAPIで翻訳
		$this->translate_and_update_slug( $post_id, $post, $title );
	}

	/**
	 * 既存スラッグを翻訳
	 *
	 * @param int $post_id 投稿ID
	 * @param WP_Post $post 投稿オブジェクト
	 * @param string $current_slug 現在のスラッグ
	 * @return void
	 */
	private function translate_existing_slug( $post_id, $post, $current_slug ) {
		// タイトルから翻訳を試みる
		$this->translate_and_update_slug( $post_id, $post, $post->post_title );
	}

	/**
	 * スラッグを翻訳して更新
	 *
	 * @param int $post_id 投稿ID
	 * @param WP_Post $post 投稿オブジェクト
	 * @param string $title タイトル
	 * @return void
	 */
	private function translate_and_update_slug( $post_id, $post, $title ) {
		// APIで翻訳
		$result = $this->api_handler->translate_slug( $title );

		// ログ記録
		$this->logger->log(
			$title,
			$result['slug'],
			$result['api_used'],
			$result['success'],
			$result['execution_time'],
			$result['error']
		);

		if ( $result['success'] && ! empty( $result['slug'] ) ) {
			// 重複チェックしてユニークなスラッグを生成
			$unique_slug = $this->slug_generator->generate_unique_slug( $result['slug'], $post_id, $post->post_type );
			$this->update_post_slug( $post_id, $unique_slug );
		} else {
			// エラー時はWordPress標準の方法でフォールバック
			$fallback_slug = $this->slug_generator->generate_slug_from_title( $title );
			$unique_slug   = $this->slug_generator->generate_unique_slug( $fallback_slug, $post_id, $post->post_type );
			$this->update_post_slug( $post_id, $unique_slug );
		}
	}

	/**
	 * 投稿のスラッグを更新
	 *
	 * @param int $post_id 投稿ID
	 * @param string $slug スラッグ
	 * @return void
	 */
	private function update_post_slug( $post_id, $slug ) {
		if ( empty( $slug ) ) {
			return;
		}

		// 無限ループを防ぐため、フックを一時的に無効化
		remove_action( 'save_post', array( $this, 'handle_save_post' ), 10 );
		remove_action( 'wp_insert_post', array( $this, 'handle_insert_post' ), 10 );

		wp_update_post(
			array(
				'ID'        => $post_id,
				'post_name' => $slug,
			)
		);

		// フックを再度有効化
		add_action( 'save_post', array( $this, 'handle_save_post' ), 10, 2 );
		add_action( 'wp_insert_post', array( $this, 'handle_insert_post' ), 10, 3 );
	}

	/**
	 * テスト用のスラッグ翻訳
	 *
	 * @param string $title タイトル
	 * @return array 結果
	 */
	public function test_translation( $title ) {
		$result = $this->api_handler->translate_slug( $title );

		// ログ記録
		$this->logger->log(
			$title,
			$result['slug'],
			$result['api_used'],
			$result['success'],
			$result['execution_time'],
			$result['error']
		);

		return $result;
	}
}

