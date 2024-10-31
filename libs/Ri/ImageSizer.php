<?php

/**
 * Class to handle image resizing of public images
 */
class Ri_ImageSizer {

	/**
	 * @var string  The md5 part of the filename
	 */
	public $md5;
	
	/**
	 * @var string  The extension of the output file
	 */
	public $toExt;
	
	/**
	 *
	 * @var string  The extension of the input file
	 */
	public $fromExt;
	
	/**
	 * @var string  The method for resizing (pin, exact, or fit)
	 */
	public $resizeMethod;
	
	/**
	 * @var string  A number between 1 and 99 representing the amount of "zoom"
	 */
	public $cropPercent;
	
	/**
	 *
	 * @var string  A number representing the jpeg quality setting on save
	 */
	public $quality;
	
	/**
	 * @var int  The width boundary. 
	 *   For resizeType=pin or exact, the width to which to resize
	 *   For resizeType=fit, the max width
	 */
	public $boundingWidth;
	
	/**
	 * @var int  The height boundary
	 *   For resizeType=pin, always 0
	 *   For resizeType=exact, the height to which to resize
	 *   For resizeType=fit, the max height
	 */
	public $boundingHeight;
	
	public $density;
	
	/**
	 * @var int  The jpeg quality setting to use (75 means 25% compression)
	 */
	public $defaultQuality = 75;
	
	/**
	 * @param string|array|object [$spec]  Parameters for the sizing. 
	 * e.g. "crop-10.exact-200x200.jpg" means crop 10% and fit to exactly 200x200 with destination format jpg
	 */
	public function __construct($spec = null) {
		if ($spec) {
			$this->setSpec($spec);
		}
	}
	
	/**
	 * Factory Method
	 * @param string|array|object [$spec]  See __construct
	 * @return \self
	 */
	public static function init($spec = null) {
		return new self($spec);
	}
	
	/**
	 * Set the spec from a string
	 * @param string $specString  Build specs based on a string such as "pin-500.quality-75.jpg"
	 * @return \self
	 */
	public function parse($specString) {
		$filename = pathinfo($specString, PATHINFO_BASENAME);
		$density = 1;
		preg_replace_callback('/@([\d+.])x/', function($match) {
			$density = (float) $match[1];
			return '';
		}, $filename);
		$parts = explode('.', $filename);
		if (preg_match('/^[0-9a-f]{32}$/', $parts[0])) {
			$this->md5 = array_shift($parts);
		}
		else {
			$this->md5 = '';
		}
		if (preg_match('/^(jpe?g|gif|png)$/i', $parts[count($parts)-1])) {			
			$this->toExt = array_pop($parts);
		}
		else {
			$this->toExt = 'jpg';
		}
		$this->scaleup = 'off';
		$this->fromExt = $this->toExt;
		$this->resizeMethod = 'original';
		$this->cropPercent = 0;
		$this->quality = false;
		foreach ($parts as $part) {
			if (preg_match('/^fit-(\d+)x(\d+)$/', $part, $match)) {
				// fit within bounds of area $1x$2
				$this->resizeMethod = 'fit';
				$this->boundingWidth = (int) $match[1];
				$this->boundingHeight = (int) $match[2];
			}
			elseif (preg_match('/^exact-(\d+)x(\d+)$/', $part, $match)) {
				// crop and resize to area $1x$2
				$this->resizeMethod = 'exact';
				$this->boundingWidth = (int) $match[1];
				$this->boundingHeight = (int) $match[2];
			}
			elseif (preg_match('/^pin-(\d+)$/', $part, $match) && (int) $match[1] > 0) {
				// size so that width is $1 (upscale or downscale)
				$this->resizeMethod = 'pin';
				$this->boundingWidth = (int) $match[1];
			}
			elseif (preg_match('/^scaleup-(on|off)$/', $part, $match)) {
				// scale up image if needed
				$this->scaleup = $match[1];
			}
			elseif ($part == 'original') {
				$this->resizeMethod = 'original';
			}
			elseif (preg_match('/^crop-(\d+)$/', $part, $match) && (int) $match[1] > 0 && (int) $match[1] < 100) {
				$this->cropPercent = (int) $match[1];
			}
			elseif (preg_match('/^quality-(\d+)$/', $part, $match) && (int) $match[1] >= 0 && (int) $match[1] <= 100) {
				$this->quality = (int) $match[1];
			}
			elseif (preg_match('/^(gif|jpe?g|png)$/i', $part)) {
				$this->fromExt = $part;
			}
		}
		$this->density = $density;
		return $this;
	}
	
	/**
	 * Set the spec from a string, array, or object
	 * @param type $spec  see __construct()
	 * @return \self
	 */
	public function setSpec($spec) {
		if (is_string($spec)) {
			$this->parse($spec);
		}
		elseif (is_array($spec) || is_object($spec)) {
			foreach ((array) $spec as $k => $v) {
				$this->$k = $v;
			}
		}
		return $this;
	}
	
	/**
	 * Calculate the new size of the image
	 * @param int $src_w  The width of the source image
	 * @param int $src_h  The height of the source image
	 * @return array  With keys "width" and "height"
	 */
	public function getDestSize($src_w, $src_h) {
		if ($this->resizeMethod == 'original') {
			return array(
				'width'=>$src_w,
				'height'=>$src_h
			);
		}
		$params = $this->getGdParams($src_w, $src_h);
		return array(
			'width'=>$params['new_w'],
			'height'=>$params['new_h'],
		);
	}
	
	/**
	 * Used internally to get parameters to pass to imagecopyresampled().
	 * This function calls getGdParamsFit(), getGdParamsExact(), or getGdParamsPin() depending on $this->resizeMethod
	 * @param int $src_w  The width of the source image
	 * @param int $src_h  The height of the source image
	 * @return array  With keys 'dst_x','dst_y','src_x','src_y','dst_w','dst_h','src_w','src_h','new_w','new_h'
	 */
	public function getGdParams($src_w, $src_h) {
		if (!$this->resizeMethod) {
			return null;
		}
		$method = 'getGdParams' . ucwords($this->resizeMethod);
		$params = $this->$method($src_w, $src_h);
		return $params;
	}
	
	/**
	 * Get parameters for imagecopyresampled() by fitting the image in the bounding box
	 * defined by $this->boundingWidth x $this->boundingHeight.
	 * @param int $src_w  The width of the source image
	 * @param int $src_h  The height of the source image
	 * @return array  With keys 'dst_x','dst_y','src_x','src_y','dst_w','dst_h','src_w','src_h','new_w','new_h'
	 */
	public function getGdParamsFit($src_w, $src_h) {
		$dst_x = 0;
		$dst_y = 0;
		$src_x = 0;
		$src_y = 0;
		if ($this->boundingHeight > $src_h && $this->boundingWidth > $src_w && $this->scaleup == 'off') {
			// don't enlarge small images
			$dst_w = $src_w;
			$dst_h = $src_h;
			$new_w = $dst_w;
			$new_h = $dst_h;
		}
		else {
			$xscale = $src_w / $this->boundingWidth;
			$yscale = $src_h / $this->boundingHeight;
			$divisor = max($xscale, $yscale);		
			$dst_w = round($src_w * (1 / $divisor));
			$dst_h = round($src_h * (1 / $divisor));
			if ($this->cropPercent) {
				// zoom in a little bit
				$src_x = round($src_w * $this->cropPercent / 200);
				$src_y = round($src_h * $this->cropPercent / 200);
				$src_w -= ($src_x * 2);
				$src_h -= ($src_y * 2);
				if ($src_h <= $this->boundingHeight && $dst_w <= $this->boundingWidth) {
					// zoom will cause pixelation so abort zooming
					$src_w += ($src_x * 2);
					$src_h += ($src_y * 2);
					$src_x = 0;
					$src_y = 0;
				}
			}
			$new_w = $dst_w;
			$new_h = $dst_h;		
		}
		return compact('dst_x','dst_y','src_x','src_y','dst_w','dst_h','src_w','src_h','new_w','new_h');		
	}
	
	/**
	 * Get parameters for imagecopyresampled() by forcing the image to be exactly
	 * $this->boundingWidth x $this->boundingHeight. Left/Right or Top/Bottom is cropped out
	 * @param int $src_w  The width of the source image
	 * @param int $src_h  The height of the source image
	 * @return array  With keys 'dst_x','dst_y','src_x','src_y','dst_w','dst_h','src_w','src_h','new_w','new_h'
	 */
	public function getGdParamsExact($src_w, $src_h) {
		$src_x = 0;
		$src_y = 0;
		$srcRatio = $src_w / $src_h;
		$dstRatio = $this->boundingWidth / $this->boundingHeight;
		if ($srcRatio > $dstRatio) {
			// source is more landscape than destination: crop left and right
			// run through fit-###x### first in case we have cropPercent
			$fit = clone $this;
			$fit->resizeMethod = 'fit';
			$fit->boundingWidth = round($this->boundingHeight * $srcRatio);
			$fit->scaleup = 'on';
			$params = $fit->getGdParams($src_w, $src_h);
			extract($params);
			// then update dst_x to be negative
			$dst_x = round(($this->boundingWidth - $dst_w) / 2);			
		}
		else {
			// source is more portrait than destination: crop top and bottom
			// run through fit-###x### first in case we have cropPercent
			$fit = clone $this;
			$fit->resizeMethod = 'fit';
			$fit->boundingHeight = round($this->boundingWidth / $srcRatio);
			$fit->scaleup = 'on';
			$params = $fit->getGdParams($src_w, $src_h);
			extract($params);
			// then update dst_y to be positive
			$dst_y = round(($this->boundingHeight - $dst_h) / 2);
		}
		// upscale if needed
		$new_w = $this->boundingWidth;
		$new_h = $this->boundingHeight;
		return compact('dst_x','dst_y','src_x','src_y','dst_w','dst_h','src_w','src_h','new_w','new_h');		
	}
	
	/**
	 * Get parameters for imagecopyresampled() by fitting the image to the width
	 * defined by $this->boundingWidth. Produces Pinterest style images with fixed width and variable height
	 * @param int $src_w  The width of the source image
	 * @param int $src_h  The height of the source image
	 * @return array  With keys 'dst_x','dst_y','src_x','src_y','dst_w','dst_h','src_w','src_h','new_w','new_h'
	 */	
	public function getGdParamsPin($src_w, $src_h) {
		$dst_x = 0;
		$dst_y = 0;
		$src_x = 0;
		$src_y = 0;
		$divisor = $src_w / $this->boundingWidth;
		$dst_w = $this->boundingWidth;
		$dst_h = round($src_h * (1 / $divisor));
		// $this->cropPercent not supported
		$new_w = $dst_w;
		$new_h = $dst_h;
		return compact('dst_x','dst_y','src_x','src_y','dst_w','dst_h','src_w','src_h','new_w','new_h');		
	}
	
	/**
	 * Build and return the destination filename with crop-##, pin-###x###, quality-## etc
	 * @return string
	 */
	public function getDestFilename() {
		$parts = array();
		if ($this->md5) {
			$parts[] = $this->md5;
		}
		if ($this->resizeMethod == 'original') {
			$parts[] = $this->fromExt;
		}
		else {
			if ($this->fromExt != $this->toExt) {
				$parts[] = $this->fromExt;
			}
			if ($this->scaleup == 'on') {
				$parts[] = 'scaleup-on';
			}
			if ($this->cropPercent > 0 && $this->cropPercent < 100) {
				// integer between 1 and 99
				$parts[] = 'crop-' . $this->cropPercent;
			}
			if ($this->resizeMethod == 'pin') {
				$parts[] = 'pin-' . $this->boundingWidth;
			}
			elseif (in_array($this->resizeMethod, array('fit','exact'))) {
				$parts[] = $this->resizeMethod . '-' . $this->boundingWidth . 'x' . $this->boundingHeight;
			}
			if (is_numeric($this->quality)) {
				$parts[] = 'quality-' . $this->quality;
			}
			$parts[] = $this->toExt;
		}
		return join('.', $parts);
	}	
	
	public function __toString() {
		return $this->getDestFilename();
	}	
	
	public static function img($spec, $article, $alt = '') {
		$sizer = static::init($spec);
		$url = $article->source_image->url . '.' . $sizer->getDestFilename();
		$size = $sizer->getDestSize($article->source_image->width, $article->source_image->height);
		return sprintf(
			'<img alt="%s" src="%s" width="%s" height="%s" />',
			esc_html($alt),
			esc_html($url),
			$size['width'],
			$size['height']
		);
	}
	
}