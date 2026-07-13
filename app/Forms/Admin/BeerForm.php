<?php

namespace App\Forms\Admin;

use Tulinkry;
use Nette\Utils\Html;
use Nette\Utils\Image;

class BeerForm extends \App\Forms\Front\BeerForm
{
	public function process($form)
	{
		if (!$this->presenter->user->isAllowed('backend')) {
			$this->presenter->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
			$this->presenter->redirect(":Front:Pub:default");
		}

		$values = $form->getValues('array');

		if ($values['id']) {
			try {
				$beer = $this->model->item($values['id']);
				unset($values['id']);
				$beer->update($values);

			} catch (\Exception $e) {
				$this->presenter->flashMessage("Pivo se nepodařilo uložit", "error");
				return;
			}
			$this->presenter->flashMessage("Pivo bylo v pořádku uloženo");

		} else {

			unset($values['id']);
			if (!$values['link'] || $values['link'] == '') {
				$values['link'] = null;
			}
			try {
				$beer = $this->model->insert($values);

			} catch (\Exception $e) {
				$this->presenter->flashMessage("Pivo se nepodařilo vložit", "error");
				return;
			}
			$this->presenter->flashMessage("Pivo bylo v pořádku vloženo");
		}


		$this->presenter->redirect("default", [ "paginator-page" => $this->presenter->getPaginator()->page ]);
	}

}
