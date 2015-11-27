<?php

namespace FrontModule\Presenters;

use Nette,
	Model,
	Tulinkry,
	Authenticator;


class SignPresenter extends BasePresenter
{

	/** @inject @var Model\UserModel */
	public $users;

	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = new Nette\Application\UI\Form;
		$form->addText('email', 'Email:')
			->setRequired('Vložte emailovou adresu, se kterou jste se registroval.');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Vložte své heslo prosím.');

		$form->addCheckbox('remember', 'Pamatovat si mě');

		$form->addSubmit('send', 'Přihlásit');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = $this->signInFormSucceeded;
		return $form;
	}


	public function signInFormSucceeded($form, $values)
	{
		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->getUser()->login($values->email, $values->password);
			$this->restoreRequest($this->backlink);
			$this->redirect(':Front:Pub:default');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}


	protected function createComponentRegistrationForm ()
	{
		$form =  new Tulinkry\Forms\RegistrationForm;
		$form -> onSuccess [] = array ( $this, "registerFormSubmitted" );
		return $form;
	}

	public function registerFormSubmitted ( $form )
	{
		$values = $form ->values;

		$values [ "password" ] = Authenticator\Authenticator::calculateHash ( $values [ "password" ], $values [ "email" ] );
		

		$values [ "registration" ] = new Tulinkry\DateTime ( date ( "Y-m-d H:i:s" ) );
		$values [ "click" ] = new Tulinkry\DateTime ( date ( "Y-m-d H:i:s" ) );
		$values [ "ip" ] = $_SERVER [ "REMOTE_ADDR" ];
		$values [ "name" ] = $values [ "username" ];

		$u = $this -> users -> create ( $values );
		

		if ( count ( $this -> users -> by ( [ "username" => $u -> getUsername () ] ) ) )
		{
			$this -> flashMessage ( "Toto přihlašovací jméno už existuje. Zvolte jiné", "error" );
			return;
		}

		if ( count ( $this -> users -> by ( [ "email" => $u -> getEmail () ] ) ) )
		{
			$this -> flashMessage ( "Tato emailová adresa už existuje. Zvolte jinou", "error" );
			return;
		}

		try
		{
			$this -> users -> insert ( $u );
		}
		catch ( \Exception $e )
		{
			$this -> flashMessage ( "Nastala chyba při ukládání", "error" );
		}

		if ( ! $form -> hasErrors () )
		{
			$this->flashMessage('Byl jsi registrován. Můžeš pokračovat přihlášením.');
			$this->redirect ("login");
		}
	}

	public function actionLogout()
	{
		//$this->getUser()->logout();
		//$this->flashMessage('You have been signed out.');
		//$this->redirect('login');
	}

}
