<?php
/**
 * ログ記録を処理するクラス
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Logger クラス
 */
class WST_Logger {

	/**
	 * ログの最大件数
	 *
	 * @var int
	 */
	private $max_logs = 100;

	/**
	 * ログを記録
	 *
	 * @param string $title 元のタイトル
	 * @param string $slug 生成されたスラッグ
	 * @param string $api_used 使用したAPI
	 * @param bool   $success 成功したかどうか
	 * @param float  $execution_time 実行時間（秒）
	 * @param string $error エラーメッセージ
	 * @return void
	 */
	public function log( $title, $slug, $api_used, $success, $execution_time, $error = '' ) {
		$logs = $this->get_logs();

		$log_entry = array(
			'datetime'       => current_time( 'mysql' ),
			'timestamp'      => current_time( 'timestamp' ),
			'title'          => $title,
			'slug'           => $slug,
			'api_used'       => $api_used,
			'success'        => $success,
			'execution_time' => $execution_time,
			'error'          => $error,
		);

		// 先頭に追加
		array_unshift( $logs, $log_entry );

		// 最大件数を超えた場合は古いログを削除
		if ( count( $logs ) > $this->max_logs ) {
			$logs = array_slice( $logs, 0, $this->max_logs );
		}

		update_option( 'wst_translation_logs', $logs );
	}

	/**
	 * ログを取得
	 *
	 * @return array ログの配列
	 */
	public function get_logs() {
		$logs = get_option( 'wst_translation_logs', array() );
		return is_array( $logs ) ? $logs : array();
	}

	/**
	 * ログをクリア
	 *
	 * @return void
	 */
	public function clear_logs() {
		delete_option( 'wst_translation_logs' );
	}

	/**
	 * ログの件数を取得
	 *
	 * @return int ログの件数
	 */
	public function get_log_count() {
		return count( $this->get_logs() );
	}
}

