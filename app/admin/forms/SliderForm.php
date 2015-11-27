<?php

namespace AdminModule\Forms;


use Tulinkry\Forms;
use Model;
use Nette\Utils\Html;
use Nette\Utils\Image;
use Tulinkry\Utils\Strings;

class SliderForm extends Forms\Form
{
	private $slider;

	public function __construct ( $slider )
	{
		parent::__construct ();
		$this -> slider = $slider;

		$this -> addMultiUpload ( "images", "Obrázky" );

		$this -> addSubmit ( "submit", "Přidat" );  

	}

	public function process ( $form )
	{

		if ( ! $this -> presenter -> user -> isAllowed ( 'backend' ) )
		{
            $this->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
            $this->redirect(":Front:Pub:default");
			return;
		}

		set_time_limit ( 0 ); // long operations

		$values = $form -> values;


		$error = false;
		foreach ( $values [ "images" ] as $key => $file )
		{
			if ( $file -> isOk () && $file -> isImage () )
			{
				$img = Image::fromFile ( $file -> temporaryFile );
				if ( $img -> width < 1200 || $img -> height < 800 )
				{
					$this -> presenter -> flashMessage ( sprintf ( "Obrázek '%s' je příliš malý. Nejmenší rozměry jsou %dx%dpx.", $file -> name, 1200, 800 ),
														 "error" );
					$error = true;
					continue;
				}

				if ( ! $this -> save ( $img ) )
				{
					$this -> presenter -> flashMessage ( sprintf ( "Obrázek '%s' je buď poškozený nebo se nejedná o obrázek.", $file -> name ),
														 "error" );
					$error = true;
					continue;
					
				}
				$this -> presenter -> flashMessage ( sprintf ( "Obrázek '%s' byl uložen", $file -> name ), "success" );
			}
			else
			{
				$this -> presenter -> flashMessage ( sprintf ( "Obrázek '%s' je buď poškozený nebo se nejedná o obrázek.", $file -> name ), 
													 "error" );
				$error = true;
				continue;
			}
		}
		
		if ( ! $error )
		{
			$this -> presenter -> flashMessage ( "Import proběhl v pořádku" );
			$this -> presenter -> redirect ( "default" );
		}
	}


	protected function save ( Image $img )
	{
		if ( ! $img )
			return false;

		$dirname = WWW_DIR . "/" . $this -> slider [ "sliderSrc" ];

		if ( ! file_exists( $dirname ) )
			mkdir ( $dirname, 0777, true );

		foreach ( [ "lg", "sm", "md", "xs" ] as $dir )
			if ( ! file_exists( $dirname . "/" . $this -> slider [ "slider" ] [ $dir ] ) )
				mkdir ( $dirname . "/" . $this -> slider [ "slider" ] [ $dir ], 0777, true );


		do {
			$name = Strings::random(10) . ".jpg";
		} while (file_exists($dirname . "/" . $name));

		$return_value = $img -> save ( $dirname . "/" . $this -> slider [ "slider" ] [ "lg" ] . "/" . $name, 80, Image::JPEG );

		$return_value = $return_value && $img -> resize ( '1200px', null ) 
											  -> save ( $dirname . "/" . $this -> slider [ "slider" ] [ "md" ] . "/" . $name, 94, Image::JPEG );
		$return_value = $return_value && $img -> resize ( '900px', null ) 
											  -> save ( $dirname . "/" . $this -> slider [ "slider" ] [ "sm" ] . "/" . $name, 94, Image::JPEG );
		$return_value = $return_value && $img -> resize ( '600px', null ) 
											  -> save ( $dirname . "/" . $this -> slider [ "slider" ] [ "xs" ] . "/" . $name, 94, Image::JPEG );

		if ( ! $return_value )
		{
			foreach ( [ $dirname . "/" . $this -> slider [ "slider" ] [ "lg" ] . "/" . $name,
						$dirname . "/" . $this -> slider [ "slider" ] [ "md" ] . "/" . $name,
						$dirname . "/" . $this -> slider [ "slider" ] [ "sm" ] . "/" . $name,
						$dirname . "/" . $this -> slider [ "slider" ] [ "xs" ] . "/" . $name ] as $image )
				if ( file_exists( $image ) )
					@unlink ( $image );
		}

		return $return_value;		
	}
}
