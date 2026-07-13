<?php

namespace App\Forms\Front;

use Nette\Application\UI\Form;
use Nette;
use Tulinkry;

/*
	  <form class="navbar-form navbar-left" role="search">
		<div class="form-group">
		  <input type="text" class="form-control" placeholder="Search">
		</div>
		<button type="submit" class="btn btn-default">Submit</button>
	  </form>

*/


/**
 * @property-read Nette\Application\UI\Presenter|null $presenter
 */
class SearchForm extends Form
{
	private $session;

	public function __construct(?Nette\ComponentModel\IContainer $parent = null, $name = null, $session = null)
	{
		parent::__construct($parent, $name);

		$this->session = $session;

		$this->getElementPrototype()->class = "navbar-form navbar-right";
		$this->getElementPrototype()->role = "search";

		$this->addText('search')
			  ->setAttribute('placeholder', "Hledat")
			  ->setAttribute('class', "form-control")
		;

		$items = array(
			'whole_name' => "Jméno",
			'name' => "Krátké jméno",
			'type' => "Typ",
			'address' => "Adresa",
			'location' => "Lokalita",
		);

		$this->addMultiSelect('fields')
			  ->setItems($items)
			  ->setDefaultValue('whole_name')
			  ->setAttribute('class', 'form-control selectpicker')
		;


		$this->addSubmit("submit", "Hledej!")
			  ->setAttribute('class', 'btn btn-default form-control')
			  ->onClick[] = array( $this, "process" );

		$renderer = $this->getRenderer();
		$renderer->wrappers['controls']['container'] = null;
		$renderer->wrappers['pair']['container'] = 'div class="form-group"';
		$renderer->wrappers['pair']['.error'] = 'has-error';
		$renderer->wrappers['control']['container'] = null;
		$renderer->wrappers['label']['container'] = 'div class="hidden"';
		$renderer->wrappers['control']['description'] = null;
		$renderer->wrappers['control']['description'] = null;
		$renderer->wrappers['control']['errorcontainer'] = null;

		if (isset($session->search) && isset($session->search->term)) {
			$this['search']->setDefaultValue($session->search->term);
			$this['fields']->setDefaultValue($session->search->fields);
		}

	}

	public function process($button)
	{
		$form = $button->form;
		$values = $form->getValues('array');

		$this->session->search = new \StdClass();
		$this->session->search->term = $values['search'];
		$this->session->search->fields = $values['fields'];
		$this->session->setExpiration('10 minutes');

		$this->presenter->redirect(":Front:Pub:search");
	}

}
