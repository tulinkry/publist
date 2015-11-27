<?php

namespace AdminModule\Presenters;

use Nette,
	Model,
	Entity;
use AdminModule\Forms;
use Tulinkry\Application\UI;
use Tulinkry;

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{
	public function renderDefault ()
	{
	}


	protected function createComponentTodoForm ( $name )
	{
		return $this [ $name ] = new Forms\TodoForm ( __DIR__ . "/templates/Homepage/" );
	}
}