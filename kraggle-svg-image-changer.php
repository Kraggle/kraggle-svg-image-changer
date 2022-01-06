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
 * @package           Kraggle-SVG-Image-Changer
 *
 * @wordpress-plugin
 * Plugin Name:       Kraggles SVG Image Changer
 * Plugin URI:        http://kragglesites.com
 * Description:       Shortcode for an svg image color changer. Use shortcode [kgl-svg image="{media-id}" colors="{media-id}"]. The initial image (for reference) and ral-colors.json (for reference) are included in the plugin folder. 
 * Version:           1.4.2
 * Author:            Kraggle
 * Author URI:        http://kragglesites.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       kraggle-svg-image-changer
 * Domain Path:       /
 */



// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

define('KGL_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('KGL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KGL_PLUGIN_URL', plugin_dir_url(__FILE__));

add_filter('plugin_action_links', 'disable_child_link', 10, 2);
function disable_child_link($links, $file) {

	if ('kraggle-svg-image-changer/kraggle-svg-image-changer.php' == $file && isset($links['activate']))
		$links['activate'] = '<span>Activate</span>';

	return $links;
}

include KGL_PLUGIN_PATH . 'simple_html_dom.php';

function cc_mime_types($mimes) {
	$mimes['svg'] = 'image/svg+xml';
	$mimes['json'] = 'application/json';
	return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

function kgl_svg($attrs) {
	$version = '1.4.3';

	wp_enqueue_script('kgl-picker', KGL_PLUGIN_URL . 'kgl-picker.js', ['jquery'], $version);
	wp_enqueue_style('kgl-picker', KGL_PLUGIN_URL . 'kgl-picker.css', [], $version);

	extract(shortcode_atts(array(
		'image' => false,
		'colors' => false
	), $attrs));

	if (!$image || !$colors) return '';
	$image = preg_replace('/[\s\S\n]+?(<svg [\s\S\n]+svg>)[\s\S\n]+/', '$1', file_get_contents(get_attached_file($image)));
	$allColors = json_decode(file_get_contents(get_attached_file($colors)));

	$svg = str_get_html($image);
	$wIds = (object) [];
	foreach ($svg->find('[id^=svg-color-]') as $el) {
		$el->id = preg_replace('/(svg-color-\d+).*/', '$1', $el->id);
		$wId = $el->id;

		foreach ($el->find('[id^=wapf-]') as $wapf) {
			if (!isset($wIds->$wId))
				$wIds->$wId = str_replace('wapf-', '', $wapf->id);
		}
	}

	preg_match_all('/id="(svg-color-(\d+))?"/', $svg, $areas);

	ob_start();
	global $product;
	$product = wc_get_product(72);
	woocommerce_simple_add_to_cart();
	$checkout = str_get_html(ob_get_contents());
	ob_end_clean();

	foreach ($checkout->find('.wapf-wrapper') as $el)
		$el->class = 'wapf-wrapper kgl-hidden';

	foreach ($checkout->find('.wapf-product-totals') as $el)
		$el->class = 'wapf-product-totals kgl-hidden';

	foreach ($checkout->find('.quantity') as $el)
		$el->outertext = '';
	// $el->hidden = true;

	foreach ($checkout->find('button') as $el) {
		$el->innertext = 'Checkout';
		$el->class = 'et_pb_button et_pb_button_1 et_hover_enabled et_pb_bg_layout_light';
		$el->id = 'kgl-submit';
	}

	$checkout = str_replace('</form>', '<span class="kgl-price" data-base="750">£750</span></form>', $checkout);

	ob_start();
	$i = 0;

	$done = []; ?>

	<div class="kgl-wrapper">

		<div class="kgl-picker">

			<?php $opts = $svg->find('g[id=svg-options] g');
			$options = (object) [];
			foreach ($opts as $opt) {
				$els = $opt->find('rect');

				$default;
				$id;
				$title;
				$wapf;

				foreach ($els as $e) {
					if (preg_match('/^default-(\w+)/', $e->id, $match))
						$default = $match[1] == 'true';
					elseif (preg_match('/^id-(.*)/', $e->id, $match))
						$id = $match[1];
					elseif (preg_match('/^title-(.*)/', $e->id, $match))
						$title = $match[1];
					elseif (preg_match('/^wapf-(.*)/', $e->id, $match))
						$wapf = $match[1];
				}

				if (!$default) {
					$els = $svg->find('[id=' . $id . ']');
					foreach ($els as $el) {
						$el->style = 'display: none;';
					}
				}

				$options->$id = $default;
				$checked = $default ? ' checked' : ''; ?>

				<div class="kgl-option">
					<input type="checkbox" name="<?= $title ?>" id="<?= 'kgl-' . $title ?>" data-id="<?= $id ?>" <?= $checked ?> data-wapf="<?= $wapf ?>">
					<label for="<?= 'kgl-' . $title ?>"><?= $title ?></label>
				</div>

			<?php } ?>

			<div class="kgl-selector">
				<span>Area: </span>
				<?php $l = count($areas[2]);
				for ($j = 0; $j < $l; $j++) {
					$n = $areas[2][$j];
					if (in_array($n, $done))
						continue;
					$id = $areas[1][$j];
					$checked = !$j ? 'checked' : '';
					$done[] = $n;
					$hide = isset($options->$id) && !$options->$id ? 'hidden' : ''; ?>
					<span data-sort="<?= $n ?>">
						<input type="radio" name="kgl-area" value="<?= $id ?>" id="<?= 'kgl-' . $id ?>" <?= $checked ?><?= $hide ?> data-wapf="<?= $wIds->$id ?>">
						<label for="<?= 'kgl-' . $id ?>" <?= $hide ?>><?= $n ?></label>
					</span>
				<?php } ?>
			</div>

			<?php foreach ($allColors as $title => $colors) {
				$open = !$i ? ' open' : '';
				$i++; ?>

				<div class="kgl-title<?= $open ?>"><?= $title . ' Hues' ?></div>
				<div class="kgl-collapse<?= $open ?>">

					<?php foreach ($colors as $name => $color) { ?>
						<div class="kgl-color" data-color="<?= $color ?>" data-ral="<?= $name ?>" style="background-color: <?= $color ?>" title="<?= $name ?>">
						</div>
					<?php } ?>

				</div>
			<?php } ?>

			<?= $checkout ?>

		</div>

		<div class="kgl-svg"><?= $svg ?></div>

	</div>

	<div></div>

<?php $html = ob_get_contents();
	ob_end_clean();

	return $html;
}
add_shortcode('kgl-svg', 'kgl_svg');

// <form class="cart" action="https://louisandjoshuaboats.com/product/boat-design/" method="post" enctype="multipart/form-data">
// 	<div class="wapf-wrapper">
// 		<div class="wapf-field-group" data-group="p_72">

// 			<div class="wapf-field-row">
// 				<div class="wapf-field-container wapf-field-true-false" style="width:100%;" for="61981debfadad">

// 					<div class="wapf-field-label wapf--above"><label><span>Coachlines</span></label></div>
// 					<div class="wapf-field-input">
// 						<div class="wapf-checkable">
// 							<input type="hidden" value="0" name="wapf[field_61981debfadad]">
// 							<label class="wapf-checkbox-label" for="92192">
// 								<input id="92192" type="checkbox" value="1" name="wapf[field_61981debfadad]" class="wapf-input" data-is-required="" data-field-id="61981debfadad" data-wapf-price="750" data-wapf-pricetype="fixed">
// 								<span class="wapf-label-text">
// 									<span class="wapf-pricing-hint">(+£750.00)</span> </span>
// 							</label>
// 						</div>
// 					</div>



// 				</div>
// 			</div>
// 			<div class="wapf-field-row">
// 				<div class="wapf-field-container wapf-field-true-false" style="width:100%;" for="61981e05711ba">

// 					<div class="wapf-field-label wapf--above"><label><span>Handrails</span></label></div>
// 					<div class="wapf-field-input">
// 						<div class="wapf-checkable">
// 							<input type="hidden" value="0" name="wapf[field_61981e05711ba]">
// 							<label class="wapf-checkbox-label" for="71225">
// 								<input id="71225" type="checkbox" value="1" name="wapf[field_61981e05711ba]" class="wapf-input" data-is-required="" data-field-id="61981e05711ba" data-wapf-price="750" data-wapf-pricetype="fixed">
// 								<span class="wapf-label-text">
// 									<span class="wapf-pricing-hint">(+£750.00)</span> </span>
// 							</label>
// 						</div>
// 					</div>



// 				</div>
// 			</div>
// 		</div><input type="hidden" value="p_72" name="wapf_field_groups">
// 	</div>
// 	<div class="wapf-product-totals" data-product-type="simple" data-product-price="750" data-product-id="72" style="display: block;">
// 		<div class="wapf--inner">
// 			<div>
// 				<span>Product total</span>
// 				<span class="wapf-product-total price amount">£750.00</span>
// 			</div>
// 			<div>
// 				<span>Options total</span>
// 				<span class="wapf-options-total price amount">£0.00</span>
// 			</div>
// 			<div>
// 				<span>Grand total</span>
// 				<span class="wapf-grand-total price amount">£750.00</span>
// 			</div>
// 		</div>
// 	</div>
// 	<div class="wcpa_form_outer"><input type="hidden" id="ral-color-1" name="ral-color-1" value=""><input type="hidden" id="ral-color-2" name="ral-color-2" value=""><input type="hidden" id="ral-color-3" name="ral-color-3" value=""><input type="hidden" id="ral-color-4" name="ral-color-4" value=""></div>
// 	<div class="quantity">
// 		<label class="screen-reader-text" for="quantity_61982116b0b40">Boat Design quantity</label>
// 		<input type="number" id="quantity_61982116b0b40" class="input-text qty text" step="1" min="1" max="" name="quantity" value="1" title="Qty" size="4" placeholder="" inputmode="numeric">
// 	</div>

// 	<button type="submit" name="add-to-cart" value="72" class="single_add_to_cart_button button alt">Add to cart</button>

// </form>