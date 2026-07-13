<?php

namespace App\Forms\Admin;

use Nette\Utils\Html;
use Tulinkry;

class PubForm extends \App\Forms\Front\PubForm
{
	protected $id;

	public function __construct($model, $users, $parameters, $id, $descriptions = null)
	{
		parent::__construct($model, $users, $parameters, $descriptions);
		$this->id = $id;

		$this->addHidden("id");

		$this->getElementPrototype()->data ['type'] = "adminForm";

		$entity = $model->item($id);
		if (!$entity) {
			return;
		}

		$entity_array = $entity->toArray();
		$entity_array [ "coords" ] = new \StdClass();
		$entity_array [ "coords" ]->lat = $entity_array [ "latitude" ];
		$entity_array [ "coords" ]->lng = $entity_array [ "longitude" ];
		$entity_array [ "coords" ]->address = $entity_array [ "address" ];
		$entity_array [ "coords" ]->location = $entity_array [ "location" ];

		$types = [];
		foreach (explode(", ", $entity_array["type"] ?? "") as $type) {
			if (!trim($type)) {
				continue;
			}
			$types[] = array_flip(array_keys($this->types))[$type];
		}

		$entity_array [ "type" ] = $types;

		$this->setDefaults($entity_array);

		$this->removeComponent($this [ "submit" ]);
		$this->removeComponent($this [ "agreement" ]);
		$this->addSubmit("submit", "Uložit");
	}

	public function process($form)
	{
		$values = $form->getValues('array');

		if (!$this->presenter->user->isAllowed('backend')) {
			$this->presenter->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
			$this->presenter->redirect(":Front:Pub:default");
		}

		if (!($user = $this->users->item($this->presenter->user->id))) {
			$form->presenter->flashMessage("Neexistující uživatel!", "error");
			$this->presenter->user->logout(true);
			$form->presenter->redirect("Sign:login");
			return;
		}


		$that = $this;
		$values [ "type" ] = array_map(function ($el) use ($that) {
			return array_keys($that->types) [ $el ];
		}, $values["type"]);

		$values [ "type" ] = implode(", ", $values [ "type" ]);
		$values [ "latitude" ] = $values [ "coords" ]->lat;
		$values [ "longitude" ] = $values [ "coords" ]->lng;
		$values [ "address" ] = $values [ "coords" ]->address;
		// GpsPositionPicker only exposes lat/lng/address (see
		// App\Forms\Front\PubForm::processValues()) - reuse address here too.
		$values [ "location" ] = $values [ "coords" ]->address;
		$values [ "updated" ] = new Tulinkry\DateTime();

		$entity = $this->model->item($values [ "id" ]);

		$lastDescription = \App\Model\PubModel::lastDescription($entity);

		if ($this->descriptions && (!$lastDescription || $lastDescription->text !== $values [ "long_name" ])) {
			$this->descriptions->insert([
				'pub_id' => $entity->id,
				'user_id' => $user->id,
				'version' => ($lastDescription ? $lastDescription->version : 0) + 1,
				'text' => $values [ "long_name" ],
			]);
		}

		unset($values [ "id" ]);
		unset($values [ "coords" ]);

		$entity->update($values);

		$this->presenter->flashMessage("Nastavení bylo uloženo", "success");

		$this->presenter->redirect("Pub:default", [ "paginator-page" => $this->presenter->getPaginator()->page ]);
	}

}
