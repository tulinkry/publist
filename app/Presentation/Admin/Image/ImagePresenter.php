<?php

namespace App\Presentation\Admin\Image;

use Nette;
use App\Forms\Admin;
use Tulinkry;

/**
 */
class ImagePresenter extends \App\Presentation\Admin\BasePresenter
{
	/** @inject @var \App\Model\PubModel */
	public $pubs;
	/** @inject @var \App\Model\RatingModel */
	public $ratings;

	private $pub;
	private $pub_id;
	private $rating;
	private $rating_id;

	public function renderDefault()
	{
		$paginator = $this [ "paginator" ]->getPaginator();
		$paginator->itemCount = $this->pubs->count();
		$this->template->pubs = $this->pubs->limit($paginator->itemsPerPage, $paginator->offset);
		if ($this->isAjax()) {
			$this->redrawControl("pubs");
		}
	}

	public function renderDetail($id)
	{
		$paginator = $this [ "paginator2" ]->getPaginator();
		$paginator->itemCount = $this->ratings->count([ "pub_id" => $this->pub->id ]);
		$this->template->ratings = $this->ratings->limit($paginator->itemsPerPage,
			$paginator->offset,
			[ "pub_id" => $this->pub->id ]);
		$this->template->paginator2 = $this["paginator2"]->getPaginator();
		$this->template->images = \App\Model\PubModel::getImages($this->pub);
	}

	public function actionDetail($id)
	{
		$this->template->pub = $this->pubs->item($id);
		$this->pub = $this->template->pub;
		$this->pub_id = $id;

		if (!$this->pub) {
			$this->flashMessage("Restaurace neexistuje, vyberte jinou");
			$this->redirect("Pub:default");
		}
	}

	public function actionInsert($id)
	{
		$this->actionDetail($id);
	}

	protected function createComponentPaginator2($name)
	{
		$visualPaginator = new Tulinkry\Components\VisualPaginator();
		$visualPaginator->paginator->itemsPerPage = 10;
		if (array_key_exists("paginator", $this->parameters->params) &&
			 array_key_exists("itemsPerPage", $this->parameters->params [ "paginator" ])) {
			$visualPaginator->paginator->itemsPerPage = intval($this->parameters->params [ "paginator" ] [ "itemsPerPage" ]);
		}
		return $this [ $name ] = $visualPaginator;
	}


	public function handleRotate($file_name)
	{
		if (!\App\Model\PubModel::rotateImage($file_name, $this->pub)) {
			$this->presenter->flashMessage("Neexistující obrázek.", "error");
		}

		$this->redrawControl("pics");

		if (!$this->isAjax()) {
			$this->redirect("this");
		}
	}

	public function handleDelete($file_name)
	{
		if (!\App\Model\PubModel::deleteImage($file_name, $this->pub)) {
			$this->presenter->flashMessage("Neexistující obrázek.", "error");
		}
		$this->redrawControl("pics");

		if (!$this->isAjax()) {
			$this->redirect("this");
		}
	}

	protected function createComponentImageForm($name)
	{
		$form = new Admin\ImageForm($this->pubs, $this->pub);
		return $this [ $name ] = $form;
	}

}
