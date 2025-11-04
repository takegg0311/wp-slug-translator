<?php
/**
 * Plugin Name: WordPress Slug Translator
 * Plugin URI: https://github.com/takegg0311/wp-slug-translator
 * Description: ページ/投稿のタイトルから英数字のスラッグを自動生成し、大規模言語モデルAPIで翻訳するプラグイン
 * Version: 1.0.0
 * Author: takegg0311
 * Author URI: https://github.com/takegg0311
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-slug-translator
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * プラグインのバージョン
 */
define( 'WST_VERSION', '1.0.0' );

/**
 * プラグインのベースディレクトリパス
 */
define( 'WST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * プラグインのベースURL
 */
define( 'WST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * プラグインのベースファイル名
 */
define( 'WST_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * プラグインのメインクラス
 */
require_once WST_PLUGIN_DIR . 'includes/class-wp-slug-translator.php';

/**
 * プラグインの初期化
 */
function wst_run() {
	$plugin = new WP_Slug_Translator();
	$plugin->run();
}
wst_run();

