<?php

namespace App\Presentation\Accessory;

use Latte;
use Nette\Utils\Image;

abstract class RatingHelper
{
	public const MIN = 0;
	public const MAX = 1;

	protected $ratio;
	protected $options = array();

	public function __construct()
	{
	}

	public function setRatio($ratio)
	{
		$this->ratio = $ratio;
		return $this;
	}

	public function setOptions($options)
	{
		if (!is_array($options)) {
			throw new \InvalidArgumentException("RatingHelper: expecting an array.");
		}
		$this->options = $options;
		return $this;
	}

	public function __toString()
	{
		return $this->ratio;
	}
}

class NumberRatingHelper extends RatingHelper
{
	public function __toString()
	{
		if ($this->ratio == 0) {
			return "";
		}
		return round($this->ratio * 100, 2) . "%";
	}
}

class StarRatingHelper extends RatingHelper
{
	public const IMAGE_PATH = "images/stars/star.png";
	public const GREY_IMAGE_PATH = "images/stars/star_grey.png";

	private function calculateNumberOfStars()
	{
		$intervals = array( 1 => RatingHelper::MAX / 5,
							 2 => RatingHelper::MAX * 2 / 5,
							 3 => RatingHelper::MAX * 3 / 5,
							 4 => RatingHelper::MAX * 4 / 5,
							 5 => RatingHelper::MAX );
		return array_find_key($intervals, fn ($interval) => $this->ratio <= $interval) ?? 0;
	}

	private function isPartial()
	{
		return false;
	}

	public function __toString()
	{
		if ($this->ratio == 0) {
			return "nehodnoceno";
		}

		$params = array_merge($this->options, [ "image_normal" => self::IMAGE_PATH,
													"image_grey" => self::GREY_IMAGE_PATH,
													"max" => 5,
													"count" => $this->calculateNumberOfStars(),
													"partial" => $this->isPartial(),
													"percent" => round($this->ratio * 100, 2) . "%" ]);
		$latte = new Latte\Engine();
		$str = $latte->renderToString(__DIR__ . "/starRatingHelperTemplate.latte", $params);
		return $str;
	}
}


class StarNumberRatingHelper extends RatingHelper
{
	//<img src='data:image/png;base64,".base64_encode($contents)."' />
	public const IMAGE_PATH = "images/stars/star.png";

	private function calculateNumber()
	{
		return round($this->ratio * 100, 0);
	}

	private function isPartial()
	{
		return false;
	}

	public function __toString()
	{
		if ($this->ratio == 0) {
			return "nehodnoceno";
		}

		$string = (string)$this->calculateNumber();
		$string .= "%";

		$image = Image::fromFile(WWW_DIR . "/" . self::IMAGE_PATH);
		$image->string($font = 4,
			$image->width / 2 - strlen($string) * $font,
			$image->height / 2 - $font,
			$string,
			$image->colorAllocate($r = 0, $g = 0, $b = 0));

		$strImage = (string) $image;

		$params = array_merge($this->options, [ "image" => base64_encode($strImage) ]);
		$latte = new Latte\Engine();
		$str = $latte->renderToString(__DIR__ . "/starNumberRatingHelperTemplate.latte", $params);
		return $str;
	}
}
