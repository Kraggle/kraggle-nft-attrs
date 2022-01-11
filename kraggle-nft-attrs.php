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
 * Version:           1.0.6
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
	$version = '1.0.6';

	wp_enqueue_style('kna', KNA_PLUGIN_URL . 'style/style.css', [], $version);
	wp_enqueue_script('module-kna', KNA_PLUGIN_URL . 'js/script.js', ['jquery'], $version);

	$json = json_decode(file_get_contents(KNA_PLUGIN_PATH . 'data/imagedata.json'));
	$path = KNA_PLUGIN_PATH . 'images/';
	$url = KNA_PLUGIN_URL . 'images/';
	$data = (object) [];

	foreach ($json as $attr) {
		$data->{$attr->path} = $attr;
		$items = [];
		foreach ($attr->items as $item) {
			$mPath = "{$attr->path}/{$item->file}.svg";
			if (!file_exists($path . $mPath)) continue;

			$item->url = $url . $mPath;
			$item->trait = str_replace('-', ' ', $item->file);
			$items[] = $item;
		}
		$data->{$attr->path}->items = $items;
	}

	wp_localize_script('module-kna', 'kna', [
		'url' => KNA_PLUGIN_URL,
		'attrs' => $data
	]);

	$selected = (object) [];
	$pause = file_get_contents('https://fa.kgl.app?t=light&i=pause-circle');
	$pause = str_replace('<svg ', '<svg class="svg-pause" ', $pause);
	$play = file_get_contents('https://fa.kgl.app?t=light&i=play-circle');
	$play = str_replace('<svg ', '<svg class="svg-play hidden" ', $play);

	ob_start(); ?>

	<div class="kna-container">

		<div class="kna-left">
			<?php foreach ($data as $attr => $obj) {
				$no = array_rand($obj->items);
				$qty = count($obj->items);
				$item = $obj->items[$no];
				$selected->$attr = $item; ?>

				<div class="kna-detail <?= $attr ?>">
					<div class="kna-bar">
						<span class="kna-title"><?= $obj->singular ?></span>
						<span class="kna-count">
							<num><?= $no + 1 ?></num> of <?= $qty ?>
						</span>
					</div>
					<span class="kna-btn <?= $attr ?>"><?= $pause . $play ?></span>

					<div class="kna-attr trait">
						<span class="kna-key">Trait</span>
						<span class="kna-val"><?= $item->trait ?></span>
					</div>

					<div class="kna-attr occur">
						<span class="kna-key">Occurrence</span>
						<span class="kna-val"><?= $item->occurrence ?></span>
					</div>

					<div class="kna-attr rarity">
						<span class="kna-key">Rarity</span>
						<span class="kna-val"><?= $item->rarity . '%' ?></span>
					</div>

				</div>

			<?php } ?>
		</div>

		<div class="kna-right">
			<?php foreach ($data as $attr => $obj) {
				$item = $selected->$attr; ?>
				<img class="kna-img <?= $attr ?>" src="<?= $item->url ?>" alt />
			<?php } ?>
		</div>
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
