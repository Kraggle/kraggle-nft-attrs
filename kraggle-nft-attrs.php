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
 * Version:           1.1.1
 * Author:            Kraggle
 * Author URI:        http://kragglesites.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       kraggle-nft-attrs
 * Domain Path:       /
 */

define('KNA_VERSION', '1.1.1');

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

define('KNA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('KNA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KNA_PLUGIN_URL', plugin_dir_url(__FILE__));

add_shortcode('nft-attrs', function() {
	wp_enqueue_style('kna', KNA_PLUGIN_URL . 'style/style.css', [], KNA_VERSION);
	wp_enqueue_script('module-kna', KNA_PLUGIN_URL . 'js/script.js', ['jquery'], KNA_VERSION);

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
});

global $used_variations, $layers;

add_shortcode('nft-rarity', function($attrs) {
	global $used_variations, $layers;
	
	extract(shortcode_atts(
		array(
			'variations' => false,
			'columns' => 3
		),
		$attrs
	));

	if (!$variations) return '';
	$variations = json_decode(file_get_contents(get_attached_file($variations)));
	
	$fa = 'https://images.kgl.app';
	$layers = json_decode(file_get_contents("$fa/martians/attributes"));
	
	if (!isset($layers->directory)) return '';
	$layers = $layers->directory;
	
	wp_enqueue_style('kna', KNA_PLUGIN_URL . 'style/style.css', [], KNA_VERSION);
	wp_enqueue_script('module-kna', KNA_PLUGIN_URL . 'js/script.js', ['jquery'], KNA_VERSION);

	// reset the variations, in case the shortcode is 
	// used more than once on a page
	$used_variations = (object) [];

	prepare_variations($variations, 1); // Do the set layers
	prepare_variations($variations, 2); // Do the matching layers
	prepare_variations($variations, 3); // Get the rest
	
	ob_start(); ?>

	<div class="kna-rarity-wrap" style="grid-template-columns: repeat(<?= $columns ?> , 1fr);">

		<?php foreach ($variations as $variation) { ?>
			
			<div class="kna-variation">
				<div class="kna-img-wrap">
					<?php foreach ($variation->layers as $layer) { ?>
						<img src="<?= $layer->url ?>" alt="layer">
					<?php } ?>
				</div>
				<div class="kna-describe">
					<span class="kna-title"><?= $variation->name ?></span>
					<span class="kna-block">
						<span class="kna-key">Layers</span>
						<?php $all = [];
						foreach ($variation->layers as $layer => $value)
							$all[] = $layers->$layer->singular;
						echo  implode(', ', $all); ?>
					</span>
					<span class="kna-block"><span class="kna-key">Rarity</span><?= $variation->rarity ?></span>
					<span class="kna-block"><span class="kna-key">Quantity</span><?= $variation->quantity ?></span>
				</div>
			</div>
		
		<?php } ?>
	
	</div>

<?php $html = ob_get_contents();
	ob_end_clean();

	return $html;
});

function kna_script_as_module($tag, $handle, $src) {
	if (preg_match('/^module-/', $handle)) {
		$tag = '<script type="module" src="' . esc_url($src) . '" id="' . $handle . '"></script>';
	}

	return $tag;
}
add_filter('script_loader_tag', 'kna_script_as_module', 10, 3);

function prepare_variations(&$variations, $run) {
	global $used_variations;
	
	foreach ($variations as &$variation) {
		foreach ($variation->layers as $attr => &$type) {
			if (!isset($used_variations->$attr)) 
				$used_variations->$attr = [];
				
			if ($run == 1) {
				// this run adds preset to used_variations
				
				$ts = explode(' ', $type);
				$types = (object) [];
				foreach ($ts as &$t) {
					if (preg_match('/~/', $t)) {
						$t = explode('~', $t);
						$types->{$t[0]} = $t[1];
					} else if (preg_match('/random|unique/', $t)) 
						$types->$t = true;
					else if (preg_match('/^https?:\/\//', $t)) 
						$types = get_layer($attr, 'url', $t);
					else 
						$types = get_layer($attr, 'file', $t);
				}
				$type = $types;
				
			} else if ($run == 2) {
				// this run gets any layers that are match
				
				foreach ($type as $key => $value) {
					if ($key == 'match') {
						$matches = get_layer([$attr, $value]);
						$type = $matches[0];
						$variation->layers->$value = $matches[1];
					}
				}
			} else {
				if (!isset($type->file))
					$type = get_layer($attr);
			}
		}
	}
}

function get_layer($attr, $type = false, $find = null) {
	global $used_variations, $layers;
	$layer = '';
	
	/* This grabs the layer when url or file is set */
	if ($type && $find) {
		$items_1 = $layers->$attr->items;
		
		foreach ($items_1 as $item) {
			if ($item->$type == $find) {
				$used_variations->$attr[] = $item->file;
				return $item;
			}
		}
		
		return $layer;
	}
	
	/* This picks up mathching attributes */
	if (is_array($attr)) {
		
		$items_1 = $layers->{$attr[0]}->items;
		$items_2 = $layers->{$attr[1]}->items;
		$used_1 = $used_variations->{$attr[0]};
		$used_2 = $used_variations->{$attr[1]};
		shuffle($items_1);
		
		foreach ($items_1 as $item_1) {
			$item_2 = false;
			
			if (isset($item_1->matches) && isset($item_1->matches->{$attr[1]})) {
				foreach ($items_2 as $item) {
					if ($item->file == $item_1->matches->{$attr[1]})
						$item_2 = $item;
				}
			}
			
			if ($item_2 && !in_array($item_1->file, $used_1) && !in_array($item_2->file, $used_2)) {
				$used_variations->{$attr[0]}[] = $item_1->file;
				$used_variations->{$attr[1]}[] = $item_2->file;
				return [$item_1, $item_2];
			}
		}
		
		return [(object) ['error' => true], (object) ['error' => true]];
	}
	
	/* This finds a unique layer */
	$items_1 = $layers->$attr->items;
	$used = $used_variations->$attr;
	do {
		$layer = $items_1[array_rand($items_1)];
	} while (in_array($layer->file, $used));
	$used_variations->$attr[] = $layer->file;
	
	return $layer;
}

if (!function_exists('logger')) {
	function logger() {
		$db = array_shift(debug_backtrace());
		$line = $db['line'];
		$file = $db['file'];

		$msg = "$file:$line [logger]";

		foreach (func_get_args() as $arg) {
			error_log($msg . (in_array(gettype($arg), ['string', 'double', 'integer']) ? $arg : json_encode($arg, JSON_PRETTY_PRINT)));
		}
	}
}