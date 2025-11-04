<?php
/**
 * API設定タブ
 *
 * @package WP_Slug_Translator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once WST_PLUGIN_DIR . 'includes/class-api-handler.php';
$api_handler = new WST_API_Handler();
$api_settings = get_option( 'wst_api_settings', array() );
?>

<form method="post" action="options.php" id="wst-api-settings-form">
	<?php settings_fields( 'wst_api_settings' ); ?>

	<table class="form-table">
		<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<?php
			$provider_key = "provider_{$i}";
			$api_key_key  = "api_key_{$i}";
			$model_key    = "model_{$i}";
			$provider     = $api_settings[ $provider_key ] ?? '';
			$api_key      = '';
			if ( ! empty( $api_settings[ $api_key_key ] ) ) {
				$api_key = $api_handler->decrypt_api_key( $api_settings[ $api_key_key ] );
			}
			$model = '';
			if ( ! empty( $api_settings[ $model_key ] ) ) {
				$model = $api_settings[ $model_key ];
			} elseif ( ! empty( $provider ) ) {
				$model = $api_handler->get_default_model( $provider );
			}
			?>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $provider_key ); ?>">
						<?php echo esc_html( "優先順位 {$i}" ); ?>
					</label>
				</th>
				<td>
					<select name="wst_api_settings[<?php echo esc_attr( $provider_key ); ?>]" id="<?php echo esc_attr( $provider_key ); ?>" class="wst-provider-select">
						<option value="">選択してください</option>
						<option value="openai" <?php selected( $provider, 'openai' ); ?>>OpenAI</option>
						<option value="gemini" <?php selected( $provider, 'gemini' ); ?>>Google Gemini</option>
						<option value="claude" <?php selected( $provider, 'claude' ); ?>>Anthropic Claude</option>
					</select>
				</td>
			</tr>
			<tr class="wst-api-key-row" data-priority="<?php echo esc_attr( $i ); ?>">
				<th scope="row">
					<label for="<?php echo esc_attr( $api_key_key ); ?>">
						APIキー
					</label>
				</th>
				<td>
					<input type="password" 
						name="wst_api_settings[<?php echo esc_attr( $api_key_key ); ?>]" 
						id="<?php echo esc_attr( $api_key_key ); ?>" 
						value="<?php echo esc_attr( $api_key ); ?>" 
						class="regular-text wst-api-key-input"
						placeholder="APIキーを入力してください"
					/>
					<p class="description">APIキーを入力してください（既に入力済みの場合は変更しない限り空欄でOK）</p>
				</td>
			</tr>
			<tr class="wst-model-row" data-priority="<?php echo esc_attr( $i ); ?>">
				<th scope="row">
					<label for="<?php echo esc_attr( $model_key ); ?>">
						モデル名
					</label>
				</th>
				<td>
					<input type="text" 
						name="wst_api_settings[<?php echo esc_attr( $model_key ); ?>]" 
						id="<?php echo esc_attr( $model_key ); ?>" 
						value="<?php echo esc_attr( $model ); ?>" 
						class="regular-text wst-model-input"
						placeholder="モデル名を入力してください"
					/>
					<p class="description">
						OpenAI: gpt-4o-mini, Gemini: gemini-1.5-flash, Claude: claude-3-5-sonnet-latest
					</p>
				</td>
			</tr>
			<tr class="wst-test-row" data-priority="<?php echo esc_attr( $i ); ?>">
				<th scope="row"></th>
				<td>
					<button type="button" class="button wst-test-connection" data-priority="<?php echo esc_attr( $i ); ?>">
						接続テスト
					</button>
					<span class="wst-test-result" data-priority="<?php echo esc_attr( $i ); ?>"></span>
				</td>
			</tr>
			<?php if ( $i < 3 ) : ?>
				<tr><td colspan="2"><hr /></td></tr>
			<?php endif; ?>
		<?php endfor; ?>
	</table>

	<?php submit_button( '設定を保存' ); ?>
</form>

