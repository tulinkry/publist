<?php

namespace FrontModule\Forms;


use Tulinkry\Forms;
use Model;
use Nette\Utils\Html;
use Nette\Utils\Image;


class ImageForm extends Forms\Form
{
	protected $model;


	public function __construct ( $model, $pub )
	{
		parent::__construct ();

		$this -> model = $model;

		$this -> addHidden ( "id", $pub -> id );

		$this -> addMultiUpload ( "images1", "Obrázky" );
		$this -> addMultiUpload ( "images2", "Obrázky" );
		$this -> addMultiUpload ( "images3", "Obrázky" );

		$this -> addSubmit ( "submit", "Přidat" );  
	}

	public function process ( $form )
	{
		if ( ! $this -> presenter -> user -> isAllowed ( 'frontend' ) )
		{
			$form -> presenter -> flashMessage ( "Na hodnocení musíte být přihlášeni.", "warning" );
			$this -> presenter -> redirectLogin ();
			return;
		}

		set_time_limit ( 0 ); // long operations

		$values = $form -> getValues(TRUE);

		if ( ! ( $entity = $this -> model -> item ( $values [ "id" ] ) ) )
		{
			$this -> presenter -> flashMessage ( sprintf ( "Restaurace [%d] neexistuje.", $values [ "id" ] ), "error" );
			return;
		}

		$values["images"] = array ();
		foreach ( [ 1, 2, 3 ] as $idx ) {
			foreach ( $values["images".$idx] as $file )
				$values["images"][] = $file;
		}

		$error = false;
		foreach ( $values [ "images" ] as $key => $file )
		{
			if ( $file -> isOk () && $file -> isImage () )
			{
				$img = Image::fromFile ( $file -> temporaryFile );
				if ( ! Model\PubModel::saveImage ( $img, $entity ) )
				{
					$this -> presenter -> flashMessage ( sprintf ( "Obrázek '%s' je buď poškozený nebo se nejedná o obrázek.", $file -> name ),
														 "error" );
					$error = true;
					continue;
					
				}
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
			$this -> presenter -> redirect ( "detail", [ "id" => $values [ "id" ], "paginator-page" => $this -> presenter -> paginator -> page ] );
		}
	}

}
