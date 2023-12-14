<?php

class sections_colorize {

	var $__devices = array(
		'print'=> array(
			//'width'=>400,
			//'height'=>400,
			//'border_top'=>0,
			//'border_bottom'=>0,
			//'border_left'=>0,
			//'border_right'=>0,
			//'border_color'=>'white',
			//'layer_width'=>570,
			//'layer_height'=>600,
			//'layer_x'=>0,
			//'layer_y'=>0,
			'clut_gradient'=>array(
				array('pct'=>0, 'color'=>0),
				array('pct'=>11, 'color'=>0),
				array('pct'=>82, 'color'=>1),
				array('pct'=>100, 'color'=>1),
			),
			/*'clut_colors'=>array(
				'rgba(204, 157, 91, 1.0)',
				'rgba(0, 0, 0, 1.0)',
			),*/
		),
		'mobile'=> array(
			'width'=>400,
			'height'=>400,
			//'border_top'=>0,
			//'border_bottom'=>0,
			//'border_left'=>0,
			//'border_right'=>0,
			//'border_color'=>'white',
			'layer_width'=>400,
			'layer_height'=>400,
			'layer_x'=>0,
			'layer_y'=>0,
			'clut_gradient'=>array(
				array('pct'=>0, 'color'=>0),
				array('pct'=>11, 'color'=>0),
				array('pct'=>82, 'color'=>1),
				array('pct'=>100, 'color'=>1),
			),
			/*'clut_colors'=>array(
				'rgba(204, 157, 91, 1.0)',
				'rgba(0, 0, 0, 1.0)',
			),*/
		),
		'desktop'=> array(
			'width'=>1920, //1920
			'height'=>600, //600
			//'border_top'=>0,
			//'border_bottom'=>90,
			//'border_left'=>90,
			//'border_right'=>90,
			//'border_color'=>'white',
			//'layer_width'=>590,
			//'layer_height'=>680,
			//'layer_x'=>0,
			//'layer_y'=>0,
			'clut_gradient'=>array(
				array('pct'=>0, 'color'=>0),
				array('pct'=>11, 'color'=>0),
				array('pct'=>82, 'color'=>1),
				array('pct'=>100, 'color'=>1),
			),
			/*'clut_colors'=>array(
				'rgba(204, 157, 91, 1.0)',
				'rgba(0, 0, 0, 1.0)',
			),*/
		),
	);

	public function device($device, $var) {
		if (array_key_exists($var, $this->__devices[$device]))
			return $this->__devices[$device][$var];
		return None;
	}

	public function deviceList() {
		return array_keys($this->__devices);
	}

	/**
	 * Resizes and crops $image to fit provided $width and $height.
	 *
	 * @param \Imagick $image
	 *   Image to change.
	 * @param int $width
	 *   New desired width.
	 * @param int $height
	 *   New desired height.
	 */
	public function imageCover(Imagick $image, $width, $height) {
	  $ratio = $width / $height;

	  // Original image dimensions.
	  $old_width = $image->getImageWidth();
	  $old_height = $image->getImageHeight();
	  $old_ratio = $old_width / $old_height;

	  // Determine new image dimensions to scale to.
	  // Also determine cropping coordinates.
	  if ($ratio > $old_ratio) {
	    $new_width = $width;
	    $new_height = $width / $old_width * $old_height;
	    $crop_x = 0;
	    $crop_y = intval(($new_height - $height) / 2);
	  }
	  else {
	    $new_width = $height / $old_height * $old_width;
	    $new_height = $height;
	    $crop_x = intval(($new_width - $width) / 2);
	    $crop_y = 0;
	  }

	  // Scale image to fit minimal of provided dimensions.
	  $image->resizeImage($new_width, $new_height, \Imagick::FILTER_LANCZOS, 0.9, true);

	  // Now crop image to exactly fit provided dimensions.
	  $image->cropImage($new_width, $new_height, $crop_x, $crop_y);
	}

	public function apply_colorize($file_path, $file_url, $rgbacolors) {
		$tmp = explode('/', $file_url);
		array_pop($tmp);
		$baseurl = implode('/', $tmp);

		$data = array();
		$path_parts = pathinfo($file_path);
		$basepath = $path_parts['dirname'];
		$basename = $path_parts['filename'];
		$imBase = $this->load($file_path);
		foreach($this->deviceList() as $device) {
			$width = $this->device($device, 'width');
			$height = $this->device($device, 'height');

			$layer_width = $this->device($device, 'layer_width');
			$layer_height = $this->device($device, 'layer_height');
			$layer_x = $this->device($device, 'layer_x');
			$layer_y = $this->device($device, 'layer_y');

			//$border_top = $this->device($device, 'border_top');
			//$border_bottom = $this->device($device, 'border_bottom');
			//$border_left = $this->device($device, 'border_left');
			//$border_right = $this->device($device, 'border_right');
			//$border_color = $this->device($device, 'border_color');

			// Strat
			$im = clone $imBase;

			// Set compression
			$im->setImageCompression(\Imagick::COMPRESSION_JPEG);
			$im->setImageCompressionQuality(85);

			//Strips an image of all profiles and comments
			$im->stripImage();

			//Configure la compression de l'image
			$im->setInterlaceScheme(\Imagick::INTERLACE_PLANE);


			// #1 / Resize image at W H desired

			// Create thumbnail max of $width x $height
			/*$im_width=$im->getImageWidth();
			if ($im_width > $width)
			{
			    $im->thumbnailImage($width,null,0);
			}

			//now check height
			$im_height=$im->getImageHeight();
			if ($im_height > $height)
			{
			    $im->thumbnailImage(null,$height);
			}*/


			// 2# / Equivalent : Resize image at W H desired
			//$im->scaleImage($width, $height, true);
			//$im->setImageExtent($width, $height);

			// #3 / Resize image then canavs size at W H desire
			$background = preg_match('/\.gif$|\.png$/', $file_url) == 1 ? 'None' : 'white';
      if (!$width && !$height)
				$im->scaleImage($width, $height, true);
			$im->setImageBackgroundColor($background);
			$w = $im->getImageWidth();
			$h = $im->getImageHeight();
			if (!$width && !$height)
				$im->extentImage($width, $height,($w-$width)/2,($h-$height)/2);


			// Contrôle la saturation, l'intensité et la teinte
			//$im->evaluateImage($evaluateType = 'Add', 0.5, 'white', 'white');
			$im->modulateImage(100,0,100);
			//$im->levelImage(0,1,100); // Bug
			//$im->levelImage(30000, 1, 65535); //65535

			// Create colors
			$clut = $this->createClut($device, $rgbacolors);
			$im->clutImage($clut);

			$fn = $basename.'-colorized-'.$device.'.png';
			$im->writeImage($basepath."/".$fn);
			unset($im);

			if ( $device != 'print' ) {
				$im = clone $imBase;
				$im->setImageCompression(\Imagick::COMPRESSION_JPEG);
				$im->setImageCompressionQuality(85);
				$im->stripImage();
				$im->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
				// Resize and crop
				//$im->cropThumbnailImage($width, $height);
				if (!$width && !$height)
					$im->cropThumbnailImage($width, $height);
				if (!$layer_width && !$layer_height)
					$im->cropImage($layer_width, $layer_height, $layer_x, $layer_y);

				// Normalize levels
				$im->autoLevelImage();

				//
				$im->modulateImage(100,0,100);
				//$im->levelImage(0,1,100); // Bug
				//$im->levelImage(30000, 1, 65535); //65535

				// Create colors
				$clut = $this->createClut($device, $rgbacolors);
				$im->clutImage($clut);

				$fnly = $basename.'-colorized-scaled-'.$device.'.jpg';
				$im->writeImage($basepath."/".$fnly);
				unset($im);
			}



			//ex 1
			/*$im->setImageCompression(\Imagick::COMPRESSION_JPEG);
			$im->setImageCompressionQuality(85);
			$im->stripImage();
			$im->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
			$im->modulateImage(100,0,100);
			$im->cropThumbnailImage($width, $height);
			$im->cropImage($layer_width, $layer_height, $layer_x, $layer_y);
			$clut = $this->createClut($device);
			$im->clutImage($clut);
			$fnly = $basename.'-layer-'.$device.'.jpg';
			$im->writeImage($basepath."/".$fnly);
			unset($im);*/


			//ex 2

/*			$im = clone $imBase;
			$im->setImageCompression(\Imagick::COMPRESSION_JPEG);
			$im->setImageCompressionQuality(85);
			$im->stripImage();
			$im->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
			$im->cropThumbnailImage($width, $height);
			//$draw = new ImagickDraw();
		//	$draw->setStrokeColor( new ImagickPixel( $border_color ) );
			//$draw->setFillColor( new ImagickPixel( $border_color ) );
			/*if ($border_top)
				$draw->rectangle(0, 0, $width, $border_top);
			if ($border_bottom)
				$draw->rectangle(0, $height - $border_bottom, $width, $height);
			if ($border_left)
				$draw->rectangle(0, 0, $border_left,  $height);
			if ($border_right)
				$draw->rectangle($width - $border_right, 0, $width,  $height);

			$im->drawImage($draw);*/
			//$fnbg = $basename.'-l-'.$device.'.jpg';
			//$im->writeImage($basepath."/".$fnbg);
			//unset($im);`


//			$clut->writeImage('clut.png');
			if ( $device != 'print' ) {
				$data[$device] = array(
					'colorized'=>array(
						'path'=>$basepath."/".$fn,
						'url'=>$baseurl."/".$fn,
						'width'=>$width,
						'height'=>$height
					),
					'colorized-scaled'=>array(
						'path'=>$basepath."/".$fnly,
						'url'=>$baseurl."/".$fnly,
						'width'=>$layer_width,
						'height'=>$layer_height,
						'x'=>$layer_x,
						'y'=>$layer_y
					),
				);
			} else {
				$data[$device] = array(
					'colorized'=>array(
						'path'=>$basepath."/".$fn,
						'url'=>$baseurl."/".$fn,
						'width'=>$width,
						'height'=>$height
					),
				);
			}
		}
		return $data;
	}

	public function load($file_path) {
		$im = new Imagick($file_path);
		return $im;
	}

	public function createClut($device, $colors) {
		$im = new Imagick();
		$gradients = $this->device($device, 'clut_gradient');
		//$colors = $this->device($device, 'clut_colors');

		for($idx = 0; $idx < count($gradients) - 1; $idx++) {
			$color1 = $colors[$gradients[$idx]['color']];
			$color2 = $colors[$gradients[$idx + 1]['color']];
			$pct1 = $gradients[$idx]['pct'];
			$pct2 = $gradients[$idx + 1]['pct'];
			$im->newPseudoImage(100,($pct2 - $pct1) * 4, "gradient:$color1-$color2");
		}
		$im->resetIterator();
		$clut = $im->appendImages(true);
		return $clut;
	}

	public function resize($im, $w, $h) {
	}

/*	public function show($data) {
?>
<?php
print "<pre>";
print_r($data);
print "</pre>";
foreach($this->deviceList() as $device) {
	$width = $data[$device]['background']['width'];
	$height = $data[$device]['background']['height'];
	$layer_width = $data[$device]['layer']['width'];
	$layer_height = $data[$device]['layer']['height'];
	$layer_x = $data[$device]['layer']['x'];
	$layer_y = $data[$device]['layer']['y'];
	$background_url = $data[$device]['background']['url'];
	$layer_url = $data[$device]['layer']['url'];
	$background_path = $data[$device]['background']['path'];
	$layer_path = $data[$device]['layer']['path'];
	echo "<div style=\"width: ${width}px; height: ${height}px; position: relative;\">";
	echo "<img src=\"".$data[$device]['background']['url']."\" style=\"position: relative; top: 0px; left: 0px;\"><img src=\"".$data[$device]['layer']['url']."\" style=\"position: absolute; top: ${layer_x}px; left: ${layer_y}px;\">";
	echo "</div>";
}
?>
<?php
	}*/

} // Fin class

/*
if (isset($argv) && $argv && $argv[0] && realpath($argv[0]) === __FILE__) {
	$ajc = new com_artetjardins_hdf_colorize();
	$ajc->apply_colorize($argv[1]);
//	$clut = $ojc->createClut();

} else {
	$path = "./FIJ_Hortillonnages_Amiens___Florent_&_Grégory_Morisseau_©_Yann_Monel_2018.jpg";
	$url = "https://www.artetjardins-hdf.com/tmp/com_artetjardins-hdf_colorize/FIJ_Hortillonnages_Amiens___Florent_&_Grégory_Morisseau_©_Yann_Monel_2018.jpg";
//	$url = "./IJ_Hortillonnages_Amiens___Florent_&_Grégory_Morisseau_©_Yann_Monel_2018.jpg";
	$ajc = new com_artetjardins_hdf_colorize();
	$data = $ajc->apply_colorize($path, $url);
	$ajc->show($data);
}
*/
