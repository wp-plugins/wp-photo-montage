<?php
	/**
	 * @file admin-menu.php
	 * Admin menu for WP Photo Montage
	 * @author Richard@TowerWebDesign.co.uk
	 **/
?>

<h2>WP Photo Montage</h2>

<p>To display a photo montage in your page or post use the photo-montage shortcode.</p>

<p>e.g.</p>

<pre>[photo_montage category="portfolio" width="600" height="600" background="#fff" columns="3" rows="3"]</pre>

<h3>Parameters</h3>
<ul>
<li>category - category name or id</li>
<li>width - width in pixel</li>
<li>height - height in pixels</li>
<li>background - background colour in hexadecimal</li>
<li>columns - number of columns of photos</li>
<li>rows - number of rows of photos</li>
</ul>

<?php
	if (!function_exists('get_the_image'))
		echo '<p><strong>Get The Image plugin is required. <a href="https://wordpress.org/plugins/get-the-image " target="_blank">Click here to download.</a></strong></p>';
?>

<p><a href="http://towerwebdesign.co.uk/wp-photo-montage">Click here for more information on WP Photo Montage</a></p>



