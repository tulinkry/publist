<?php

namespace AdminModule\Presenters;

use Nette,
	Model,
	FrontModule\Controls,
	Nette\Application\UI\Multiplier,
	AdminModule\Forms,
	Oli,
	Tulinkry;


/**
 */
class EmailPresenter extends BasePresenter
{
	/** @inject @var Model\EmailModel */
	public $emails;


	public function renderDefault()
	{
		$paginator = $this [ "paginator" ] -> getPaginator ();
		$paginator -> itemCount = $this -> emails -> count ();
		$this -> template -> emails = $this -> emails -> limit ( $paginator -> itemsPerPage, $paginator -> offset );
		if ( $this -> isAjax () )
			$this -> invalidateControl ( "emails" );
	}

}