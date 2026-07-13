<?php

namespace App\Presentation\Admin\Pub;

use Nette;
use App\Forms\Admin;
use Tulinkry;

/**
 */
class PubPresenter extends \App\Presentation\Admin\BasePresenter
{
	/** @inject @var \App\Model\PubModel */
	public $pubs;
	/** @inject @var \App\Model\RatingModel */
	public $ratings;
	/** @inject @var \App\Model\BeerModel */
	public $beers;
	/** @inject @var \App\Model\BeerRatingModel */
	public $beerRatings;
	/** @inject @var \App\Model\DescriptionModel */
	public $descriptions;

	private $pub;
	private $pub_id;
	private $rating;
	private $rating_id;

	public function renderDefault()
	{
		$paginator = $this [ "paginator" ]->getPaginator();
		$paginator->itemCount = $this->pubs->count();
		$this->template->pubs = $this->pubs->limit($paginator->itemsPerPage, $paginator->offset, [], [ "inserted" => "DESC" ]);
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

	protected function createComponentPubForm($name)
	{
		return $this [ $name ] = new Admin\PubForm($this->pubs, $this->users, $this->parameters, $this->pub_id, $this->descriptions);
	}

	public function handleHide($pub_id)
	{
		if (!($entity = $this->pubs->item($pub_id))) {
			$this->presenter->flashMessage("Neexistující událost.", "error");
			if (!$this->isAjax()) {
				$this->redirect("this");
			}
			return;
		}

		$entity->update([ 'hidden' => true ]);
		$this->redrawControl("pubs");
		if (!$this->isAjax()) {
			$this->redirect("this");
		}
	}

	public function handleUnhide($pub_id)
	{
		if (!($entity = $this->pubs->item($pub_id))) {
			$this->presenter->flashMessage("Neexistující událost.", "error");
			if (!$this->isAjax()) {
				$this->redirect("this");
			}
			return;
		}
		$entity->update([ 'hidden' => false ]);
		$this->redrawControl("pubs");
		if (!$this->isAjax()) {
			$this->redirect("this");
		}
	}

	public function handleDelete($pub_id)
	{
		if (!($entity = $this->pubs->item($pub_id))) {
			$this->presenter->flashMessage("Neexistující událost.", "error");
			if (!$this->isAjax()) {
				$this->redirect("this");
			}
			return;
		}
		if (!\App\Model\PubModel::deleteImages($entity)) {

			$this->presenter->flashMessage("Nepodařilo se smazat fyzická data.", "error");
			if (!$this->isAjax()) {
				$this->redirect("this");
			}
			return;
		}

		$entity->delete();
		$this->redrawControl("pubs");

		if (!$this->isAjax()) {
			$this->redirect("this");
		}
	}

	public function actionRating($id)
	{
		$this->template->rating = $this->ratings->item($id);
		$this->rating = $this->template->rating;
		$this->rating_id = $id;
		$this->template->paginator2 = $this["paginator2"]->getPaginator();
	}

	public function handleDeleteRating($rating_id)
	{
		if (!($entity = $this->ratings->item($rating_id))) {
			$this->presenter->flashMessage("Neexistující událost.", "error");
			if (!$this->isAjax()) {
				$this->redirect("this");
			}
			return;
		}
		$pub = $entity->ref('pubs', 'pub_id');
		$entity->delete();
		\App\Model\PubModel::recomputeAndTouch($pub);
		$this->redrawControl("ratings");
		if (!$this->isAjax()) {
			$this->redirect("this");
		}
	}


	protected function createComponentRatingForm($name)
	{
		return $this [ $name ] = new Admin\RatingForm($this->pubs, $this->ratings, $this->beers, $this->rating_id, $this->beerRatings);
	}

	protected function createComponentPubTypesForm($name)
	{
		if (!(isset($this->parameters->params['pub']) && isset($this->parameters->params['pub']['typeFile']))) {
			throw new \Nette\InvalidArgumentException("Configuration section 'pub' and parameter 'typeFile' don't exists.");
		}

		return $this [ $name ] = new Admin\PubTypesForm($this->parameters->params['pub']['typeFile']);
	}
}
