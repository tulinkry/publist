<?php

namespace App\Presentation\Admin\Homepage;

use Nette;
use App\Forms\Admin;
use Tulinkry\Application\UI;
use Tulinkry;

/**
 * Homepage presenter.
 */
class HomepagePresenter extends \App\Presentation\Admin\BasePresenter
{
	public function renderDefault()
	{
	}


	protected function createComponentTodoForm($name)
	{
		return $this [ $name ] = new Admin\TodoForm(__DIR__);
	}
}
