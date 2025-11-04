/**
 * 管理画面スクリプト
 *
 * @package WP_Slug_Translator
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// API設定の表示/非表示
		$('.wst-provider-select').on('change', function() {
			var priority = $(this).closest('tr').find('.wst-provider-select').attr('id').replace('provider_', '');
			var provider = $(this).val();
			
			if (provider) {
				$('.wst-api-key-row[data-priority="' + priority + '"]').addClass('active');
				$('.wst-model-row[data-priority="' + priority + '"]').addClass('active');
				$('.wst-test-row[data-priority="' + priority + '"]').addClass('active');
			} else {
				$('.wst-api-key-row[data-priority="' + priority + '"]').removeClass('active');
				$('.wst-model-row[data-priority="' + priority + '"]').removeClass('active');
				$('.wst-test-row[data-priority="' + priority + '"]').removeClass('active');
			}
		});

		// 初期表示の設定
		$('.wst-provider-select').each(function() {
			$(this).trigger('change');
		});

		// 接続テスト
		$('.wst-test-connection').on('click', function() {
			var priority = $(this).data('priority');
			var provider = $('#provider_' + priority).val();
			var apiKey = $('#api_key_' + priority).val();
			var model = $('#model_' + priority).val();

			if (!provider) {
				alert('プロバイダーを選択してください');
				return;
			}

			var $result = $('.wst-test-result[data-priority="' + priority + '"]');
			$result.html('<span class="loading">テスト中...</span>');

			// 保存されたAPIキーを使用してテスト（フォームの値は参考情報として送信）
			$.ajax({
				url: wstAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wst_test_api_connection',
					nonce: wstAdmin.nonce,
					priority: priority,
					provider: provider,
					api_key: apiKey,
					model: model
				},
				success: function(response) {
					if (response.success) {
						$result.html('<span class="success">✓ ' + response.data.message + '</span>');
					} else {
						$result.html('<span class="error">✗ ' + response.data.message + '</span>');
					}
				},
				error: function() {
					$result.html('<span class="error">✗ 接続エラーが発生しました</span>');
				}
			});
		});

		// 翻訳テスト実行
		$('#wst-run-test').on('click', function() {
			var title = $('#wst-test-title').val().trim();

			if (!title) {
				alert('タイトルを入力してください');
				return;
			}

			var $button = $(this);
			var originalText = $button.text();
			$button.prop('disabled', true).text('実行中...');

			$.ajax({
				url: wstAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wst_test_translation',
					nonce: wstAdmin.nonce,
					title: title
				},
				success: function(response) {
					$button.prop('disabled', false).text(originalText);

					if (response.success) {
						$('#wst-result-slug').text(response.data.slug || '-');
						$('#wst-result-api').text(response.data.api_used || '-');
						$('#wst-result-time').text(response.data.execution_time ? response.data.execution_time.toFixed(3) + '秒' : '-');
						$('#wst-result-status').html('<span class="wst-status success">成功</span>');
						$('#wst-result-error-row').hide();
						$('#wst-test-result').show();
					} else {
						$('#wst-result-slug').text('-');
						$('#wst-result-api').text('-');
						$('#wst-result-time').text('-');
						$('#wst-result-status').html('<span class="wst-status error">失敗</span>');
						$('#wst-result-error').text(response.data.error || 'エラーが発生しました');
						$('#wst-result-error-row').show();
						$('#wst-test-result').show();
					}
				},
				error: function() {
					$button.prop('disabled', false).text(originalText);
					alert('エラーが発生しました');
				}
			});
		});
	});
})(jQuery);

