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
class ImagePresenter extends BasePresenter
{
	/** @inject @var Model\PubModel */
	public $pubs;
	/** @inject @var Model\RatingModel */
	public $ratings;

	private $pub;
	private $pub_id;
	private $rating;
	private $rating_id;

	public function renderDefault()
	{
		$paginator = $this [ "paginator" ] -> getPaginator ();
		$paginator -> itemCount = $this -> pubs -> count ();
		$this -> template -> pubs = $this -> pubs -> limit ( $paginator -> itemsPerPage, $paginator -> offset );
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
		$this -> template -> images = Model\PubModel::getImages ( $this -> pub );
	}

	public function actionDetail ( $id )
	{
		$this -> template -> pub = $this -> pubs -> item ( $id );
		$this -> pub = $this -> template -> pub;
		$this -> pub_id = $id;

	}

	public function actionInsert ( $id )
	{
		$this -> actionDetail ( $id );
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


	public function handleRotate ( $file_name )
	{
		if ( ! Model\PubModel::rotateImage ( $file_name, $this -> pub ) )
			$this -> presenter -> flashMessage ( "Neexistující obrázek.", "error" );

		$this -> invalidateControl ( "pics" );

		if ( ! $this -> isAjax () )
			$this -> redirect ( "this" );
	}

	public function handleDelete ( $file_name )
	{
		if ( ! Model\PubModel::deleteImage ( $file_name, $this -> pub ) )
			$this -> presenter -> flashMessage ( "Neexistující obrázek.", "error" );
		$this -> invalidateControl ( "pics" );

		if ( ! $this -> isAjax () )
			$this -> redirect ( "this" );
	}	

	protected function createComponentImageForm ( $name )
	{
		$form = new Forms\ImageForm ( $this -> pubs, $this -> pub );
		return $this [ $name ] = $form;
	}

}