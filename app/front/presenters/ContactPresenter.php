<?php

namespace FrontModule\Presenters;

use Nette,
	Model,
	FrontModule\Forms,
	Tulinkry;


/**
 * presenter.
 */
class ContactPresenter extends BasePresenter
{


	
	protected function createComponentContactForm ( $name )
	{

		if ( array_key_exists("contactEmails", $this -> parameters -> params ) )
			$contacts = $this -> parameters -> params [ "contactEmails" ];
		
		$form = new Tulinkry\Forms\ContactForm ( $contacts, "Publist" );
		return $form;

	}

}