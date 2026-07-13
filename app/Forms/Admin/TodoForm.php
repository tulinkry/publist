<?php

namespace App\Forms\Admin;

use Tulinkry\Forms;

class TodoForm extends Forms\Form
{
	public const FILENAME = "todo.txt";
	private $destinationPath;

	public function __construct($destinationPath)
	{
		parent::__construct();

		$this->destinationPath = $destinationPath;

		$this->addTextArea("todo", "Todolist")
			  ->setAttribute("rows", 25);

		$this->addSubmit("submit", "Uložit");

		$values = array( "todo" => null );

		if (file_exists($destinationPath . "/" . self::FILENAME)) {
			$values [ "todo" ] = file_get_contents($destinationPath . "/" . self::FILENAME);
		}

		$this->setDefaults($values);
	}

	public function process($form)
	{

		if (!$this->presenter->user->isAllowed('backend')) {
			$this->presenter->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
			$this->presenter->redirect(":Front:Pub:default");
		}

		$values = $form->getValues('array');
		file_put_contents($this->destinationPath . "/" . self::FILENAME, $values [ "todo" ]);

	}

}
