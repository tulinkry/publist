<?php

namespace App\Forms\Front;

use Tulinkry\Forms;
use Tulinkry;
use Nette\Utils\Html;
use Nette\Utils\Image;

class BeerForm extends Forms\Form
{
	protected $model;
	protected $beer;



	public function __construct($model, $beer)
	{
		parent::__construct();

		$this->model = $model;
		$this->beer = $beer;

		$this->getElementPrototype()->class = "beerForm";

		// attached() is never called by modern Nette (see Tulinkry\Forms\Form) -
		// wire the search-url data attribute via monitor() instead.
		$this->monitor(\Nette\Application\UI\Presenter::class, function ($presenter) {
			if (method_exists($presenter, "handleSearch")) {
				// JS appends its own "&by=<query>" on top of this base URL
				// (see www/js/main.js) - handleSearch(string $by) just needs
				// a placeholder value to satisfy link() generation here.
				$this->getElementPrototype()->data [ "search-url" ] = $presenter->link("Search!", [ "by" => "" ]);
			}
		});

		$this->addHidden('id', $beer ? $beer->id : null);


		$this->addText('name', 'Název piva')
			  ->setAttribute('placeholder', "Staropramen")
			  ->getControlPrototype()->id = "beerForm-name";
		//-> setRequired ()
		//-> addRule ( $this::FILLED, "Název piva musí být vyplněn" );
		;

		$degrees = array(
			"Nejpoužívanější" => array( 10, 11, 12, 13, 15, 16 ),
			"Ostatní" => Tulinkry\Utils\Arrays::createSequence(5, 50)
		);

		$this->addSelect('degree', 'Stupeň piva')
			  ->setItems($degrees, false)
			  //-> setRequired ()
			  ->setPrompt('Vyberte stupeň piva')
			  ->getControlPrototype()->id = "beerForm-degree";
		//-> addRule ( $this::FILLED, "Stupeň piva musí být vyplněn" );


		$this->addText("link", "Odkaz na jinou stránku")
			  ->setAttribute("placeholder", "http://popisekpiva.cz?pivo=3")
			  ->getControlPrototype()->id = "beerForm-link";


		if ($beer) {
			$this->setDefaults($beer->toArray());
			$this->addSubmit("submit", "Uložit");
		} else {
			$this->addSubmit("submit", "Přidat");
		}

	}

	public function process($form)
	{
		if (!$this->presenter->user->isAllowed('frontend')) {
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
				$form->addError("Pivo se nepodařilo uložit.");
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
				$this->presenter->flashMessage("Pivo se nepodařilo vložit. Duplicitní pivo?", "error");
				$form->addError("Pivo se nepodařilo vložit. Duplicitní pivo?");
				return;
			}
			$this->presenter->flashMessage("Pivo bylo v pořádku vloženo");
		}

		if (!$this->presenter->isAjax()) {
			$this->presenter->redirect("default", [ "paginator-page" => $this->presenter->getPaginator()->page ]);

		}
	}

}
