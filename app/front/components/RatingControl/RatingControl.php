<?php

namespace FrontModule\Controls;

use Tulinkry\Application\UI\Control;
use Tulinkry;
use Forms;
use Model;
use Nette;


class RatingControl extends Control
{
	const MIN = 0;
	const MAX = 1;

	private $colorIntervals = array ( 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0 );
	private $colorClasses   = array ( "rating-1", "rating-2", "rating-3", "rating-4",
									  "rating-5", "rating-6", "rating-7", "rating-8", 
									  "rating-9", "rating-10");
	private $colorHex 		= array ();

	private $usePercent;
	private $helper;


	public function __construct ( $usePercent = false )
	{
		parent::__construct ();

		$this->usePercent = $usePercent;		
		$this->helper = new StarRatingHelper;
	}

	protected function getRatio ( $args )
	{
		if ( count ( $args ) != 1 )
			throw new \InvalidArgumentException ( sprintf ( "RatingControl: ratio is required" ) );

		if ( ! ( $ratio = array_shift ( $args ) ) || ! array_key_exists ( "ratio", $ratio ) )
			throw new \InvalidArgumentException ( sprintf ( "RatingControl: ratio is required" ) );

		$ratio = $ratio [ "ratio" ];
		return $ratio;
	}

	protected function calculateColorHex ( $ratio )
	{
		return "#000000";
	}

	protected function calculateColorClass ( $ratio )
	{
		foreach ( $this -> colorIntervals as $key => $interval )
			if ( $ratio <= $interval )
				return $this -> colorClasses [ $key ];
		return "rating-1";
	}

	public function generateHtml ( $ratio )
	{
		foreach ( $this -> colorIntervals as $key => $interval )
			if ( $ratio <= $interval )
			{

				return $html;		
			}
		return "";
	}

	public function renderNumber ()
	{	
		$ratio = $this -> getRatio ( func_get_args () );

		$this -> template -> setFile ( __DIR__  . "/ratingControl.latte" );
		
		if ($this->usePercent)
			$ratio /= 100;
		
		$this -> helper = new NumberRatingHelper;
		$this -> helper -> setRatio ( $ratio );

		$this -> template -> helper = $this -> helper;

		$this -> template -> render ();
	}

	public function renderStarNumber ()
	{	
		$ratio = $this -> getRatio ( func_get_args () );

		$this -> template -> setFile ( __DIR__  . "/ratingControl.latte" );
		
		if ($this->usePercent)
			$ratio /= 100;
		
		$this -> helper = new StarNumberRatingHelper;
		$this -> helper -> setRatio ( $ratio );

		$this -> template -> helper = $this -> helper;

		$this -> template -> render ();
	}

	public function render ()
	{	
		$ratio = $this -> getRatio ( func_get_args () );

		$this -> template -> setFile ( __DIR__  . "/ratingControl.latte" );

		$this -> template -> colorHex = $this -> calculateColorHex ( $ratio );
		$this -> template -> colorClass = $this -> calculateColorClass ( $ratio );
		
		if ($this->usePercent)
			$ratio /= 100;
		

		$this -> helper = new StarRatingHelper;
		$this -> helper -> setRatio ( $ratio );
		$this -> helper -> setOptions ( [ "basePath" => $this -> presenter -> template -> basePath ] );

		$this -> template -> helper = $this -> helper;

		$this -> template -> render ();
	}

}