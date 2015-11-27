<?php

namespace AdminModule\Forms;


use Tulinkry\Forms;
use Model;


class TodoForm extends Forms\Form
{
	const FILENAME = "todo.txt";
	private $destinationPath;

	public function __construct ( $destinationPath )
	{
		parent::__construct ();
		
		$this->destinationPath = $destinationPath;

		$this -> addTextArea ( "todo", "Todolist" )
			  -> setAttribute ( "rows", 25 );

		$this -> addSubmit ( "submit", "Uložit" );

		$values = array ( "todo" => null );
		
		if ( file_exists( $destinationPath . "/" . self::FILENAME ) )
			$values [ "todo" ] = file_get_contents ( $destinationPath . "/" . self::FILENAME );

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
		file_put_contents ( $this -> destinationPath . "/" . self::FILENAME, $values [ "todo" ] );	

	}

}


