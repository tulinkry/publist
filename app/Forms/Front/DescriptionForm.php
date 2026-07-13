<?php

namespace App\Forms\Front;

use Tulinkry\Forms;
use Tulinkry;
use Nette\Utils\Html;
use Nette\Utils\Image;

class DescriptionForm extends Forms\Form
{
	protected $model;
	protected $pub;
	protected $users;
	protected $description;
	protected $lastDescription;



	public function __construct($model, $users, $pub, $description, $lastDescription = null)
	{
		parent::__construct();

		$this->model = $model;
		$this->pub = $pub;
		$this->users = $users;
		$this->description = $description;
		$this->lastDescription = $lastDescription;

		$this->getElementPrototype()->class = "descriptionForm ajax";

		$this->addHidden('id', $description ? $description->id : null);


		$text = $this->addTextArea("text", "Popisek")
						   ->setAttribute("placeholder", "Delší popisek restauračního zařízení")
						   ->setAttribute('rows', 10);
		$text = $text->getControlPrototype();
		$text->data [ 'content' ] = "Krátký popisek by měl obsahovat např. umístění zařízení vzhledem okolnímu prostředí, jeho dostupnost a jiné"
										   . " informace, které by mohly ostatní zajímat a nedají se jim jinak sdělit.";
		$text->data [ 'heading' ] = "Pomocník";


		if ($pub && $lastDescription) {
			$this ["text"]->setDefaultValue($lastDescription->text);
		}


		if ($description) {
			$this->setDefaults($description->toArray());
			$this->addSubmit("submit", "Upravit");
		} else {
			$this->addSubmit("submit", "Upravit");
		}

	}


	public function process($form)
	{
		if (!$this->presenter->user->isAllowed('frontend')) {
			$this->presenter->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
			$this->presenter->redirect(":Front:Pub:default");
		}

		if (!($user = $this->users->item($this->presenter->user->id))) {
			$form->presenter->flashMessage("Neexistující uživatel!", "error");
			$this->presenter->user->logout(true);
			$form->presenter->redirect("Sign:login");
			return;
		}

		$values = $form->getValues('array');

		if ($values['id']) {
			try {
				$desc = $this->model->item($values['id']);
				if (!$desc || $desc->pub_id != $this->pub->id) {
					$this->presenter->flashMessage("Neexistující popis!", "error");
					$form->addError("Neexistující popis.");
					return;
				}
				unset($values['id']);
				$desc->update($values);
			} catch (\Exception $e) {
				$this->presenter->flashMessage("Popis se nepodařilo uložit", "error");
				$form->addError("Popis se nepodařilo uložit.");
				return;
			}
			$this->presenter->flashMessage("Popis byl v pořádku uložen");

		} else {

			unset($values['id']);

			$values["user_id"] = $user->id;
			$values["version"] = ($this->lastDescription ? $this->lastDescription->version : 0) + 1;
			$values["pub_id"] = $this->pub->id;


			try {
				$desc = $this->model->insert($values);
			} catch (\Exception $e) {
				$this->presenter->flashMessage("Popis se nepodařilo vložit.", "error");
				$form->addError("Popis se nepodařilo vložit.");
				return;
			}
			$this->presenter->flashMessage("Popis byl v pořádku vložen");
		}

		$this->presenter->redrawControl('description');

		if (!$this->presenter->isAjax()) {
			$this->presenter->redirect("this");
		}
	}

}
