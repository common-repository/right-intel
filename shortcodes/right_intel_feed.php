<?php

$exports = function($attr) {
	if (empty($attr['url'])) {
		return 'Shortcode right-intel-feed requires url attribute.';
	}
	$__url = preg_replace('~\.\w+$~', '', $attr['url']) . '.json?limit=' . ((int) $attr['limit'] <= 0 ? '100' : $attr['limit']);
	$__json = Ri_Curl::getContents($__url);
	$feed = json_decode($__json);
	ob_start();
	
	if ($attr['template']) {
		require(get_template_directory() . '/' . $attr['template']);
		return ob_get_clean();
	}
	
	$intelMaxLength = (int) $attr['intel_maxlength'];
	$headlineMaxLength = (int) $attr['headline_maxlength'];
	$summaryMaxLength = (int) $attr['summary_maxlength'];
	
	?>

		<div class="ri-feed-articles">
			<?php foreach ($feed->items as $item) { ?>
				<div class="ri-feed-article">
					<?php if (!empty($item->intel)) { ?>
						<div class="ri-feed-intel ri-bubble"><?php echo esc_html(
						// truncate full_text if needed
						$intelMaxLength > 0 && strlen($item->intel) > $intelMaxLength ? 
						substr($item->intel, 0, $intelMaxLength) . '...' :
						$item->intel
					)?></div>
					<?php } ?>
					<?php if (@$item->image->url && !preg_match('~/img/missing~', $item->image->url) && !empty($attr['img'])) { ?>
						<div class="ri-feed-image"><?php echo Ri_ImageSizer::img($attr['img'], $item)?></div>
					<?php } ?>
					<div class="ri-feed-headline"><?php echo esc_html(
						// truncate full_text if needed
						$headlineMaxLength > 0 && strlen($item->headline) > $headlineMaxLength ? 
						substr($item->headline, 0, $headlineMaxLength) . '...' :
						$item->headline
					)?></div>
					<?php if (!empty($item->summary)) { ?>
						<div class="ri-feed-summary"><?php echo esc_html(
							// truncate full_text if needed
							$summaryMaxLength > 0 && strlen($item->full_text) > $summaryMaxLength ? 
							substr($item->full_text, 0, $summaryMaxLength) . '...' :
							$item->full_text
						)?></div>
					<?php } ?>
					<?php if (!empty($item->link)) { ?>				
						<div class="ri-feed-source">
							<a class="ri-feed-source-link" target="_blank" href="<?php esc_html($item->link)?>"><?php echo esc_html($item->source_name) ?: 'Source'?></a>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	
	<?php
	return ob_get_clean();
};
