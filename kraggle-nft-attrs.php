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
 * Version:           1.1.0
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
	$version = '1.1.0';

	wp_enqueue_style('kna', KNA_PLUGIN_URL . 'style/style.css', [], $version);
	wp_enqueue_script('module-kna', KNA_PLUGIN_URL . 'js/script.js', ['jquery'], $version);

	$fa = 'https://images.kgl.app';
	$json = json_decode(file_get_contents("$fa/martians/attributes"), true);
	
	if (!isset($json['directory'])) return '';
	$json = $json['directory'];

	usort($json, function($a, $b) {
		return $a['order'] <=> $b['order'];
	});
	$json = json_decode(json_encode($json));
	
	$data = (object) [];
	foreach ($json as $attr) {
		foreach ($attr->items as $item)
			$item->trait = str_replace('-', ' ', $item->file);
		$data->{$attr->path} = $attr;
	}

	wp_localize_script('module-kna', 'kna', [
		'url' => KNA_PLUGIN_URL,
		'attrs' => $data
	]);

	$selected = (object) [];
	$fa = 'https://fa.kgl.app';
	$pause = file_get_contents("$fa?t=light&i=pause-circle&class=svg-pause");
	$play = file_get_contents("$fa?t=light&i=play-circle&class=svg-play%20hidden");
	$arrow_left = file_get_contents("$fa?t=light&i=arrow-alt-left&class=left");
	$arrow_right = file_get_contents("$fa?t=light&i=arrow-alt-left&class=right");

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
						<div class="kna-control">
							<span class="kna-btn kna-skip prev <?= $attr ?>"><?= $arrow_left ?></span>
							<span class="kna-count">
								<num><?= $no + 1 ?></num> of <?= $qty ?>
							</span>
							<span class="kna-btn kna-skip next <?= $attr ?>"><?= $arrow_right ?></span>
						</div>
					</div>
					<span class="kna-btn pause <?= $attr ?>"><?= $pause . $play ?></span>

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
