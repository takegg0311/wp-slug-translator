<?php
/**
 * ログ参照タブ
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once WST_PLUGIN_DIR . 'includes/class-logger.php';
$logger  = new WST_Logger();
$logs    = $logger->get_logs();
$log_count = count( $logs );
?>

<div class="wst-logs-container">
	<?php if ( isset( $_GET['cleared'] ) && '1' === $_GET['cleared'] ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>ログをクリアしました。</p>
		</div>
	<?php endif; ?>

	<div class="wst-logs-header">
		<p>
			<strong>ログ件数: <?php echo esc_html( $log_count ); ?>件</strong>
			<?php if ( $log_count > 0 ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=wst_clear_logs&_wpnonce=' . wp_create_nonce( 'wst_clear_logs' ) ) ); ?>" 
					class="button" 
					onclick="return confirm('ログをクリアしますか？');">
					ログをクリア
				</a>
			<?php endif; ?>
		</p>
	</div>

	<?php if ( $log_count > 0 ) : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>日時</th>
					<th>元のタイトル</th>
					<th>生成されたスラッグ</th>
					<th>使用API</th>
					<th>ステータス</th>
					<th>実行時間</th>
					<th>エラー</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( $log['datetime'] ); ?></td>
						<td><?php echo esc_html( $log['title'] ); ?></td>
						<td><code><?php echo esc_html( $log['slug'] ); ?></code></td>
						<td><?php echo esc_html( $log['api_used'] ); ?></td>
						<td>
							<?php if ( $log['success'] ) : ?>
								<span class="wst-status success">成功</span>
							<?php else : ?>
								<span class="wst-status error">失敗</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( number_format( $log['execution_time'], 3 ) ); ?>秒</td>
						<td>
							<?php if ( ! empty( $log['error'] ) ) : ?>
								<span class="wst-error-message"><?php echo esc_html( $log['error'] ); ?></span>
							<?php else : ?>
								-
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p>ログがありません。</p>
	<?php endif; ?>
</div>

