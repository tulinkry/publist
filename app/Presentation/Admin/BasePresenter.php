<?php

namespace App\Presentation\Admin;

use Nette;
use Tulinkry\Application\UI;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends UI\AdminPresenter
{
	public function startup()
	{
		parent::startup();
		if (!$this->user->isAllowed('backend')) {
			$this->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
			$this->redirect(":Front:Pub:default");
		}
	}

}
