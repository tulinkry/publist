<?php

namespace App\Forms\Front;

use Nette\Application\UI\Form;

class SignInForm extends Form
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
