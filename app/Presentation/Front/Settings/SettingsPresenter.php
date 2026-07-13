<?php

namespace App\Presentation\Front\Settings;

use Nette;
use Tulinkry;

class SettingsPresenter extends \App\Presentation\Front\BasePresenter
{
	/** @inject @var \App\Model\UserModel */
	public $users;

	protected $userClass;

	public function actionDefault()
	{

		if (!$this->user->isAllowed('frontend')) {
			$this->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
			$this->redirect(":Front:Pub:default");
		}

		$this->userClass = $this->getUser()->isLoggedIn() ? $this->users->item($this->getUser()->getId()) : null;

		$this->template->userClass = $this->userClass;
	}

	public function createComponentChangePasswordForm()
	{
		return new Tulinkry\Forms\ChangePasswordForm($this->users, $this->userClass);
	}


}
