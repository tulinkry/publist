<?php

namespace App\Presentation\Front\Contact;

use Nette;
use Tulinkry;

/**
 * presenter.
 */
class ContactPresenter extends \App\Presentation\Front\BasePresenter
{
	protected function createComponentContactForm($name)
	{

		$contacts = $this->parameters->params [ "contactEmails" ] ?? null;

		$form = new Tulinkry\Forms\ContactForm($contacts, "Publist");
		return $form;

	}

}
