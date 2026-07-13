<?php

namespace App\Forms\Front;

use Tulinkry\Application\UI\Form;

/**
 * Same fields as SignInForm - kept as a separate class since this one is
 * shown inside a modal Dialog control and uses Tulinkry's Bootstrap-styled
 * Form/CustomRenderer base, not the plain Nette\Application\UI\Form the
 * full-page login form (SignInForm) uses.
 */
class DialogSignInForm extends Form
{
	public function __construct()
	{
		parent::__construct();

		$this->addText('email', 'Email:')
			->setRequired('Vložte emailovou adresu, se kterou jste se registroval.');

		$this->addPassword('password', 'Heslo:')
			->setRequired('Vložte své heslo prosím.');

		$this->addCheckbox('remember', 'Pamatovat si mě');

		$this->addSubmit('send', 'Přihlásit');
	}
}
