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
class PubPresenter extends BasePresenter
{
	/** @inject @var Model\PubModel */
	public $pubs;
	/** @inject @var Model\RatingModel */
	public $ratings;
	/** @inject @var Model\BeerModel */
	public $beers;
	/** @inject @var Oli\GoogleAPI\IMapAPI */
	public $map;
	/** @inject @var Oli\GoogleAPI\IMarkers */
	public $markers;

	private $pub;
	private $pub_id;
	private $rating;
	private $rating_id;

	public function renderDefault()
	{
		$paginator = $this [ "paginator" ] -> getPaginator ();
		$paginator -> itemCount = $this -> pubs -> count ();
		$this -> template -> pubs = $this -> pubs -> limit ( $paginator -> itemsPerPage, $paginator -> offset, [], [ "inserted" => "DESC" ] );
		if ( $this -> isAjax () )
			$this -> invalidateControl ( "pubs" );

	}

	public function renderDetail ( $id )
	{
		$paginator = $this [ "paginator2" ] -> getPaginator ();
		$paginator -> itemCount = $this -> ratings -> count ( [ "pub" => $this -> pub -> id ] );
		$this -> template -> ratings = $this -> ratings -> limit ( $paginator -> itemsPerPage, 
																   $paginator -> offset, 
																   [ "pub" => $this -> pub -> id ] );
		$this -> template -> paginator2 = $this["paginator2"]->getPaginator();	
	}

	public function actionDetail ( $id )
	{
		$this -> template -> pub = $this -> pubs -> item ( $id );
		$this -> pub = $this -> template -> pub;
		$this -> pub_id = $id;

	}

	protected function createComponentPaginator2 ( $name )
	{
	    $visualPaginator = new Tulinkry\Components\VisualPaginator();
	    $visualPaginator -> paginator -> itemsPerPage = 10;
	    if ( array_key_exists ( "paginator", $this -> parameters -> params ) &&
	    	 array_key_exists ( "itemsPerPage", $this -> parameters -> params [ "paginator" ] ) )
	    	$visualPaginator -> paginator -> itemsPerPage = intval ( $this -> parameters -> params [ "paginator" ] [ "itemsPerPage" ] );
	    return $this [ $name ] = $visualPaginator;
	}

	protected function createComponentMap ()
	{

		$map = $this->map->create();

		$map->setProportions('100%', '500px');
		if ( $this -> pub )
			$map->setCoordinates(array($this->pub->latitude, $this->pub->longitude))
			    ->setZoom(15)
			    ->setType(Oli\GoogleAPI\MapAPI::ROADMAP);

		$markers = $this->markers->create();
		//$markers->fitBounds();

		$map -> isScrollable ( false );
		$map -> isDraggable ( false );


		if ( $this -> pub )
			$markers -> addMarker ( array ( $this->pub->latitude, $this->pub->longitude ), Oli\GoogleAPI\Markers::DROP )
					 -> setMessage ( $this->pub->name );

		$map->addMarkers($markers);
		return $map;
	}

	protected function createComponentPubForm ( $name )
	{
		return $this [ $name ] = new Forms\PubForm ( $this -> pubs, $this -> users, $this->parameters, $this -> pub_id );
	}

	public function handleHide ( $pub_id )
	{
		if ( ! ( $entity = $this -> pubs -> item ( $pub_id ) ) )
		{
			$this -> presenter -> flashMessage ( "Neexistující událost.", "error" );
			if ( ! $this -> isAjax () )
				$this -> redirect ( "this" );
			return;
		}

		$entity -> hidden = true;
		$this -> pubs -> update ( $entity );
		$this -> invalidateControl ( "pubs" );
		if ( ! $this -> isAjax () )
			$this -> redirect ( "this" );
	}

	public function handleUnhide ( $pub_id )
	{
		if ( ! ( $entity = $this -> pubs -> item ( $pub_id ) ) )
		{
			$this -> presenter -> flashMessage ( "Neexistující událost.", "error" );
			if ( ! $this -> isAjax () )
				$this -> redirect ( "this" );
			return;
		}
		$entity -> hidden = false;
		$this -> pubs -> update ( $entity );
		$this -> invalidateControl ( "pubs" );
		if ( ! $this -> isAjax () )
			$this -> redirect ( "this" );
	}

	public function handleDelete ( $pub_id )
	{
		if ( ! ( $entity = $this -> pubs -> item ( $pub_id ) ) )
		{
			$this -> presenter -> flashMessage ( "Neexistující událost.", "error" );
			if ( ! $this -> isAjax () )
				$this -> redirect ( "this" );			
			return;
		}
		if ( ! Model\PubModel::deleteImages ( $entity ) )
		{

			$this -> presenter -> flashMessage ( "Nepodařilo se smazat fyzická data.", "error" );
			if ( ! $this -> isAjax () )
				$this -> redirect ( "this" );			
			return;
		}

		$this -> pubs -> remove ( $entity );
		$this -> invalidateControl ( "pubs" );

		if ( ! $this -> isAjax () )
			$this -> redirect ( "this" );		
	}	

	public function actionRating ( $id )
	{
		$this -> template -> rating = $this -> ratings -> item ( $id );
		$this -> rating = $this -> template -> rating;
		$this -> rating_id = $id;
		$this -> template -> paginator2 = $this["paginator2"]->getPaginator();
	}

	public function handleDeleteRating ( $rating_id )
	{
		if ( ! ( $entity = $this -> ratings -> item ( $rating_id ) ) )
		{
			$this -> presenter -> flashMessage ( "Neexistující událost.", "error" );
			if ( ! $this -> isAjax () )
				$this -> redirect ( "this" );
			return;
		}
		$pub = $entity -> pub;
		$pub -> removeRating ( $entity );
		$pub -> recompute ();
		$this -> ratings -> remove ( $entity );
		$this -> pubs -> update ( $pub );
		$this -> invalidateControl ( "ratings" );
		if ( ! $this -> isAjax () )
			$this -> redirect ( "this" );
	}		


	protected function createComponentRatingForm ( $name )
	{
		return $this [ $name ] = new Forms\RatingForm ( $this -> pubs, $this -> ratings, $this-> beers, $this -> rating_id );
	}

	protected function createComponentPubTypesForm ( $name )
	{
		if(! (isset($this->parameters->params['pub']) && isset($this->parameters->params['pub']['typeFile'])))
			throw new \Nette\InvalidArgumentException( "Configuration section 'pub' and parameter 'typeFile' don't exists." );

		return $this [ $name ] = new Forms\PubTypesForm ( $this->parameters->params['pub']['typeFile'] );
	}
}