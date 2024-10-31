<!doctype html>
<html lang="en">
<meta charset="utf-8">
<title>Post Preview</title>
<style>
html, body {
	padding: 0;
	margin: 0;
	font: 15px/1.4 Arial, Helvetica, sans-serif;
}
.article {
	width: 608px;
}
</style>
<link rel="stylesheet" href="<?php echo esc_html($cssUrl)?>" />

<div class="article">
	<p class="ri-bubble">
		Bubbles on this blog should look like this. 
		If you choose the bubble type "Image with shadows", the posts must have at least 608px to accommodate the bubble.
		If something looks strange, check the theme CSS for conflicts. 
	</p>
	<div class="ri-img-left">
		<span class="ri-image-wrapper">
			<img class="ri-image-secondary" src="<?php echo esc_html($placeholderImageUrl)?>" alt="" width="260" border="0" />
		</span>
	</div>
	<p class="ri-normal">
		This is the body of the post. 
		This text will sit to the right of the post image.
	</p>
	<p class="ri-normal">
		For best SEO results, this text should be a summary of the source article or your thoughts about it. 
		Copying the entire text of the source article can be bad for SEO and may infringe on copyright.
		However, publishers generally get an SEO boost when an article links to theirs.
		So be sure to link to the source to give credit.
		Be sure to use a WordPress plugin dedicated to SEO to make the best of your Sharpr post.
	</p>
	<p class="ri-normal">
		Also note the article text will wrap around the post image.
	</p>
	<p class="ri-normal">
		Read more from the source: <a href="#">Source Name</a>
	</p>
</div>
<?php die;