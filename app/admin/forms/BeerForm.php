<?php

namespace AdminModule\Forms;


use FrontModule\Forms;
use Tulinkry;
use Model;
use Nette\Utils\Html;
use Nette\Utils\Image;


class BeerForm extends Forms\BeerForm
{


	public function process ( $form )
	{
		if ( ! $this -> presenter -> user -> isAllowed ( 'backend' ) )
		{
            $this->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
            $this->redirect(":Front:Pub:default");
		}

		$values = $form -> getValues (TRUE);

		if ($values['id']) {
			try {
				$this -> model -> update_array ( $values['id'], $values );

			} catch ( \Exception $e )
			{
				$this -> presenter -> flashMessage ( "Pivo se nepodařilo uložit", "error" );
				return;
			}
			$this -> presenter -> flashMessage ( "Pivo bylo v pořádku uloženo" );

		} else {

			unset($values['id']);
			if(!$values['link'] || $values['link']=='')
				$values['link'] = NULL;
			try {
				$beer = $this -> model -> create ( $values );
				$this -> model -> insert ( $beer );
				
			} catch ( \Exception $e )
			{
				$this -> presenter -> flashMessage ( "Pivo se nepodařilo vložit", "error" );
				return;
			}
			$this -> presenter -> flashMessage ( "Pivo bylo v pořádku vloženo" );
		}


		$this -> presenter -> redirect ( "default", [ "paginator-page" => $this -> presenter -> paginator -> page ] );
	}

}
