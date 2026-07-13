<?php

namespace App\Presentation\Admin\Email;

use Nette;
use Tulinkry;

/**
 */
class EmailPresenter extends \App\Presentation\Admin\BasePresenter
{
	/** @inject @var \App\Model\EmailModel */
	public $emails;


	public function renderDefault()
	{
		$paginator = $this [ "paginator" ]->getPaginator();
		$paginator->itemCount = $this->emails->count();
		$this->template->emails = $this->emails->limit($paginator->itemsPerPage, $paginator->offset);
		if ($this->isAjax()) {
			$this->redrawControl("emails");
		}
	}

}
