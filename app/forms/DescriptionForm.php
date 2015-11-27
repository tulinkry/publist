<?php

namespace FrontModule\Forms;


use Tulinkry\Forms;
use Tulinkry;
use Model;
use Nette\Utils\Html;
use Nette\Utils\Image;


class DescriptionForm extends Forms\Form
{
	protected $model;
	protected $pub;
	protected $users;
	protected $description;



	public function __construct ( $model, $users, $pub, $description )
	{
		parent::__construct ();

		$this -> model = $model;
		$this -> pub = $pub;
		$this -> users = $users;
		$this -> description = $description;

		$this->getElementPrototype()->class="descriptionForm ajax";

		$this -> addHidden ( 'id', $description ? $description -> id : NULL );


		$text = $this -> addTextArea ( "text", "Popisek" )
						   -> setAttribute ( "placeholder", "Delší popisek restauračního zařízení" )
						   -> setAttribute ( 'rows', 10 );
		$text = $text -> getControlPrototype ();
		$text -> data [ 'content' ] = "Krátký popisek by měl obsahovat např. umístění zařízení vzhledem okolnímu prostředí, jeho dostupnost a jiné"
										   . " informace, které by mohly ostatní zajímat a nedají se jim jinak sdělit.";
		$text -> data [ 'heading' ] = "Pomocník";


		if($pub) {
			$this ["text"] -> setDefaultValue ( $pub->lastDescription->text );
		}


		if($description) {
			$this -> setDefaults ( $description -> toArray () );
			$this -> addSubmit ( "submit", "Upravit" );
		} else {
			$this -> addSubmit ( "submit", "Upravit" );
		}

	}


	public function process ( $form )
	{
		if ( ! $this -> presenter -> user -> isAllowed ( 'frontend' ) )
		{
            $this->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
            $this->redirect(":Front:Pub:default");
		}

		if ( ! ( $user = $this -> users -> item ( $this -> presenter -> user -> id ) ) )
		{
			$form -> presenter -> flashMessage ( "Neexistující uživatel!", "error" );
			$this -> presenter -> user -> logout ( true );
			$form -> presenter -> redirect ( "Sign:login" );
			return;
		}

		$values = $form -> getValues (TRUE);

		if ($values['id']) {
			try {
				$this -> model -> update_array ( $values['id'], $values );
			} catch ( \Exception $e )
			{
				$this -> presenter -> flashMessage ( "Popis se nepodařilo uložit", "error" );
				$form -> addError ( "Popis se nepodařilo uložit." );
				return;
			}
			$this -> presenter -> flashMessage ( "Popis byl v pořádku uložen" );

		} else {

			unset($values['id']);

			$values["user"] = $user;
			$values["version"] = $this -> pub -> lastDescription -> version + 1;


			try {
				$desc = $this -> model -> create ( $values );
				$this -> pub -> addDescription ( $desc );
				$this -> model -> update ( $this->pub );
				
			} catch ( \Exception $e )
			{
				$this -> presenter -> flashMessage ( "Popis se nepodařilo vložit.", "error" );
				$form -> addError ( "Popis se nepodařilo vložit." );
				return;
			}
			$this -> presenter -> flashMessage ( "Popis byl v pořádku vložen" );
		}
		
		$this -> presenter -> invalidateControl ( 'description' );

		if ( ! $this->presenter->isAjax() ) {
			$this -> presenter -> redirect ( "this" );
		}
	}

}
