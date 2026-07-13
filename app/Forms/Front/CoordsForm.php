<?php

namespace App\Forms\Front;

use Tulinkry\Application\UI\Form;

class CoordsForm extends Form
{
	public function __construct()
	{
		parent::__construct();

		$this->addText('latitude', 'Latitude');
		$this->addText('longitude', 'Longitude');
		$this->addSubmit('submit', 'Použít');

		$this->getElementPrototype()->class = 'form-inline';
	}
}
