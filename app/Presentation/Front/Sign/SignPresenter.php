<?php

namespace App\Presentation\Front\Sign;

use Nette;
use Tulinkry;
use App\Forms\Front;

class SignPresenter extends \App\Presentation\Front\BasePresenter
{
	/** @inject @var \App\Model\UserModel */
	public $users;

	protected function createComponentSignInForm()
	{
		$form = new Front\SignInForm();
		// call method signInFormSucceeded() on success
		$form->onSuccess[] = [$this, 'signInFormSucceeded'];
		return $form;
	}


	public function signInFormSucceeded($form, $values)
	{
		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', false);
		} else {
			$this->getUser()->setExpiration('20 minutes', true);
		}

		try {
			$this->getUser()->login($values->email, $values->password);
			$this->restoreRequest($this->backlink);
			$this->redirect(':Front:Pub:default');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}


	protected function createComponentRegistrationForm()
	{
		$form =  new Tulinkry\Forms\RegistrationForm();
		$form->onSuccess [] = array( $this, "registerFormSubmitted" );
		return $form;
	}

	public function registerFormSubmitted($form)
	{
		$values = $form->getValues('array');

		$values [ "password" ] = password_hash($values [ "password" ], PASSWORD_DEFAULT);


		$values [ "registration" ] = new Tulinkry\DateTime(date("Y-m-d H:i:s"));
		$values [ "click" ] = new Tulinkry\DateTime(date("Y-m-d H:i:s"));
		$values [ "ip" ] = $this->getHttpRequest()->getRemoteAddress();
		$values [ "name" ] = $values [ "username" ];
		// NOT NULL columns with no DB default and no corresponding form
		// field - "right" is this app's ACL role name (see Authorizator),
		// new self-registrations get the base "user" role; "skin"/"state"
		// are otherwise-unused legacy columns (see the commented-out
		// setSkin() call in ChangePasswordForm) that still can't be NULL.
		$values [ "right" ] = "user";
		$values [ "skin" ] = 0;
		$values [ "state" ] = 1;
		// "another_password" is a form-only confirmation field (checked in
		// RegistrationForm::validateForm()), not a users table column.
		unset($values [ "another_password" ]);

		if (count($this->users->by([ "username" => $values [ "username" ] ]))) {
			$this->flashMessage("Toto přihlašovací jméno už existuje. Zvolte jiné", "error");
			return;
		}

		if (count($this->users->by([ "email" => $values [ "email" ] ]))) {
			$this->flashMessage("Tato emailová adresa už existuje. Zvolte jinou", "error");
			return;
		}

		try {
			$u = $this->users->insert($values);
		} catch (\Exception $e) {
			$this->flashMessage("Nastala chyba při ukládání", "error");
			return;
		}

		$this->flashMessage('Byl jsi registrován. Můžeš pokračovat přihlášením.');
		$this->redirect("login");
	}

	public function actionLogout()
	{
		//$this->getUser()->logout();
		//$this->flashMessage('You have been signed out.');
		//$this->redirect('login');
	}

}
