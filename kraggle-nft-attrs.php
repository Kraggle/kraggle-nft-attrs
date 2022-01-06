<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://kragglesites.com
 * @since             
 * @package           Kraggle-NFT-Attrs
 *
 * @wordpress-plugin
 * Plugin Name:       Kraggles NFT Attributes
 * Plugin URI:        http://kragglesites.com
 * Description:       Shortcode for showing off nft attributes
 * Version:           1.0.0
 * Author:            Kraggle
 * Author URI:        http://kragglesites.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       kraggle-nft-attrs
 * Domain Path:       /
 */



// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

define('KNA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('KNA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KNA_PLUGIN_URL', plugin_dir_url(__FILE__));

function kna_shortcode() {
	$version = '1.0.0';

	wp_enqueue_style('kna', KNA_PLUGIN_URL . 'style/style.css', [], $version);
	wp_enqueue_script('module-kna', KNA_PLUGIN_URL . 'js/script.js', ['jquery'], $version);
	wp_localize_script('module-kna', 'kna', [
		'url' => KNA_PLUGIN_URL
	]);

	$data = json_decode(file_get_contents(KNA_PLUGIN_PATH . 'data/imagedata.json'));
	$path = KNA_PLUGIN_PATH . 'images/';
	$url = KNA_PLUGIN_URL . 'images/';

	ob_start(); ?>

	<div class="kna-container">
		<?php foreach ($data as $attr => $value) { ?>
			<p class="kna-title"><?= $attr ?></p>
			<div class="kna-attrs">
				<?php
				usort($value, function ($a, $b) {
					return $a->rarity <=> $b->rarity;
				});
				foreach ($value as $item) {
					$mPath = "{$attr}/{$item->trait}.svg";
					if (!file_exists($path . $mPath)) {
						continue;
					} ?>
					<div class="kna-attr">
						<img class="kna-img" src="<?= $url . $mPath ?>" alt="<?= $item->trait ?>">
						<p class="kna-name"><?= str_replace('-', ' ', $item->trait) ?></p>
						<p class="kna-rare"><?= "{$item->rarity}%" ?></p>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>

<?php $html = ob_get_contents();
	ob_end_clean();

	return $html;
}
add_shortcode('nft-attrs', 'kna_shortcode');

function kna_script_as_module($tag, $handle, $src) {
	if (preg_match('/^module-/', $handle)) {
		$tag = '<script type="module" src="' . esc_url($src) . '" id="' . $handle . '"></script>';
	}

	return $tag;
}
add_filter('script_loader_tag', 'kna_script_as_module', 10, 3);
