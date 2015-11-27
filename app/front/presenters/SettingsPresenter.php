<?php

namespace FrontModule\Presenters;

use Nette,
	Model,
	FrontModule\Controls,
	Nette\Application\UI\Multiplier,
	FrontModule\Forms,
	Oli,
	Tulinkry;


class SettingsPresenter extends BasePresenter
{
	/** @inject @var Model\UserModel */
	public $users;

	protected $userClass;

	public function actionDefault ()
	{

        if (!$this->user->isAllowed('frontend')) 
        {
            $this->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
            $this->redirect(":Front:Pub:default");
        }		
        
		$this -> userClass = null;
		if ($this->getUser()->isLoggedIn())
		{
			$data = $this->getUser()->getIdentity()->getData();
			if ( isset($data["userClass"])) {
				$user = $this -> users -> merge ( $data ["userClass"] );
				$this -> userClass = $user;
			}
			
		}


		$this -> template -> userClass = $this -> userClass;
	}

	public function createComponentChangePasswordForm ()
	{
		return new Tulinkry\Forms\ChangePasswordForm ( $this -> users, $this -> userClass );
	}


}
