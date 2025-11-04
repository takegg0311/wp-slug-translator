<?php
/**
 * スラッグ生成を処理するクラス
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Slug Generator クラス
 */
class WST_Slug_Generator {

	/**
	 * スラッグが英数字のみで構成されているかチェック
	 *
	 * @param string $slug スラッグ
	 * @return bool 英数字のみの場合true
	 */
	public function is_alphanumeric_only( $slug ) {
		if ( empty( $slug ) ) {
			return false;
		}

		// 英数字とハイフンのみを許可
		return preg_match( '/^[a-z0-9\-]+$/i', $slug ) === 1;
	}

	/**
	 * 重複しないスラッグを生成
	 *
	 * @param string $slug 元のスラッグ
	 * @param int    $post_id 投稿ID（除外用、0の場合は新規投稿）
	 * @param string $post_type 投稿タイプ
	 * @return string 重複しないスラッグ
	 */
	public function generate_unique_slug( $slug, $post_id = 0, $post_type = 'post' ) {
		if ( empty( $slug ) ) {
			return '';
		}

		$original_slug = $slug;
		$counter      = 1;

		while ( $this->is_slug_exists( $slug, $post_id, $post_type ) ) {
			$slug = $original_slug . '-' . $counter;
			$counter++;
		}

		return $slug;
	}

	/**
	 * スラッグが存在するかチェック
	 *
	 * @param string $slug スラッグ
	 * @param int    $post_id 投稿ID（除外用）
	 * @param string $post_type 投稿タイプ
	 * @return bool 存在する場合true
	 */
	private function is_slug_exists( $slug, $post_id = 0, $post_type = 'post' ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} 
			WHERE post_name = %s 
			AND post_type = %s 
			AND post_status != 'trash'",
			$slug,
			$post_type
		);

		// 既存の投稿を除外（更新時）
		if ( $post_id > 0 ) {
			$query .= $wpdb->prepare( ' AND ID != %d', $post_id );
		}

		$existing_post = $wpdb->get_var( $query );

		return ! empty( $existing_post );
	}

	/**
	 * タイトルからスラッグを生成（WordPress標準の方法）
	 *
	 * @param string $title タイトル
	 * @return string スラッグ
	 */
	public function generate_slug_from_title( $title ) {
		// WordPressの標準関数を使用
		$slug = sanitize_title( $title );
		return $slug;
	}
}

