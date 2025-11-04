<?php
/**
 * テスト機能タブ
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="wst-test-container">
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="wst-test-title">テスト用タイトル</label>
			</th>
			<td>
				<input type="text" 
					id="wst-test-title" 
					class="regular-text" 
					placeholder="例: こんにちは、世界"
					value=""
				/>
				<p class="description">翻訳テストを実行するタイトルを入力してください</p>
			</td>
		</tr>
		<tr>
			<th scope="row"></th>
			<td>
				<button type="button" class="button button-primary" id="wst-run-test">
					翻訳テスト実行
				</button>
			</td>
		</tr>
	</table>

	<div id="wst-test-result" class="wst-test-result-container" style="display: none;">
		<h3>結果</h3>
		<table class="widefat">
			<tbody>
				<tr>
					<th>生成されたスラッグ</th>
					<td id="wst-result-slug"></td>
				</tr>
				<tr>
					<th>使用API</th>
					<td id="wst-result-api"></td>
				</tr>
				<tr>
					<th>実行時間</th>
					<td id="wst-result-time"></td>
				</tr>
				<tr>
					<th>ステータス</th>
					<td id="wst-result-status"></td>
				</tr>
				<tr id="wst-result-error-row" style="display: none;">
					<th>エラー</th>
					<td id="wst-result-error"></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

