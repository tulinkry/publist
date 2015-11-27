<?php

namespace FrontModule\Presenters;

use Nette,
	Model,
	FrontModule\Controls,
	Nette\Application\UI\Multiplier,
	FrontModule\Forms,
	Oli,
	Tulinkry;


class LadderPresenter extends BasePresenter
{
	/** @inject @var Model\PubModel */
	public $pubs;

	/** @inject @var Oli\GoogleAPI\IMapAPI */
	public $map;

	/** @inject @var Oli\GoogleAPI\IMarkers */
	public $markers;

	/** @var */
	public $latitude = null;
	/** @var */
	public $longitude = null;

	/** @var */
	public $sort = 1;
	/** @var */
	public $ascending = 0;

	const SORT_MAX = 17;
	const SORT_MIN = 1;


	private $orderBy = array (  
		1 => "mark",
		2 => "name",
		3 => "location",
		4 => "beerMark",
		5 => "beerPrice",
		6 => "wineMark",
		7 => "winePrice",
		8 => "foodMark",
		9 => "toaletsMark",
		10 => "serviceMark",
		11 => "overallMark",
		12 => "foodPrice",
		13 => "interierMark",
		14 => "exterierMark",
		15 => "distance",
		16 => "inserted",
		17 => "updated"
	);
	private $order = array (
		 1 => "DESC",
		 2 => "ASC",
		 3 => "ASC", 
		 4 => "DESC", 
		 5 => "ASC", 
		 6 => "DESC", 
		 7 => "ASC", 
		 8 => "DESC", 
		 9 => "DESC", 
		10 => "DESC", 
		11 => "DESC", 
		12 => "DESC",
		13 => "DESC",
		14 => "DESC",
		15 => "ASC",
		16 => "DESC",
		17 => "DESC"
	);

	private $modes = array (
		1 => "známky",
		2 => "jména",
		3 => "místa",
		4 => "piva",
		5 => "ceny piva",
		6 => "vína",
		7 => "ceny vína",
		8 => "jídla",
		9 => "toalet",
		10 => "personálu",
		11 => "celkového dojmu",
		12 => "celkové ceny",
		13 => "interiéru",
		14 => "exteriéru",
		15 => "vzdálenost",
		16 => "času vložení",
		17 => "času poslední úpravy",
	);

	private $menu = array ();


	public function startup ()
	{
		parent::startup ();
		$this -> menu = array (
			1 => (object) array ( "name" => "Známka", "title" => "Seřadit podle celkového hodnocení", "sort" => 1 ),
			2 => (object) array ( "name" => "Jméno", "title" => "Seřadit podle jména", "sort" => 2 ),
			3 => (object) array ( "name" => "Místo", "title" => "Seřadit podle místa", "sort" => 3 ),
			4 => (object) array ( "name" => "Pivo", "title" => "Seřadit podle piva", "sort" => 4 ),
			//5 => (object) array ( "name" => "Cena piva", "title" => "Seřadit podle ceny piva", "sort" => 5 ),
			6 => (object) array ( "name" => "Víno", "title" => "Seřadit podle vína", "sort" => 6 ),
			7 => (object) array ( "name" => "Cena vína", "title" => "Seřadit podle průměrné ceny vína", "sort" => 7 ),
			8 => (object) array ( "name" => "Jídlo", "title" => "Seřadit podle jídla", "sort" => 8 ),
			9 => (object) array ( "name" => "Toalety", "title" => "Seřadit podle toalet", "sort" => 9 ),
			10 => (object) array ( "name" => "Personál", "title" => "Seřadit podle personálu", "sort" => 10 ),
			11 => (object) array ( "name" => "Celkový dojem", "title" => "Seřadit podle celkového dojmu", "sort" => 11 ),
			12 => (object) array ( "name" => "Celková cena", "title" => "Seřadit podle celkové ceny", "sort" => 12 ),
			13 => (object) array ( "name" => "Interiér", "title" => "Seřadit podle dojmu z interiéru", "sort" => 13 ),
			14 => (object) array ( "name" => "Exteriér", "title" => "Seřadit podle dojmu z exteriéru", "sort" => 14 ),
			//15 => (object) array ( "name" => "Vzdálenost", "title" => "Seřadit podle vzdálenosti", "sort" => 15 ),
			//16 => (object) array ( "name" => "Čas vložení", "title" => "Seřadit podle času vložení", "sort" => 16 ),
			//17 => (object) array ( "name" => "Čas poslední úpravy", "title" => "Seřadit podle času poslední úpravy", "sort" => 17 ),
		);

		$this["paginator"]->getPaginator()->itemsPerPage = 50;
	}

	protected function setTemplateParameters ()
	{
		$this -> template -> orders = $this -> order;
		$this -> template -> modes = $this -> modes;
		$this -> template -> orderBy = $this -> orderBy;
	


		$this -> template -> sortmenu = $this -> menu;

		if( $this -> isAjax () )
			$this -> invalidateControl ( "pubs" );
	}
	
	public function renderAll( $sort, $mode = false )
	{
		$this -> sort ( $sort, $mode );
	}

	public function renderBeer( $sort, $mode = false )
	{
		$this -> sort ( $sort, $mode );
		foreach ( [6,7,8,9,10,11,12,13,14,15,16,17] as $i )
			unset( $this -> template -> sortmenu[$i] );		
	}

	public function renderWine( $sort, $mode = false )
	{
		$this -> sort ( $sort, $mode );
		foreach ( [4,5,8,9,10,11,12,13,14,15,16,17] as $i )
			unset( $this -> template -> sortmenu[$i] );		
	}

	public function sort ( $sort, $mode = false )
	{
		$sort = (int) $sort;
		$mode = (boolean) $mode;
		$this -> setTemplateParameters ();

		if ( $sort > self::SORT_MAX )
			$this -> redirect ( "this", [ "sort" => self::SORT_MAX ] );
			//$sort = self::SORT_MAX;
		if ( $sort < self::SORT_MIN )
			$this -> redirect ( "this", [ "sort" => self::SORT_MIN ] );
			//$sort = self::SORT_MIN;

		$default = $this -> order [ $sort ];
		$inverse = $default === "ASC" ? "DESC" : "ASC";
		$mode = $mode ? $inverse : $default;

		$paginator = $this [ "paginator" ] -> getPaginator ();
		$paginator -> itemCount = $this -> pubs -> count ( [ "hidden" => false ] );

		/*$this -> template -> pubs = $this -> pubs -> limit ( $paginator -> itemsPerPage, 
															 $paginator -> offset, 
															 array_merge( [ "hidden" => false ], [] ), 
															 $this -> orderBy [ $sort ] );
		*/
		$this -> template -> pubs = $this -> pubs -> sort ( $paginator -> itemsPerPage,
															$paginator -> offset,
															$this -> orderBy [ $sort ], 
															$mode );
		$this -> template -> sort = $sort;
		$this -> template -> mode = $mode;

		if($this->isAjax()){
			$this -> invalidateControl ( 'pubs' );
		}		
	}

	public function renderDefault( $sort, $mode = false )
	{
		$this -> sort ( $sort, $mode );
		
		foreach ( [4,5,6,7,15,16,17] as $i )
			unset( $this -> template -> sortmenu[$i] );
	}

	public function handleSort ( $sort, $mode )
	{
		$this -> redirect ( "this", [ "sort" => (int) $sort, "mode" => (boolean) $mode ] );
	}



	public function handleLocation ( $lat, $lng )
	{

		$session = $this -> getSession ( 'coords' );
		$session -> lat = $lat;
		$session -> lng = $lng;

		$this -> redirect ( "this", array (
									"lat" => $lat,
									"lng" => $lng,
									"sort" => 15,
									"mode" => true ) );
	}

	public function renderTrial( $sort, $mode = false )
	{
		$this -> renderDefault ( $sort, $mode );
	}

	public function renderHarmonika( $sort, $mode = false )
	{
		$this -> renderDefault ( $sort, $mode );
	}

	public function renderNewest ( $sort, $mode = false )
	{
		$this -> renderDefault ( $sort, $mode );
	}

	public function actionClosest ( $sort = 15, $mode = true, $lat = null, $lng = null )
	{
		$sort = (int) $sort;
		$mode = (boolean) $mode;
		$this -> setTemplateParameters ();

		if ( $this -> getSession () -> hasSection ( 'coords' ) ) {
			$session = $this -> getSession ( 'coords' );
			$lat = $session -> lat;
			$lng = $session -> lng;
		}

		$this -> template -> lat = $lat;
		$this -> template -> lng = $lng;
		if ( $lat === null || $lng === null )
		{
			$lat = 50.083;
			$lng = 14.423;
			$this -> flashMessage ( 
				sprintf ( "Poloha nenalezena, používají se výchozí hodnoty [%f, %f]", $lat, $lng ), 
				'warning' );
		}

		if ( $lat > 90 || $lat < -90 || $lng > 180 || $lng < -180 )
		{
			$this -> flashMessage ( 
				sprintf ( "Souřadnice nejsou ve správném formátu [%f, %f]", $lat, $lng ), 
				'danger' );
			return;
		}

		if ( $sort != 15 ) /* distances */
		{
			$this -> renderDefault ( $sort, $mode );
		} 
		else 
		{
			$this -> template -> pubs = $this -> pubs -> closest ( $lat, $lng, 100, 0, $mode );
		}



		$markers = $this->markers->create();


		
		$markers -> addMarker ( array ( $lat, $lng ), Oli\GoogleAPI\Markers::DROP )
			 	 -> setMessage ( "Vaše poloha", true )
			 	 -> setColor ( 'blue' );

		$this -> template -> distances = [];
		foreach ( $this -> template -> pubs as $pub )
		{
			$link = Nette\Utils\Html::el("a");
			$link -> href ( $this -> link ( "Pub:detail", $pub -> id ) );
			$link -> title = $pub -> name . " detaily";
			$link -> setText ( $pub -> name );

			$markers -> addMarker ( array ( $pub->latitude, $pub->longitude ), Oli\GoogleAPI\Markers::DROP )
				 	 -> setMessage ( (string)$link );

			$this -> template -> distances [ $pub -> id ] = $pub -> distance ( $lat, $lng );
		}
		$markers->fitBounds();
		$this["closestMap"]->addMarkers($markers);

		$this["closestMap"]->setCoordinates(array($lat, $lng));

		$this["coordsForm"] -> setDefaults ( [ "latitude" => $lat, "longitude" => $lng ] );


		$this -> template -> isMobile = Tulinkry\Http\Browser::isMobile ();
		$this -> template -> isAndroid = Tulinkry\Http\Browser::isAndroid ();
		$this -> template -> isIOS = Tulinkry\Http\Browser::isIOS ();

		$this -> template -> sort = $sort;
		$this -> template -> mode = $mode;
	}

	public function actionClosestMap ( $lat = null, $lng = null )
	{
		$this -> actionClosest ( 15, true, $lat, $lng );
	}



	protected function createComponentClosestMap ()
	{
		$map = $this->map->create();

		$map->setProportions('100%', '800px');
		$map->setZoom(15)
		    ->setType(Oli\GoogleAPI\MapAPI::ROADMAP);


		//$map -> isStaticMap ( true );
		//$map -> isScrollable ( false );
		//$map -> isDraggable ( false );

		


		return $map;
	}

	protected function createComponentCoordsForm ( $name )
	{
		$form = new Tulinkry\Application\UI\Form;

		$form -> addText ( "latitude", "Latitude" );
		$form -> addText ( "longitude", "Longitude" );

		$presenter = $this;
		$form -> addSubmit ( "submit", "Použít" )
			  -> onClick [] = function ( $button ) use ( $presenter )
			  {
			  	$values = $button -> form -> values;
				if ( $values [ "latitude" ] > 90 || $values [ "latitude" ] < -90 ||
					 $values [ "longitude" ] > 180 || $values [ "longitude" ] < -180 )
					$button -> form -> addError ( "Nesprávný formát souřadnic" );

				$presenter -> redirect ( "this", array (
											"lat" => $values["latitude"],
											"lng" => $values["longitude"],
											"sort" => 15,
											"mode" => true ) );
			  };

		$form -> getElementPrototype () -> class = "form-inline";


		return $this [ $name ] = $form;
	}

	protected function createComponentMap ()
	{
		$pubs = $this -> pubs;
		return new Multiplier ( function ( $pub_id ) use ( $pubs ) {
			$pub = $pubs -> item ( $pub_id );
			$map = $this->map->create();

			$map->setProportions('100%', '500px');

			if ( $pub )
				$map->setCoordinates(array($pub->latitude, $pub->longitude))
				    ->setZoom(15)
				    ->setType(Oli\GoogleAPI\MapAPI::ROADMAP);

			//$map -> isStaticMap ( true );
			$map -> isScrollable ( false );
			$map -> isDraggable ( false );

			$markers = $this->markers->create();
			//$markers->fitBounds();

			if ( $pub )
				$markers -> addMarker ( array ( $pub->latitude, $pub->longitude ), Oli\GoogleAPI\Markers::DROP )
						 -> setMessage ( $pub->name );

			$map->addMarkers($markers);

			return $map;

		});
	}
}
