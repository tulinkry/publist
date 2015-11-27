<?php

namespace AdminModule\Forms;


use Tulinkry\Forms;
use Model;
use Nette\Utils\Neon;


class PubTypesForm extends Forms\Form
{
	private $destinationPath;

	protected $types = array (
		//[Aa][Uu][Rr][Aa]([Cc][Ee]|[Nn][Tt]
		"Restaurace" => "\s*?Rest[^-\s]*\s*",
		"Bar" => "\s*?Bar[^-\s]*\s*",
		"Klub" => "\s*?[CK]lub\s*",
		"Pizzerie" => "\s*?Piz[^-\s]*\s*",
		"Hospoda" => "\s*?Hosp[^-\s]*\s*",
		//"Hospůdka" => "\s*?Hospů[^-\s]*\s*",
		"Pivnice" => "\s*?Pivni[^-\s]*\s*",
		"Bufet" => "\s*?Buf[^-\s]*\s*",
		"Kavárna" => "\s*?(Kavár|Kaf|Caf)[^-\s]*\s*",
	);

	public function __construct ( $destinationPath )
	{
		parent::__construct ();
		
		$this->destinationPath = $destinationPath;

		$this -> addTextArea ( "types", "Types" )
			  -> setAttribute ( "rows", 25 );

		$this -> addSubmit ( "submit", "Uložit" );

		$values = array ( "types" => null );
		
		if ( file_exists( $destinationPath ) )
			$values [ "types" ] = file_get_contents ( $destinationPath );

		$this -> setDefaults ( $values );
	}

	public function process ( $form )
	{

		if ( ! $this -> presenter -> user -> isAllowed ( 'backend' ) )
		{
            $this->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
            $this->redirect(":Front:Pub:default");
		}

		$values = $form -> values;

		try {
			$types = Neon::decode ( $values["types"] );
			$types = $types['types'];
		} catch ( \Exception $e ) {
			$form -> presenter -> flashMessage ( "Neplatný řetězec: " . $e->getMessage(), 'danger' );
			return;
		}


		file_put_contents ( $this -> destinationPath, $values [ "types" ] );
		$form -> presenter -> flashMessage ( "Uložení bylo úspěšné" );

	}

}


