<?php
/**
 * 翻訳設定タブ
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once WST_PLUGIN_DIR . 'includes/class-api-handler.php';
$api_handler = new WST_API_Handler();
$default_prompt = $api_handler->get_default_prompt();
$prompt_template = get_option( 'wst_prompt_template', '' );
if ( empty( $prompt_template ) ) {
	$prompt_template = $default_prompt;
}
?>

<form method="post" action="options.php">
	<?php settings_fields( 'wst_prompt_settings' ); ?>

	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="wst_prompt_template">プロンプトテンプレート</label>
			</th>
			<td>
				<textarea 
					name="wst_prompt_template" 
					id="wst_prompt_template" 
					rows="10" 
					class="large-text code"
				><?php echo esc_textarea( $prompt_template ); ?></textarea>
				<p class="description">
					<code>{title}</code> をプレースホルダーとして使用できます。実際のタイトルで置換されます。
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"></th>
			<td>
				<button type="button" class="button" id="wst-reset-prompt">デフォルトに戻す</button>
			</td>
		</tr>
	</table>

	<?php submit_button( '設定を保存' ); ?>
</form>

<script>
jQuery(document).ready(function($) {
	var defaultPrompt = <?php echo wp_json_encode( $default_prompt ); ?>;
	
	$('#wst-reset-prompt').on('click', function() {
		if (confirm('デフォルトのプロンプトに戻しますか？')) {
			$('#wst_prompt_template').val(defaultPrompt);
		}
	});
});
</script>

