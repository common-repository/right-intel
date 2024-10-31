<h3>Automatic Upgrade</h3>

<?php if (count($postIds) == 0) { ?>

<p>Upgrade complete!</p>

<?php } else { ?>
	
<p>The Sharpr WordPress Plugin now supports WordPress's new Media library functionality. Images from Sharpr posts will now be converted.</p>

<div class="regenerating">Converted image <span id="NumDone">0</span> of <?php echo count($postIds)?></div>
<div class="ri-progress">
	<?php foreach ($postIds as $id) { ?><img src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D" alt="" /><?php } ?>
</div>
<p id="UpgradeComplete" style="display:none">Done!</p>
<style>
.ri-progress img {
	height: 50px;
	width: 50px;
	margin: 5px 5px 0 0;
	background-color: #ccc;
}
</style>
<script>
(function($) {
	
	var ids = <?=json_encode($postIds)?>;
	var imgs = $('.ri-progress img');
	var numDoneDisplay = $('#NumDone');
	var numDone = 0;
	function process() {
		if (ids.length === 0) {			
			$.ajax('?page=regenerate_thumbnail_by_id&wp_post_id=done', {
				complete: function() {
					$('#UpgradeComplete').show();
				}
			});
			return;
		}
		var id = ids.pop();
		$.ajax('?page=regenerate_thumbnail_by_id&wp_post_id=' + id, {
			success: function(data) {
				if (data && data.src) {
					imgs[numDone].src = data.src;
				}
				else {
					imgs[numDone].style.backgroundColor = '#B1DCC4';
				}
				process();
			},
			complete: function() {
				numDoneDisplay.text(++numDone);
			}
		});
	}
	// start 2 threads
	process();
	process();
	
}(jQuery));
</script>

<?php } ?>