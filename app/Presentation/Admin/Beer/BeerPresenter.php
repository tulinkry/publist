<?php

namespace App\Presentation\Admin\Beer;

use Nette;
use App\Forms\Admin;
use Tulinkry;

/**
 */
class BeerPresenter extends \App\Presentation\Admin\BasePresenter
{
	/** @inject @var \App\Model\BeerModel */
	public $beers;
	/** @inject @var \App\Model\BeerLinksModel */
	public $beerlinks;

	private $beer = null;
	private $beer_id;


	public function renderDefault()
	{
		$paginator = $this [ "paginator" ]->getPaginator();
		$paginator->itemCount = $this->beers->count();
		$this->template->beers = $this->beers->limit($paginator->itemsPerPage, $paginator->offset);
		if ($this->isAjax()) {
			$this->redrawControl("beers");
		}
	}

	public function renderDetail($id)
	{
	}

	public function actionDetail($id)
	{
		$this->template->beer = $this->beers->item($id);
		$this->beer = $this->template->beer;
		$this->beer_id = $id;

	}

	public function actionInsert()
	{
		$this->beer = null;

		$r = $this->beerlinks->by('Němý medvěd');
	}

	public function handleSearch(string $by)
	{
		$r = $this->beerlinks->by($by);
		$this->payload->pubs = $r;
		//$this -> sendPayload();
	}

	public function handleDelete($beer_id)
	{
		try {
			$entity = $this->beers->item($beer_id);
			$entity?->delete();
		} catch (\Exception $e) {
			$this->presenter->flashMessage("Pivo se nepodařilo smazat.", "error");
		}
		$this->redrawControl("beers");

		//$this -> presenter -> flashMessage ( "Pivo bylo smazáno" );

		if (!$this->isAjax()) {
			$this->redirect("this");
		}
	}

	protected function createComponentBeerForm($name)
	{
		$form = new Admin\BeerForm($this->beers, $this->beer);
		return $this [ $name ] = $form;
	}

}
