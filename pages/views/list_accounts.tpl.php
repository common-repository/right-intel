<div class="wrap" id="RiSettings">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2>Sharpr Settings</h2>
	
	<?php if ($hasConnectedBefore) { ?>
		<div class="ri-settings-area">
	
			<div class="ri-preview-wrapper">
				<h3>Visual Preview</h3>
				<iframe id="FramePostPreview" src="<?php echo esc_html($previewUrl)?>" seamless></iframe>
			</div>

			<form method="post" action="" class="ri-form" id="FormStylingOptions">
				<fieldset>
					<legend>Bubble Styling</legend>
					<p id="WrapperBubbleType">
						<label>Bubble Type</label>
						<select name="bubble_type" id="InputBubbleType">
							<option value="image"<?php echo ($bubble_type=='css3' ? '' : ' selected')?>>Image with shadows (608px wide)</option>
							<option value="css3"<?php echo ($bubble_type=='css3' ? ' selected' : '')?>>CSS3 shape (variable width; Recommended)</option>
						</select>
					</p>
					<p id="WrapperColorBubbleCss3">
						<label>Bubble Color</label>
						<input type="text" name="color_bubble" id="InputColorBubble" value="<?php echo esc_html($color_bubble)?>">
					</p>
					<p id="WrapperColorText">
						<label>Bubble Text Color</label>
						<input type="text" name="color_text" id="InputColorText" value="<?php echo esc_html($color_text)?>">
					</p>
					<p id="WrapperUseOswald">
						<label>Bubble Text Font</label>
						<select name="use_oswald" id="InputUseOswald">
							<option value="1"<?php echo ($use_oswald==='1' ? ' selected' : '')?>>Oswald</option>
							<option value="2"<?php echo ($use_oswald==='2' ? ' selected' : '')?>>Arvo Italic</option>
							<option value="3"<?php echo ($use_oswald==='3' ? ' selected' : '')?>>Georgia Italic</option>
							<option value="0"<?php echo ($use_oswald==='0' ? ' selected' : '')?>>Paragraph default</option>
						</select>						
					</p>
				</fieldset>
				<fieldset>
					<legend>Image Display</legend>
					<?php if ($themeSupportsThumbnailAbovePost) { ?>
					<p id="WrapperImageDisplayType">
						<label>Show Post Image (if supported by your theme)</label>
						<select name="image_display_type" id="InputImageDisplayType">
							<option value="post_only"<?php echo ($image_display_type=='post_only' ? '' : ' selected')?>>Only below headline (Recommended)</option>
							<option value="thumbnail_only"<?php echo ($image_display_type=='thumbnail_only' ? ' selected' : '')?>>Only above headline</option>
							<option value="both"<?php echo ($image_display_type=='both' ? ' selected' : '')?>>Above headline and below headline</option>
						</select>
					</p>
					<?php } ?>
				</fieldset>
				<p class="ri-form-buttons">
					<input type="submit" value="Save" class="button-primary" name="save" />
				</p>
			</form>
	
		</div>
	<?php } ?>
	
	<div class="ri-admin-section">
		<?php if (count($msgs)) { ?>
			<div class="updated settings-error" id="setting-error-settings_updated"> 
				<p><strong>Success! <?php echo join(' ', $msgs)?></strong></p>
			</div>
		<?php } ?>
		<?php if (isset($_GET['error'])) { ?>
			<div class="updated settings-error" id="setting-error-settings_updated"> 
				<p><strong><?php echo esc_html($_GET['error'])?></strong></p>
			</div>
		<?php } ?>

		<?php if (!$validInstall) { ?>

			<?php Ri_Flash::output()?>
			<p>To connect a Sharpr Hub, please check the WordPress installation then refresh this page.</p>

		<?php } else { ?>

			<?php if ($hasConnectedBefore) { ?>
			
				<h2>Connected Sharpr Hubs</h2>
				<p>Sharpr editors from any of the following hubs can push Sharpr posts to this blog.</p>
				
			<?php } else { ?>
				
				<h2>Connect a Sharpr Hub</h2>
				<p>By connecting Sharpr to WordPress, editors can push Sharpr posts to this blog.</p>
				<p>After you connect one or more hubs, you will see styling options.</p>
				
			<?php } ?>

			<?php if (count($creds)) { ?>

				<table class="wp-list-table widefat ri-list-table">
					<thead>
						<th>Hub Name</th>
						<th>Connected By</th>
						<th>Connected On</th>
						<th>Actions</th>
					</thead>
					<tbody>
						<?php foreach ($creds as $cred) { ?>
							<tr>
								<td><?php echo esc_html($cred->instance_name)?></td>
								<td><?php echo esc_html($cred->User->display_name)?></td>
								<td><?php echo date_i18n(get_option('date_format'), strtotime($cred->created))?></td>
								<td><a href="options.php?page=right_intel_disconnect_account&amp;account_id=<?php echo esc_html($cred->api_login)?>" class="ri-confirm" data-confirm-msg="Are you sure you want to disconnect the hub &quot;<?php echo esc_html($cred->instance_name)?>&quot;?">Disconnect</a></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>

			<?php } ?>

			<form action="<?php echo esc_html($actionUrl)?>" method="post">
				<?php foreach ($connectionFields as $name => $value) { ?>
					<input type="hidden" name="<?php echo esc_html($name)?>" value="<?php echo esc_html($value)?>" />
				<?php } ?>
				<input type="submit" value="Connect Hub &rsaquo;" class="button-primary" name="go" />
			</form>
		<?php } ?>
	</div>
	
	<div class="ri-admin-section">
		<h2>Sharpr Short Codes</h2>
		
		<p>Sharpr supports the following short codes. 
			For support, please contact <a href="mailto:support@sharpr.com">support@sharpr.com</a>.
		</p>
		
		<h3 class="ri-h3">[right_intel_feed] - Latest posts from your Sharpr Hub</h3>
		
		<p>Working example: <code>[right_intel_feed url="https://sharpr.com/posts/rss/jRlr9vKhPp1fdLBk8sRbFmn/4359/myintel.rss"]</code></p>
		
		<p>Note that HTML will have a small amount of styling that you can override if needed.</p>
		
		<p>Available attributes:</p>
		
		<table class="wp-list-table widefat ri-list-table">
			<thead>
				<th>Name</th>
				<th>Description</th>
				<th>Default Value</th>
				<th>Examples</th>
			</thead>
			<tbody>
				<tr>
					<td><code>url</code></td>
					<td>A RSS URL found under Admin > RSS</td>
					<td><em>required</em></td>
					<td>https://sharpr.com/posts/rss/jRlr9vKhPp1fdLBk8sRbFmn/4359/myintel.rss</td>
				</tr>
				<tr>
					<td><code>img</code></td>
					<td>The size and method of image resizing. Full documentation at 
						<a href="https://sharpr.com/developers/images" target="_blank">sharpr.com/developers</a>.
					</td>
					<td>pin-150.jpg</td>
					<td>pin-100.jpg, exact-100x100.jpg, fit-100x100.jpg, crop-20.exact-100x100.jpg</td>
				</tr>
				<tr>
					<td><code>limit</code></td>
					<td>The maximum number of posts to show. Default of 0 means 100.</td>
					<td>3</td>
					<td><em>any number up to 100</em></td>
				</tr>
				<tr>
					<td><code>intel_maxlength</code></td>
					<td>Truncate insight bubble text when longer than this number of characters.</td>
					<td>500</td>
					<td><em>any number up to 500</em></td>
				</tr>
				<tr>
					<td><code>headline_maxlength</code></td>
					<td>Truncate headline text when longer than this number of characters.</td>
					<td>161</td>
					<td><em>any number up to 161</em></td>
				</tr>
				<tr>
					<td><code>summary_maxlength</code></td>
					<td>Truncate summary text when longer than this number of characters.</td>
					<td>250</td>
					<td><em>any number up to 250</em></td>
				</tr>
				<tr>
					<td><code>template</code></td>
					<td>If given, the path to a template file relative to your theme directory. 
						Variables available are <code>$attr</code> which is an array containing the attributes passed to the shortcode
						and <code>$feed</code> which has structure like 
						<a target="_blank" href="https://sharpr.com/posts/rss/jRlr9vKhPp1fdLBk8sRbFmn/4359/myintel.json">this example</a>.
					</td>
					<td>250</td>
					<td><em>any number up to 250</em></td>
				</tr>
			</tbody>
		</table>
		
		<h3 class="ri-h3">[right_intel_board] - Embed story HTML</h3>
		
		<p>Working example: <code>[right_intel_board url="https://sharpr.com/board/e9aa29daffc1ae614a89d4b7382b04330efe09aa/raw"]</code></p>
		
		<p>Note that HTML will be completely unstyled.</p>
		
		<p>Available attributes:</p>
		
		<table class="wp-list-table widefat ri-list-table">
			<thead>
				<th>Name</th>
				<th>Description</th>
				<th>Default Value</th>
				<th>Examples</th>
			</thead>
			<tbody>
				<tr>
					<td><code>url</code></td>
					<td>An Insight Board URL found under Stories &gt; Edit Story &gt; Enable Insight Boards &gt; check Enabled &gt; Get embed code &gt; Server Request.</td>
					<td><em>required</em></td>
					<td>https://sharpr.com/board/e9aa29daffc1ae614a89d4b7382b04330efe09aa/raw</td>
				</tr>
			</tbody>
		</table>		
		
	</div>
	
</div>
