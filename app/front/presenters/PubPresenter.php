<?php

namespace FrontModule\Presenters;

use Nette,
	Tulinkry,
	Model,
	FrontModule\Controls,
	Nette\Application\UI\Multiplier,
	FrontModule\Forms,
	Oli;
use Nette\Mail\IMailer;


class PubPresenter extends BasePresenter
{

	/** @inject @var Model\RatingModel */
	public $ratings;
	/** @inject @var Model\DescriptionModel */
	public $descriptions;
	/** @inject @var Model\BeerModel */
	public $beers;
	/** @inject @var Model\BeerLinksModel */
	public $beerlinks;

	/** @inject @var Oli\GoogleAPI\IMapAPI */
	public $map;

	/** @inject @var Oli\GoogleAPI\IMarkers */
	public $markers;


	const SELECT_CLOSEST = 1;
	const SELECT_MAP = 2;
	const SELECT_COORDS = 3;

	private $allowed_types = array ( self::SELECT_MAP, self::SELECT_COORDS, self::SELECT_CLOSEST );

	protected function createComponentSignInForm()
	{
		$form = new Tulinkry\Application\UI\Form;
		$form->addText('email', 'Email:');
			//->setRequired('Vložte emailovou adresu, se kterou jste se registroval.');

		$form->addPassword('password', 'Heslo:');
			//->setRequired('Vložte své heslo prosím.');

		$form->addCheckbox('remember', 'Pamatovat si mě');

		$form->addSubmit('send', 'Přihlásit');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = $this->signInFormSucceeded;
		return $form;
	}


	public function startup ()
	{
		parent::startup ();
		$this['signInForm'] -> onSuccess [] = function ($form) {
			echo "papapa";
		};
	}

	public function signInFormSucceeded($form)
	{
		\Tracy\Dumper::dump ( $form->values );

		//$form -> addError ( "Error" );

		return;

		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->getUser()->login($values->email, $values->password);
			$this->restoreRequest($this->backlink);
			$this->redirect(':Front:Pub:default');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function handleShowDialog ()
	{


		$this->dialog()->show( $this['signInForm'] );
		$this->dialog()->title("Sign Form");
		$this -> invalidateControl ("dialog");
		// nebo
		//$this->dialog()->show("Zpráva")->width("600px");
		// nebo
		//$this->dialog()->show(Array("<div class='form'>",$this['myComponent'],"</div>"))->width("600px");
	}

	public function handleCloseDialog()
	{
		$this->dialog()->close();
		$this->invalidateControl ("dialog");
	}


	/**
	 * @inject
	 * @var IMailer 
	 */
	public $mailer;

	private $id;
	private $pub;
	private $rating;

	public function renderDefault()
	{
		$paginator = $this [ "paginator" ] -> getPaginator ();
		$paginator -> itemCount = $this -> pubs -> count ( [ "hidden" => false ] );
    	$paginator -> itemsPerPage = 50;
		$this -> template -> pubs = $this -> pubs -> fetchLimit ( $paginator -> itemsPerPage, $paginator -> offset, [ "hidden" => false ], [ "mark" => "DESC" ] );


		if ( $this -> isAjax () )
			$this -> invalidateControl ( "pubs" );

	}

	protected function loadPub ( $id )
	{
		$this -> pubs -> refresh ( $this -> pubs -> item ( $id ) );
		$this -> template -> pub = $this -> pubs -> item ( $id );
		$this -> id = $id;
		$this -> pub = $this -> template -> pub;
		if ( ! $this -> pub )
		{
			$this -> flashMessage ( "Restaurace neexistuje, vyberte jinou" );
			$this -> redirect ( "Pub:default" );
		}
	}



	public function actionSearch($term = "")
	{
		if($this->session->hasSection('search')){
			$search = $this->session->getSection('search');
			if(isset($search->search) && isset($search->search->term)){
				$term = $search->search->term;
				$fields = $search->search->fields;
			}
		}
		$paginator = $this [ "paginator" ] -> getPaginator ();
		$res = $this -> pubs -> search ( $term, $fields);
		$paginator -> itemCount = count($res);
    	$paginator -> itemsPerPage = 50;
		$this -> template -> pubs = $this -> pubs -> search ( $term, $fields, $paginator -> itemsPerPage, $paginator -> offset );
		$this -> template -> count = count($res);


		if ( $this -> isAjax () )
			$this -> invalidateControl ( "pubs" );

	}



	public function actionDetail ( $id )
	{
		$this -> loadPub ( $id );
		$this -> template -> images = Model\PubModel::getImages ( $this -> pub );

		$paginator = $this [ "paginator2" ] -> getPaginator ();
		$paginator -> itemCount = count ( $this -> template -> images );

		$imgs = [];
		$it = -1;
		foreach ( $this -> template -> images as $key => $entity )
		{
			$it ++;
			if ( $it < $paginator -> offset || $it >= ( $paginator -> offset + $paginator -> itemsPerPage ) )
				continue;
			$imgs [] = $entity;
		}

		$this -> template -> descriptionFormVisible = false;


		$this -> template -> lat = $this->pub->latitude;
		$this -> template -> lng = $this->pub->longitude;
		$this -> template -> isMobile = Tulinkry\Http\Browser::isMobile ();
		$this -> template -> isAndroid = Tulinkry\Http\Browser::isAndroid ();
		$this -> template -> isIOS = Tulinkry\Http\Browser::isIOS ();

		$this -> template -> images = $imgs;
		$this -> template -> paginator2 = $this["paginator2"]->getPaginator();
		$this -> invalidateControl ( "pics" );
				
	}

	public function renderDetail ( $id )
	{
		//$this -> pubs -> detach ( $this -> pubs -> item ( $id ) );
		//$this -> template -> pub = $this -> pubs -> item ( $id );

	}

	public function convertTimeNumber ($num, $names) 
	{
		switch ($num) {
			case $num > (365*24*60*60):
				return floor( $num/(365*24*60*60)) . " " . $names['year'];
			case $num > (30*24*60*60):
				return floor( $num/(30*24*60*60)) . " " . $names['month'];
			case $num > (24*60*60):
				return floor( $num/(24*60*60)) . " " . $names['day'];
			case $num > (60*60):
				return floor( $num/(60*60)) . " " . $names['hour'];
			case $num > 60:
				return floor( $num/(60)) . " " . $names['minute'];
			default:
				return floor($num) . " " . $names['second'];
		}
	}

	public function actionInfo ( $id )
	{
		if ( ! $this -> user -> isAllowed ( 'frontend') )
		{
			$this -> flashMessage ( "Pro hodnocení se musíte přihlásit.", "warning" );
			$this -> redirectLogin ();
		}

		$this -> loadPub ( $id );

		$lasts = $this -> ratings -> by ( [ "user" => $this -> user -> id, "pub" => $this -> pub -> id ], [ "date" => "DESC" ] );
		$this->rating = $rating = count($lasts) ? $lasts[0] : NULL;

		if($rating && $rating->date->getTimestamp() > (time () - Model\RatingModel::RATING_CLOSURE)){
			$str = sprintf ( 'Stále ještě můžete upravovat své hodnocení, zbývá %.1f hodin.', 
				round(($rating->date->getTimestamp()-(time () - Model\RatingModel::RATING_CLOSURE)) / 3600, 1 ));
			$this -> flashMessage ( $str );
			return;
		}

		if($rating && $rating->date->getTimestamp() > (time () - Model\RatingModel::RATING_INTERVAL)){
			$n = [ "year" => "roků", "month" => "měsíců", "day" => "dní", "hour" => "hodin", "minute" => "minut", "second" => "sekund" ];
			$str = sprintf ( 'Bohužel tuto restauraci jste v poslední době již hodnotil. Příští hodnocení budete moci vložit za %s.', 
				$this->convertTimeNumber(($rating->date->getTimestamp()-(time () - Model\RatingModel::RATING_INTERVAL)), $n) );


			$this -> flashMessage ( $str, 'danger' );
		}
	}



	public function renderInsert ( $type = self::SELECT_MAP, $step = 1, $placeid = null )
	{
		if ( ! in_array ( $type, $this -> allowed_types ) )
		{
			$this -> flashMessage ( "Vybraný typ není podporován", 'error' );
			$this -> redirect ( 'Pub:default' );
		}
		$this -> template -> type = $type;
		$this -> template -> step = $step;

		if ( $type === self::SELECT_CLOSEST )
		{
			if ( $step === 1 )
			{
				$this -> template -> coordsExist = false;
				$this -> template -> pubs = [];

				if ( ! $this -> getSession () -> hasSection ( 'coords' ) )
				{
					//$this -> flashMessage ( 'Nepodařilo se zjistit vaše GPS souřadnice. Zkuste to znovu kliknutím na tlačítko "Získat aktuální polohu".', 'warning' );
					return;
				}
				$session = $this -> getSession ( 'coords' );
				$this -> template -> coordsExist = true;

				$lat = $session -> lat;
				$lng = $session -> lng;

				if ( $lat > 90 || $lat < -90 || $lng > 180 || $lng < -180 )
				{
					$this -> flashMessage ( 
						sprintf ( "Souřadnice nejsou ve správném formátu [%f, %f]", $lat, $lng ), 
						'error' );
					return;
				}

				// nearby lookup
				$url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json";

				$query = array ( 
								 'key' => 'AIzaSyCBNGn9ADxqF-jlhzkzq1c0P3cBu_XdM0s',
								 'location' => $lat . "," . $lng,
								 'rankby' => 'distance',
								 'types' => 'food|restaurant|bar|cafe',
								 'language' => 'cs',
								  );

				$url = $url . "?" . http_build_query ( $query );


				//echo $url;
				if ( ( $res = @file_get_contents ( $url ) ) === FALSE )
				{
					$this -> flashMessage ( "Nepodařilo se zjistit nejbližší restaurace. Pokus o kontaktování"
											. " vzdáleného serveru skončil s chybou, opakujte prosím později. Jste připojeni k internetu?", 'error');
					return;
				}

				if ( ( $res = json_decode ( $res ) ) === NULL )
				{
					$this -> flashMessage ( "Nepodařilo se zjistit nejbližší restaurace.", 'error');
					return;
				}

				if ( $res -> status !== "OK" )
				{
					$this -> flashMessage ( "Nepodařilo se zjistit nejbližší restaurace.", 'error');
					return;
				}

				$res = $res -> results;

				//echo "<pre>";
				//print_r ( $res );
				//echo "</pre>";
				foreach ( $res as $pub )
				{
					$p = new \StdClass;
					$p -> name = $pub -> name;
					$p -> lat = $pub -> geometry -> location -> lat;
					$p -> lng = $pub -> geometry -> location -> lng;
					$p -> id = $pub -> place_id;
					$p -> location = $pub -> vicinity;
					$p -> distance = \Tulinkry\Utils\Gps::distance ( $p -> lat, $p -> lng, $lat, $lng );
					$this -> template -> pubs [] = $p;
				}
			} else if ( $step === 2 )
			{
				// pub details lookup
				$url = "https://maps.googleapis.com/maps/api/place/details/json";

				$query = array ( 
								 'key' => 'AIzaSyCBNGn9ADxqF-jlhzkzq1c0P3cBu_XdM0s',
								 'placeid' => $placeid,
								 'language' => 'cs',
								  );

				$url = $url . "?" . http_build_query ( $query );

				if ( ( $res = @file_get_contents ( $url ) ) === FALSE )
				{
					$this -> flashMessage ( "Nepodařilo se zjistit nejbližší restauraci. Pokus o kontaktování"
											. " vzdáleného serveru skončil s chybou, opakujte prosím později. Jste připojeni k internetu?", 'error');
					return;
				}

				if ( ( $res = json_decode ( $res ) ) === NULL )
				{
					$this -> flashMessage ( "Nepodařilo se zjistit podrobnosti o restauraci.", 'error');
					$this -> redirect ( 'this', [ 'step' => 1 ] );
					return;
				}

				if ( $res -> status !== "OK" )
				{
					$this -> flashMessage ( "Nepodařilo se zjistit podrobnosti o restauraci.", 'error');
					$this -> redirect ( 'this', [ 'step' => 1 ] );
					return;
				}

				$res = $res -> result;

				$this [ "pubForm" ] [ "whole_name" ] -> setDefaultValue ( $res -> name );
				$this [ "pubForm" ] [ "name" ] -> setDefaultValue ( $res -> name );
				$this [ "pubForm" ] [ "long_name" ] -> setDefaultValue ( $res -> name );
				if ( isset ( $res -> opening_hours -> weekday_text ) ) {
					$this [ "pubForm" ] [ "opening_hours" ] -> setDefaultValue ( implode ( PHP_EOL, $res -> opening_hours -> weekday_text ) );
					$this [ "pubForm" ] [ "opening_hours" ] -> getControlPrototype () -> rows = count ( $res -> opening_hours -> weekday_text );
				}
				if ( isset ( $res -> website ) ) {
					$this [ "pubForm" ] [ "website" ] -> setDefaultValue ( $res -> website );
				}

				$this [ "pubForm" ] [ "coords" ] -> setDefaultValue ( [ 'lat' => $res -> geometry -> location -> lat,
																		'lng' => $res -> geometry -> location -> lng,
																		'address' => $res -> formatted_address,
																		'location' => $res -> formatted_address ] );


				// locality lookup
				$address = $res -> formatted_address;
				$lat = $res -> geometry -> location -> lat;
				$lng = $res -> geometry -> location -> lng;

				$url = "https://maps.googleapis.com/maps/api/geocode/json";

				$query = array ( 
								 'key' => 'AIzaSyCBNGn9ADxqF-jlhzkzq1c0P3cBu_XdM0s',
								 //'latlng' => $lat . "," . $lng,
								 //'place_id' => $placeid,
								 'address' => $address,
								 'language' => 'cs',
								  );

				$url = $url . "?" . http_build_query ( $query );

				if ( ( $res = @file_get_contents ( $url ) ) === FALSE )
				{
					$this -> flashMessage ( "Nebylo možné zjistit lokalitu oblasti, klikněte prosím na tlačítko 'Najít' vedle pole Adresa.", 'warning');
					return;
				}

				if ( ( $res = json_decode ( $res ) ) === NULL )
				{
					$this -> flashMessage ( "Nebylo možné zjistit lokalitu oblasti, klikněte prosím na tlačítko 'Najít' vedle pole Adresa.", 'warning');
					return;
				}

				if ( $res -> status !== "OK" )
				{
					$this -> flashMessage ( "Nebylo možné zjistit lokalitu oblasti, klikněte prosím na tlačítko 'Najít' vedle pole Adresa.", 'warning');
					return;
				}

				$results = $res -> results;


				if ( count ( $results ) > 0 ) {
					$location = $this -> parseAddressComponents ( $results );
					$location = count($location) ? implode ( ", ", $location ) : $address;
					$this [ "pubForm" ] [ "coords" ] -> setDefaultValue ( [ 'lat' => $lat,
																			'lng' => $lng,
																			'address' => $address,
																			'location' => $location ] );
					
				}

			}
		}

	}

	protected function parseAddressComponents ( $results )
	{
		if (! count($results) )
			return [];

		$parts = [];

		foreach ( $results[0] -> address_components as $component )
		{
			if ( in_array ( "locality", $component->types ) )
				$parts [ 'locality' ] = $component->long_name;
			if ( in_array ( "neighborhood", $component->types ) )
				$parts [ 'neighborhood' ] = $component->long_name;
			if ( in_array ( "country", $component->types ) )
				$parts [ 'country' ] = $component->long_name;


			// sublocality
			if ( in_array( "sublocality", $component->types ) )
				$parts [ 'sublocality' ] = $component->long_name;
			if ( in_array ( "sublocality_level_1", $component->types ) )
				$parts [ "sublocality_level_1" ] = $component->long_name;
			if ( in_array ( "sublocality_level_2'", $component->types ) )
				$parts [ "sublocality_level_2" ] = $component->long_name;
			if ( in_array ( "sublocality_level_3", $component->types ) )
				$parts [ "sublocality_level_3" ] = $component->long_name;
			if ( in_array ( "sublocality_level_4", $component->types ) )
				$parts [ "sublocality_level_4" ] = $component->long_name;
			if ( in_array ( "sublocality_level_5", $component->types ) )
				$parts [ 'sublocality_level_5' ] = $component->long_name;
		}	
		$parts2 = [];

		$sublocality = isset($parts['sublocality'] )|| 
					   isset($parts['sublocality_level_1']) ||
					   isset($parts['sublocality_level_2']) ||
					   isset($parts['sublocality_level_3']) ||
					   isset($parts['sublocality_level_4']) ||
					   isset($parts['sublocality_level_5']);
		$sublocality_name = "";

		if(isset($parts['neighborhood']))
			$parts2 [] = ( $parts ['neighborhood'] );

		for( $i = 5; $i > 0; $i -- )
			if(isset($parts['sublocality_level_' . $i]))
				$sublocality_name = $parts['sublocality_level_' . $i];
		
		if($sublocality_name != "") 
			$parts2 [] = ($sublocality_name);

		if(isset($parts['sublocality']) && $sublocality_name == "") 
			$parts2 [] = ( $parts ['sublocality'] );

		$sublocality_name = $sublocality_name == "" && isset($parts['sublocality']) ? $parts['sublocality'] : $sublocality_name;

		if(isset($parts['locality']) && ! preg_match("/".$parts['locality']."/", $sublocality_name) ) 
			$parts2 [] = ( $parts['locality'] );
		if(isset($parts['country']))
			$parts2 [] = ( $parts['country'] );

		return $parts2;
	}

	public function handleSearch($by)
	{
		$r = $this->beerlinks->by($by);
		$this -> payload -> pubs = $r;
		$this -> sendPayload();
	}

	public function handleEnableDescription ()
	{
		$this -> template -> descriptionFormVisible = true;
		$this -> invalidateControl ('description');
	}

	public function handleLocation ( $lat, $lng )
	{
		$session = $this -> getSession ( 'coords' );
		$session -> setExpiration ( '10 minutes' );
		$session -> lat = $lat;
		$session -> lng = $lng;
		$this -> invalidateControl ('pubs');
		if ( ! $this -> isAjax () )
			$this -> redirect ('this');
	}	


	public function actionImage ( $id )
	{
		if ( ! $this -> user -> isAllowed ( 'frontend') )
		{
			$this -> flashMessage ( "Pro nahrávání obrázků se musíte přihlásit.", "warning" );
			$this -> redirectLogin ();
		}

		$this -> loadPub ( $id );
	}


	protected function createComponentImageForm ( $name )
	{
		return $this [ $name ] = new Forms\ImageForm ( $this -> pubs, $this -> pub );
	}

	protected function createComponentRatingForm ( $name )
	{
		return $this [ $name ] = new Forms\RatingForm ( $this -> pubs, 
														$this -> ratings, 
														$this -> beers, 
														$this -> users, 
														$this -> rating, 
														$this -> pub -> id );
	}
	
	protected function createComponentBeerForm ( $name )
	{
		$form = new Forms\BeerForm ( $this -> beers, null );
		$form -> getElementPrototype () -> addClass ( "ajax" );

		$presenter = $this;
		$form -> onError [] = function ($f) use ($presenter) {
			$presenter -> template -> beerFormVisible = true;
			$presenter -> invalidateControl ( 'beerForm' );
			$presenter -> invalidateControl ( 'beerFormVisible' );
		};

		$form -> onSubmit [] = function ($f) use ($presenter) {
			if ($f->isValid()) {
				$presenter -> template -> beerFormVisible = false;
				$presenter -> flashMessage ( "Nové pivo úspěšně přidáno" );
				$presenter -> invalidateControl ( 'beerForm' );
				$presenter -> invalidateControl ( 'beerFormVisible' );
			}
		};

		return $this [ $name ] = $form;
	}

	protected function createComponentPubForm ( $name )
	{
		$form = new Forms\PubForm ( $this -> pubs, $this -> users, $this -> parameters );
		$params = $this -> parameters -> params;
		$presenter = $this;
		$form -> onCreate [] = function ( $pub ) use ( $presenter, $params )
		{

			$p = [ "presenter" => $presenter,
						"pub" => $pub ];
		
			$latte = new \Latte\Engine;

			$str = $latte -> renderToString ( __DIR__ . "/templates/Pub/pubApproveEmail.latte", $p );


			if ( ! array_key_exists ( "approvePub", $params ) )
				throw new Tulinkry\Exception ( "Config section 'approvePub' is missing in parameters." );
			 $approvePub = $params [ "approvePub" ];

			if ( ! array_key_exists ( "to", $approvePub ) )
				throw new Tulinkry\Exception ( "Config section 'to' is missing in parameters['approvePub']." );
			$to = $approvePub [ "to" ];

			if ( ! array_key_exists ( "from", $approvePub ) )
				throw new Tulinkry\Exception ( "Config section 'from' is missing in parameters['approvePub']." );
			$from = $approvePub [ "from" ];

			$subject_template = "Sdělení stránky - %s";
			if ( array_key_exists ( "subject", $approvePub ) &&
				 count ( explode ( "%s", $approvePub [ "subject" ] ) ) === 2 )
				$subject_template = $approvePub [ "subject" ];

			$subject = sprintf ( $subject_template, "Nová restaurace - " . $pub -> whole_name );
		
			if ( ! is_array ( $to ) )
				$to = array ( $to );

			$message = new \Nette\Mail\Message;
			$message -> setFrom ( $from )
				     -> setSubject ( $subject )
				     -> setHtmlBody ( $str );

			foreach ( $to as $recipient )
				$message-> addTo ( $recipient );

			$presenter -> mailer -> send ( $message );

		};
		return $this [ $name ] = $form;
	}

	protected function createComponentAlternatePubForm ( $name )
	{
		$form = new Forms\AlternatePubForm ( $this -> pubs, $this -> users, $this->parameters );
		$form -> onCreate = $this [ "pubForm" ] -> onCreate;
		

		return $this [ $name ] = $form;
	}

	protected function createComponentDescriptionForm ( $name )
	{
		$form = new Forms\DescriptionForm ( $this -> descriptions, $this -> users, $this -> pub, null );
		return $this [ $name ] = $form;
	}


	protected function createComponentMap ()
	{
		$map = $this->map->create();

		$map->setProportions('100%', '500px');
		if ( $this->pub )
			$map->setCoordinates(array($this->pub->latitude, $this->pub->longitude))
			    ->setZoom(15)
			    ->setType(Oli\GoogleAPI\MapAPI::ROADMAP);

		//$map -> isStaticMap ( true );
		$map -> isScrollable ( false );
		$map -> isDraggable ( false );

		$markers = $this->markers->create();
		//$markers->fitBounds();

		if ( $this->pub )
			$markers -> addMarker ( array ( $this->pub->latitude, $this->pub->longitude ), Oli\GoogleAPI\Markers::DROP )
					 -> setMessage ( $this->pub->name );

		$map->addMarkers($markers);
		return $map;
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

}
