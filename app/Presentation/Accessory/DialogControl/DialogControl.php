<?php

namespace App\Presentation\Accessory;

use Tulinkry\Application\UI\Control;
use Tulinkry;
use Forms;
use Nette;

class Dialog extends Control
{
	protected $form;

	public function __construct()
	{
		//$form -> getElementPrototype () -> addClass ( "ajax" );
	}

	public function show($form)
	{
		$this->template->_form = $this->form = $form;

		$this->redrawControl();
		return $this;
	}

	public function title($title)
	{
		$this->template->title = $title;
		return $this;
	}


	public function close()
	{
		$this->template->close = true;
		$this->redrawControl();
	}



	public function render()
	{
		$this->template->setFile(__DIR__  . "/dialogControl.latte");
		$this->template->_form = $this->form;
		$this->template->render();
	}

}
