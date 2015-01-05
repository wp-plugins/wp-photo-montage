<?php
/*
Plugin Name: WP Photo Montage
Plugin URI: http://towerwebdesign.co.uk/wp-photo-montage
Description: A WordPress photo montage creator
Version: 1.0.0
Author: Richard Brown
Author URI: http://www.TowerWebDesign.co.uk
License: GPL2

Copyright 2012 Richard Brown  (email : richard@towerwebdesign.co.uk)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!class_exists('WP_Photo_Montage')) {

	class WP_Photo_Montage {
		public function __construct() {
			add_action('admin_menu', array(&$this, 'add_menu'));
			add_shortcode('photo_montage', array(&$this, 'photo_montage_shortcode'));
			$this->createOutputFolder();
			$this->deleteOldFiles();
 		}
		public function add_menu() {
			add_options_page('WP Photo Montage Settings', 'Photo Montage', 'manage_options', 'wp_photo_montage', array(&$this, 'plugin_settings_page'));
		}
		/**
		 * Menu Callback
		 */     
		public function plugin_settings_page() {
			if (!current_user_can('manage_options')) {
				  wp_die(__('You do not have sufficient permissions to access this page.'));
			 }

			 // Render the settings template
			 require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'admin-menu.php';
		}
				
		/**
		  * @function photo_montage_shortcode
		  * @return String of html
		  **/
		  
		public function photo_montage_shortcode($atts) {		
			$html = '';

			extract( shortcode_atts( array(
				'category'			=>	'',
				'width'				=>	'600',
				'height'				=>	'400',
				'columns'			=>	'4',
				'rows'				=>	'2',
				'background'		=>	'#fa4',
			),$atts));
			
			$image_filename = $this->buildPhotoMontageImage($category, $width, $height, $columns, $rows, $background);
			if ($image_filename != null) {
				$base_image_filename = basename($image_filename);
				$image_url = plugins_url('tmp'.DIRECTORY_SEPARATOR.$base_image_filename,__FILE__);
				$category_name = $this->findCategoryName($category);
				$title = "Photo montage of ".$category_name;
				$html .= '<img src="'.$image_url.'" width="'.$width.'" height="'.$height.'" alt="'.$title.'" title="'.$title.'" />';
			} else
				$html .= '<p>Problem creating photo montage.</p>';
			return $html;
		}
		
		protected function findCategoryName($category) {
			return $category;
		}
		
		protected function buildPhotoMontageImage($category, $width, $height, $columns, $rows, $background) {
			// first create our final bitmap to render to
			$image=$this->CreateOutputBitmap($width, $height, $background);
			$this->reset($width,$height,$columns,$rows);
			$photo_number=0;
			$photo_filenames = $this->buildArrayOfPhotoFilenames($category, $columns * $rows);
			foreach ($photo_filenames as $filename)
				$this->RenderPhotoFilenameIntoImage($filename,$image,$photo_number++);
			/*if ($caption)
				$this->addCaptionToImage($caption, $image);*/
			$filename = $this->findMontageImageFilename();
			$this->RenderToFilename($image,$filename);
			return $filename;
		}
		
		protected function buildArrayOfPostIdsWithPhotos($category,$number) {		// todo - build from the category specified
			$args = array(
				'numberposts'		=>	$number,
				'post_type'			=>	'post',
				'orderby'			=>	'rand',
			);
			if (is_numeric($category))
				$args['category'] = $category;
			else
				$args['category_name'] = $category;
				
			$posts_ar = get_posts( $args );
			$post_ids = array();
			foreach ($posts_ar as $post) {
				$post_ids[] = $post->ID;
			}
			return $post_ids;
		}
		protected function buildArrayOfPhotoFilenames($category,$number_required) {
			$filenames = array();
			$post_ids = $this->buildArrayOfPostIdsWithPhotos($category,$number_required);
			$photos_ar = array();
			$n = 0;
			foreach ($post_ids as $post_id) {
				
				$ar = array(
					'post_id'	=>	$post_id,
					'scan'		=>	true,
					'format'		=> 'array',
				);
				$image_ar = get_the_image($ar);
				
				if (isset($image_ar['src']))
					$filenames[]= $image_ar['src'];
				$n++;
				if ($n >= $number_required)
					break;
			}
			return $filenames;
		}
		
		protected function CreateOutputBitmap($width, $height, $background_color_hex = 'EAACB1') {	// default background is pink
			$image=imagecreatetruecolor($width,$height);
			$alpha=0;		
			extract($this->hex_to_rgb($background_color_hex));
			$background_color = imagecolorallocatealpha($image,$red,$green,$blue,$alpha);
			imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $background_color);
			return $image;
		}

		protected function ResizeImage($image,$source_width,$source_height,$dest_width,$dest_height) {
			$sized_image=imagecreatetruecolor($dest_width,$dest_height);
			imagecopyresized($sized_image,$image,0,0,0,0,$dest_width,$dest_height,$source_width,$source_height);
			imagedestroy($image);
			return $sized_image;
		}
		
		protected function AddImageBorder($image,$source_width,$source_height,$border_size,$border_color,$outer_border_rgb,$transparent_border=false) {
			$dest_width=$source_width+$border_size+$border_size;
			$dest_height=$source_height+$border_size+$border_size;
			
			$transparent_border = false;
			$debug = false; //true;
			
			if ($debug)
				print("AddImageBorder($image) border_color $border_color size $border_size<br />");

			$bordered_image=imagecreatetruecolor($dest_width,$dest_height);	


			$b=$border_color&0xff;
			$g=($border_color>>8)&0xff;
			$r=($border_color>>16)&0xff;
			if ($debug) {
				print("rgb=$r,$g,$b<br />");
				if ($transparent_border)
					print("Adding transparent_border<br />");
			}
			
			$background_color=imagecolorallocatealpha($bordered_image,$r,$g,$b,0);
			
			if ($transparent_border) {
				//$background_color = imagecolorallocatealpha($this->OurOutputImage, 255, 255, 0, 75);
				$background_color=imagecolorallocatealpha($this->OurOutputImage,$r,$g,$b,127);
				imagecolortransparent($bordered_image,$background_color);
			}

			imagefilledrectangle($bordered_image, 0, 0, $dest_width - 1, $dest_height - 1, $background_color);

			// copy the source image into the centre
			//imagecopy($bordered_image,$image,$border_size,$border_size,0,0,$source_width,$source_height);
			imagecopymerge($bordered_image,$image,$border_size,$border_size,0,0,$source_width,$source_height,100);
			//imagecopyresized($bordered_image,$image,$border_size,$border_size,0,0,$source_width,$source_height,$source_width,$source_height);
			imagedestroy($image);
			// finally draw the outer border
			if ($outer_border_rgb!=$border_color) {
				$b=$outer_border_rgb&0xff;
				$g=($outer_border_rgb>>8)&0xff;
				$r=($outer_border_rgb>>16)&0xff;
				$outer_border_color=imagecolorallocate($bordered_image,$r,$g,$b);
				imageline($bordered_image,0,0,$dest_width-1,0,$outer_border_color);
				imageline($bordered_image,$dest_width-1,0,$dest_width-1,$dest_height-1,$outer_border_color);
				imageline($bordered_image,$dest_width-1,$dest_height-1,0,$dest_height-1,$outer_border_color);
				imageline($bordered_image,0,$dest_height-1,0,0,$outer_border_color);
			}
			//imagepng($bordered_image,'tmp/bordered_image.png');
			return $bordered_image;
		}

		protected function FindPhotoPosition($photo_number) {
			$photos_per_line=$this->OurColumns;
			$photo_x_offset=-50;				// distance from edge of background
			$photo_x_displacement=$this->OurRenderWidth/($photos_per_line);		// distance between photos
			$photos_per_row=$this->OurRows;
			//$photo_y_offset=-50;				// distance from edge of background
			$photo_y_offset=-20;				// distance from edge of background
			$column=$photo_number%$photos_per_line;
			$row=(int)($photo_number/$photos_per_line);
			//print("Photo pos=$column,$row<br />");
			if ($photos_per_line>1)
				$photo_y_displacement=$this->OurRenderHeight/($photos_per_row);		// distance between photos
			else
				$photo_y_displacement=$this->OurRenderHeight;
			$photo_y_displacement -= 30;
			
			$x=(int)$column*$photo_x_displacement+$photo_x_offset;
			$y=(int)$row*$photo_y_displacement+$photo_y_offset;
			$dy=$photo_number%$photos_per_row;
			//print("Photo $photo_number @ $x,$y,dy=$dy<br />");
			return array($x,$y);
		}


		protected function findMontageImageFilename() {
			$rnd = rand(0,99999);
			$output_folder = $this->findOutputFolder();
			if (!file_exists($output_folder) && !is_dir($output_folder))
				mkdir($output_folder);
			return $output_folder.$rnd.'.png';
		}
		protected function createOutputFolder() {
			$output_folder = $this->findOutputFolder();
			if (!file_exists($output_folder) && !is_dir($output_folder))
				mkdir($output_folder);
		}
		protected function findOutputFolder() {
			return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
		}
		protected function RenderToFilename($image_resource,$filename) {
			ImagePng($image_resource,$filename);
			imagedestroy($image_resource);
		}
		/**
		 * Converts hexadecimal to RGB color array
		 * @link http://www.anyexample.com/programming/php/php_convert_rgb_from_to_html_hex_color.xml
		 * @uses hexdec http://php.net/manual/en/function.hexdec.php
		 * @access private
		 * @param string $color 6 or 3 character long hexadecimal code
		 * @return array with red, green, blue keys and corresponding values
		 */
		protected function hex_to_rgb($color) {
			 if($color[0] == '#')
				  $color = substr($color, 1);
		 
			 if(strlen($color) == 6) {
				  list($r, $g, $b) = array(
						$color[0].$color[1],
						$color[2].$color[3],
						$color[4].$color[5]
				  );
			 } elseif (strlen($color) == 3) {
				  list($r, $g, $b) = array(
						$color[0].$color[0],
						$color[1].$color[1],
						$color[2].$color[2]
				  );
			 } else {
				  return array('red' => 255, 'green' => 255, 'blue' => 255);
			 }
		 
			 $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
			 return array('red' => $r, 'green' => $g, 'blue' => $b);
		}

	
		protected function reset($width,$height,$columns,$rows) {
			$this->SetLayoutSize($width,$height,$columns,$rows);
			// Set some default colors
			$this->OurBackgroundColor=0xffffff;
			$this->OurMaskR=160;
			$this->OurMaskG=160;
			$this->OurMaskB=160;
			$this->OurMaskColor=0xa0a0a0;
		}
		
		protected function SetLayoutSize($width,$height,$columns,$rows) {
			$this->OurRenderWidth=$width;
			$this->OurRenderHeight=$height;
			$this->OurColumns=$columns;
			$this->OurRows=$rows;
			$this->CalculateBestPhotoSize();
		}
		protected function CalculateBestPhotoSize() {
			// now calculate photo sizes
			$this->OurBasePhotoWidth=$this->OurRenderWidth/($this->OurColumns);
			$this->OurPhotoBorderSize=(int)($this->OurBasePhotoWidth>>3);
			if ($this->OurBasePhotoWidth<10)
				$this->OurBasePhotoWidth=10;
		}
		
		protected function loadImage($filename) {
			// convert relative to full filename by adding domain root file location
			$full_filename = $this->findImageFilename($filename);
			if (!file_exists($full_filename)) {
				echo 'File '.$full_filename.' does not exist<br />';
				return false;
			}
			// now create the image from the file
			$info = pathinfo($full_filename);
			$ext = strtolower($info['extension']);
			switch ($ext) {
				case 'jpg':
				case 'jpeg':
					return @imagecreatefromjpeg($full_filename);
				case 'png':
					return @imagecreatefrompng($full_filename);
				case 'gif':
					return @imagecreatefromgif($full_filename);
				case 'bmp':
					return @imagecreatefrombmp($full_filename);
				default:
					return null;
			}
		}
		
		protected function findImageFilename($filename) {
			// (http://twd.loc/wp-content/uploads/2013/08/FanshawBrookHomePage-150x150.png)
			// strip the domain
			//print("findImageFilename($filename)<br />");
			$image_path = $path = parse_url($filename, PHP_URL_PATH);
			//print("Found image_path $image_path<br />");
			$base_location = dirname(dirname(dirname(dirname(__FILE__))));
			//print("base_location = $base_location <br />");
			if (strpos($filename, $base_location)===false)
				return $base_location . $image_path;
			else
				return $filename;
		}
	
		/**
		 * @function RenderPhotoFilenameIntoImage
		 * Adds a new image to our montage
		 * - loads the image
		 * - resizes it to fit the montage
		 * - adds a white polaroid type border
		 * - adds a transparent border
		 * - rotates it
		 * - renders it to the montage
		 **/
		
		protected function RenderPhotoFilenameIntoImage($base_filename,$output_image,$photo_number) {		
			$filename = $this->findImageFilename($base_filename);
			$image = $this->loadImage($filename);
			if ($image) {
				$ok=true;
				
				// First choose a size for the image
				list($image_width,$image_height)=getimagesize($filename);
				//print("Loaded $filename : $image_width x $image_height<br />");
				// Resize down to our NB maybe we should do this last to preserve quality
				// choose the destination width and height
				$aspect_ratio=$image_height/$image_width;
				$delta=$this->OurBasePhotoWidth>>3;		// may need tweeking depending on size
				$dest_width=rand($this->OurBasePhotoWidth-$delta,$this->OurBasePhotoWidth+$delta);
				$dest_height=$dest_width*$aspect_ratio;
				$image=$this->ResizeImage($image,$image_width,$image_height,$dest_width,$dest_height);
				$image_width=$dest_width;	// reflect the new size
				$image_height=$dest_height;	// reflect the new size
				
				// add a white border so the image looks like a polaroid photo
				$image=$this->AddImageBorder($image,$image_width,$image_height,$this->OurPhotoBorderSize,0xffffff,0x222222);
				$image_width+=$this->OurPhotoBorderSize+$this->OurPhotoBorderSize;
				$image_height+=$this->OurPhotoBorderSize+$this->OurPhotoBorderSize;
				
				// now add an extra transparent border space for the rotation - NB this should be transparent
				$resize_border_size=40;	// may need tweeking depending on size
				$image_width+=$resize_border_size+$resize_border_size;
				$image_height+=$resize_border_size+$resize_border_size;
				$dest_width=$image_width;
				$dest_height=$image_height;
				// rotate it
				$angle=rand(-15,15);
				imagesavealpha($image,true);
				$bgcolor=imagecolorclosest($image,$this->OurMaskR,$this->OurMaskG,$this->OurMaskB);
				imagecolortransparent($image,$bgcolor);
				$rotated_image=imagerotate($image,$angle,$bgcolor);
				if ($rotated_image!==false) {
					$image=$rotated_image;
					imagesavealpha($image,true);
					$bgcolor=imagecolorclosest($image,$this->OurMaskR,$this->OurMaskG,$this->OurMaskB);
					imagecolortransparent($image,$bgcolor);
					imagesavealpha($image,true);
					$image_width=imagesx($image);
					$image_height=imagesy($image);
					//imagepng($image,'tmp/rotated_image'.$photo_number.'.png');
				}

				// choose a render position for it
				// NB we need to spread the images around evenly then randomize them slightly
				// so don't just choose a random pos
				$half_width=$dest_width>>1;
				$half_height=$dest_height>>1;
				list($dest_x,$dest_y) = $this->FindPhotoPosition($photo_number);

				// render it
				imagealphablending($image,1);
				imagealphablending($output_image,1);
				
				$bgcolor=imagecolorclosest($image,$this->OurMaskR,$this->OurMaskG,$this->OurMaskB);
				$this->OurBackgroundR=200;
				$this->OurBackgroundG=200;
				$this->OurBackgroundB=200;
				$gray=imagecolorclosest($image,$this->OurBackgroundR,$this->OurBackgroundG,$this->OurBackgroundB);
				imagecolortransparent($image,$bgcolor);						// no effect
				imagecolortransparent($output_image,$bgcolor);			

				imagecopy($output_image,$image,$dest_x,$dest_y,0,0,$image_width,$image_height);
				// and destroy the image
				imagedestroy($image);
			} else
				$ok=false;
			return $ok;
		}
		
		protected function deleteOldFiles() {
			$path = dirname(__FILE__). DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
			foreach (new DirectoryIterator($path) as $file) {
				if ((time()-filectime($path.$file)) > 86400) {  
					if (preg_match('/\.png$/i', $file))
						unlink($path.$file);
				}
			}
   	}
	}
}

	new WP_Photo_Montage();
