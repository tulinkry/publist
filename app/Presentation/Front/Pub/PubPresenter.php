<?php

namespace App\Presentation\Front\Pub;

use Nette;
use Tulinkry;
use App\Forms\Front;
use Nette\Mail\IMailer;

class PubPresenter extends \App\Presentation\Front\BasePresenter
{
	/** @inject @var \App\Model\RatingModel */
	public $ratings;
	/** @inject @var \App\Model\DescriptionModel */
	public $descriptions;
	/** @inject @var \App\Model\BeerModel */
	public $beers;
	/** @inject @var \App\Model\BeerLinksModel */
	public $beerlinks;
	/** @inject @var \App\Model\BeerRatingModel */
	public $beerRatings;


	public const SELECT_CLOSEST = 1;
	public const SELECT_MAP = 2;
	public const SELECT_COORDS = 3;

	private $allowed_types = array( self::SELECT_MAP, self::SELECT_COORDS, self::SELECT_CLOSEST );

	protected function createComponentSignInForm()
	{
		$form = new Front\DialogSignInForm();
		// call method signInFormSucceeded() on success
		$form->onSuccess[] = [$this, 'signInFormSucceeded'];
		return $form;
	}

	public function signInFormSucceeded($form, $values)
	{
		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', false);
		} else {
			$this->getUser()->setExpiration('20 minutes', true);
		}

		try {
			$this->getUser()->login($values->email, $values->password);
			$this->restoreRequest($this->backlink);
			$this->redirect('this');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function handleShowDialog()
	{


		$this->dialog()->show($this['signInForm']);
		$this->dialog()->title("Sign Form");
		$this->redrawControl("dialog");
		// nebo
		//$this->dialog()->show("Zpráva")->width("600px");
		// nebo
		//$this->dialog()->show(Array("<div class='form'>",$this['myComponent'],"</div>"))->width("600px");
	}

	public function handleCloseDialog()
	{
		$this->dialog()->close();
		$this->redrawControl("dialog");
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
		$paginator = $this [ "paginator" ]->getPaginator();
		$paginator->itemCount = $this->pubs->count([ "hidden" => false ]);
		$paginator->itemsPerPage = 50;
		$this->template->pubs = $this->pubs->limit($paginator->itemsPerPage, $paginator->offset, [ "hidden" => false ], [ "mark" => "DESC" ]);


		if ($this->isAjax()) {
			$this->redrawControl("pubs");
		}

	}

	protected function loadPub($id)
	{
		$this->template->pub = $this->pubs->item($id);
		$this->id = $id;
		$this->pub = $this->template->pub;
		if (!$this->pub) {
			$this->flashMessage("Restaurace neexistuje, vyberte jinou");
			$this->redirect("Pub:default");
		}
	}



	public function actionSearch($term = "")
	{
		$fields = ['whole_name']; // SearchForm's own default selected field
		if ($this->getSession()->hasSection('search')) {
			$search = $this->getSession()->getSection('search');
			if (isset($search->search) && isset($search->search->term)) {
				$term = $search->search->term;
				$fields = $search->search->fields;
			}
		}
		$paginator = $this [ "paginator" ]->getPaginator();
		$res = $this->pubs->search($term, $fields);
		$paginator->itemCount = count($res);
		$paginator->itemsPerPage = 50;
		$this->template->pubs = $this->pubs->search($term, $fields, $paginator->itemsPerPage, $paginator->offset);
		$this->template->count = count($res);


		if ($this->isAjax()) {
			$this->redrawControl("pubs");
		}

	}



	public function actionDetail($id)
	{
		$this->loadPub($id);
		$this->template->images = \App\Model\PubModel::getImages($this->pub);

		$paginator = $this [ "paginator2" ]->getPaginator();
		$paginator->itemCount = count($this->template->images);

		$imgs = [];
		$it = -1;
		foreach ($this->template->images as $key => $entity) {
			$it++;
			if ($it < $paginator->offset || $it >= ($paginator->offset + $paginator->itemsPerPage)) {
				continue;
			}
			$imgs [] = $entity;
		}

		$this->template->descriptionFormVisible = false;


		$this->template->lat = $this->pub->latitude;
		$this->template->lng = $this->pub->longitude;
		$this->template->isMobile = Tulinkry\Http\Browser::isMobile();
		$this->template->isAndroid = Tulinkry\Http\Browser::isAndroid();
		$this->template->isIOS = Tulinkry\Http\Browser::isIOS();

		// Client-side map (see app/Presentation/Front/presenters/templates/components/googleMap.latte):
		// a single marker on the pub's own location. Replaces the former
		// Oli\GoogleAPI server-side map/markers DI services (createComponentMap()),
		// which relied on an abandoned, already-non-functional composer package.
		$this->template->mapId = 'pub-map';
		$this->template->mapCenter = [ $this->pub->latitude, $this->pub->longitude ];
		$this->template->mapZoom = 15;
		$this->template->mapMarkers = [
			[
				'lat' => $this->pub->latitude,
				'lng' => $this->pub->longitude,
				'title' => $this->pub->name,
			],
		];

		$this->template->images = $imgs;
		$this->template->paginator2 = $this["paginator2"]->getPaginator();
		$this->redrawControl("pics");

	}

	public function renderDetail($id)
	{
		//$this -> pubs -> detach ( $this -> pubs -> item ( $id ) );
		//$this -> template -> pub = $this -> pubs -> item ( $id );

	}

	public function actionInfo($id)
	{
		if (!$this->user->isAllowed('frontend')) {
			$this->flashMessage("Pro hodnocení se musíte přihlásit.", "warning");
			$this->redirectLogin();
		}

		$this->loadPub($id);

		$this->rating = $rating = $this->ratings->by([ "user_id" => $this->user->id, "pub_id" => $this->pub->id ], [ "date" => "DESC" ])->fetch();

		if ($rating && $rating->date->getTimestamp() > (time() - \App\Model\RatingModel::RATING_CLOSURE)) {
			$str = sprintf('Stále ještě můžete upravovat své hodnocení, zbývá %.1f hodin.',
				round(($rating->date->getTimestamp() - (time() - \App\Model\RatingModel::RATING_CLOSURE)) / 3600, 1));
			$this->flashMessage($str);
			return;
		}

		if ($rating && $rating->date->getTimestamp() > (time() - \App\Model\RatingModel::RATING_INTERVAL)) {
			$n = [ "year" => "roků", "month" => "měsíců", "day" => "dní", "hour" => "hodin", "minute" => "minut", "second" => "sekund" ];
			$str = sprintf('Bohužel tuto restauraci jste v poslední době již hodnotil. Příští hodnocení budete moci vložit za %s.',
				\App\Model\RatingModel::formatDuration(($rating->date->getTimestamp() - (time() - \App\Model\RatingModel::RATING_INTERVAL)), $n));


			$this->flashMessage($str, 'danger');
		}
	}



	public function renderInsert($type = self::SELECT_MAP, $step = 1, $placeid = null)
	{
		if (!in_array($type, $this->allowed_types)) {
			$this->flashMessage("Vybraný typ není podporován", 'error');
			$this->redirect('Pub:default');
		}
		$this->template->type = $type;
		$this->template->step = $step;

		if ($type === self::SELECT_CLOSEST) {
			if ($step === 1) {
				$this->template->coordsExist = false;
				$this->template->pubs = [];

				if (!$this->getSession()->hasSection('coords')) {
					//$this -> flashMessage ( 'Nepodařilo se zjistit vaše GPS souřadnice. Zkuste to znovu kliknutím na tlačítko "Získat aktuální polohu".', 'warning' );
					return;
				}
				$session = $this->getSession('coords');
				$this->template->coordsExist = true;

				$lat = $session->lat;
				$lng = $session->lng;

				if (!\App\Model\PubModel::isValidCoordinate($lat, $lng)) {
					$this->flashMessage(
						sprintf("Souřadnice nejsou ve správném formátu [%f, %f]", $lat, $lng),
						'error');
					return;
				}

				// nearby lookup
				$url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json";

				$query = array(
								 'key' => $this->parameters->params [ 'googleMapsApiKey' ],
								 'location' => $lat . "," . $lng,
								 'rankby' => 'distance',
								 'types' => 'food|restaurant|bar|cafe',
								 'language' => 'cs',
								  );

				$url = $url . "?" . http_build_query($query);


				//echo $url;
				if (($res = @file_get_contents($url)) === false) {
					$this->flashMessage("Nepodařilo se zjistit nejbližší restaurace. Pokus o kontaktování"
											. " vzdáleného serveru skončil s chybou, opakujte prosím později. Jste připojeni k internetu?", 'error');
					return;
				}

				if (($res = json_decode($res)) === null) {
					$this->flashMessage("Nepodařilo se zjistit nejbližší restaurace.", 'error');
					return;
				}

				if ($res->status !== "OK") {
					$this->flashMessage("Nepodařilo se zjistit nejbližší restaurace.", 'error');
					return;
				}

				$res = $res->results;

				//echo "<pre>";
				//print_r ( $res );
				//echo "</pre>";
				foreach ($res as $pub) {
					$p = new \StdClass();
					$p->name = $pub->name;
					$p->lat = $pub->geometry->location->lat;
					$p->lng = $pub->geometry->location->lng;
					$p->id = $pub->place_id;
					$p->location = $pub->vicinity;
					$p->distance = \Tulinkry\Utils\Gps::distance($p->lat, $p->lng, $lat, $lng);
					$this->template->pubs [] = $p;
				}
			} elseif ($step === 2) {
				// pub details lookup
				$url = "https://maps.googleapis.com/maps/api/place/details/json";

				$query = array(
								 'key' => $this->parameters->params [ 'googleMapsApiKey' ],
								 'placeid' => $placeid,
								 'language' => 'cs',
								  );

				$url = $url . "?" . http_build_query($query);

				if (($res = @file_get_contents($url)) === false) {
					$this->flashMessage("Nepodařilo se zjistit nejbližší restauraci. Pokus o kontaktování"
											. " vzdáleného serveru skončil s chybou, opakujte prosím později. Jste připojeni k internetu?", 'error');
					return;
				}

				if (($res = json_decode($res)) === null) {
					$this->flashMessage("Nepodařilo se zjistit podrobnosti o restauraci.", 'error');
					$this->redirect('this', [ 'step' => 1 ]);
					return;
				}

				if ($res->status !== "OK") {
					$this->flashMessage("Nepodařilo se zjistit podrobnosti o restauraci.", 'error');
					$this->redirect('this', [ 'step' => 1 ]);
					return;
				}

				$res = $res->result;

				$this [ "pubForm" ] [ "whole_name" ]->setDefaultValue($res->name);
				$this [ "pubForm" ] [ "name" ]->setDefaultValue($res->name);
				$this [ "pubForm" ] [ "long_name" ]->setDefaultValue($res->name);
				if (isset($res->opening_hours->weekday_text)) {
					$this [ "pubForm" ] [ "opening_hours" ]->setDefaultValue(implode(PHP_EOL, $res->opening_hours->weekday_text));
					$this [ "pubForm" ] [ "opening_hours" ]->getControlPrototype()->rows = count($res->opening_hours->weekday_text);
				}
				if (isset($res->website)) {
					$this [ "pubForm" ] [ "website" ]->setDefaultValue($res->website);
				}

				$this [ "pubForm" ] [ "coords" ]->setDefaultValue([ 'lat' => $res->geometry->location->lat,
																		'lng' => $res->geometry->location->lng,
																		'address' => $res->formatted_address,
																		'location' => $res->formatted_address ]);


				// locality lookup
				$address = $res->formatted_address;
				$lat = $res->geometry->location->lat;
				$lng = $res->geometry->location->lng;

				$url = "https://maps.googleapis.com/maps/api/geocode/json";

				$query = array(
								 'key' => $this->parameters->params [ 'googleMapsApiKey' ],
								 //'latlng' => $lat . "," . $lng,
								 //'place_id' => $placeid,
								 'address' => $address,
								 'language' => 'cs',
								  );

				$url = $url . "?" . http_build_query($query);

				if (($res = @file_get_contents($url)) === false) {
					$this->flashMessage("Nebylo možné zjistit lokalitu oblasti, klikněte prosím na tlačítko 'Najít' vedle pole Adresa.", 'warning');
					return;
				}

				if (($res = json_decode($res)) === null) {
					$this->flashMessage("Nebylo možné zjistit lokalitu oblasti, klikněte prosím na tlačítko 'Najít' vedle pole Adresa.", 'warning');
					return;
				}

				if ($res->status !== "OK") {
					$this->flashMessage("Nebylo možné zjistit lokalitu oblasti, klikněte prosím na tlačítko 'Najít' vedle pole Adresa.", 'warning');
					return;
				}

				$results = $res->results;


				if (count($results) > 0) {
					$location = $this->parseAddressComponents($results);
					$location = count($location) ? implode(", ", $location) : $address;
					$this [ "pubForm" ] [ "coords" ]->setDefaultValue([ 'lat' => $lat,
																			'lng' => $lng,
																			'address' => $address,
																			'location' => $location ]);

				}

			}
		}

	}

	protected function parseAddressComponents($results)
	{
		if (!count($results)) {
			return [];
		}

		$parts = [];

		foreach ($results[0]->address_components as $component) {
			if (in_array("locality", $component->types)) {
				$parts [ 'locality' ] = $component->long_name;
			}
			if (in_array("neighborhood", $component->types)) {
				$parts [ 'neighborhood' ] = $component->long_name;
			}
			if (in_array("country", $component->types)) {
				$parts [ 'country' ] = $component->long_name;
			}


			// sublocality
			if (in_array("sublocality", $component->types)) {
				$parts [ 'sublocality' ] = $component->long_name;
			}
			if (in_array("sublocality_level_1", $component->types)) {
				$parts [ "sublocality_level_1" ] = $component->long_name;
			}
			if (in_array("sublocality_level_2'", $component->types)) {
				$parts [ "sublocality_level_2" ] = $component->long_name;
			}
			if (in_array("sublocality_level_3", $component->types)) {
				$parts [ "sublocality_level_3" ] = $component->long_name;
			}
			if (in_array("sublocality_level_4", $component->types)) {
				$parts [ "sublocality_level_4" ] = $component->long_name;
			}
			if (in_array("sublocality_level_5", $component->types)) {
				$parts [ 'sublocality_level_5' ] = $component->long_name;
			}
		}
		$parts2 = [];

		$sublocality = isset($parts['sublocality']) ||
					   isset($parts['sublocality_level_1']) ||
					   isset($parts['sublocality_level_2']) ||
					   isset($parts['sublocality_level_3']) ||
					   isset($parts['sublocality_level_4']) ||
					   isset($parts['sublocality_level_5']);
		$sublocality_name = "";

		if (isset($parts['neighborhood'])) {
			$parts2 [] = ($parts ['neighborhood']);
		}

		for ($i = 5; $i > 0; $i--) {
			if (isset($parts['sublocality_level_' . $i])) {
				$sublocality_name = $parts['sublocality_level_' . $i];
			}
		}

		if ($sublocality_name != "") {
			$parts2 [] = ($sublocality_name);
		}

		if (isset($parts['sublocality']) && $sublocality_name == "") {
			$parts2 [] = ($parts ['sublocality']);
		}

		$sublocality_name = $sublocality_name == "" && isset($parts['sublocality']) ? $parts['sublocality'] : $sublocality_name;

		if (isset($parts['locality']) && !preg_match("/".$parts['locality']."/", $sublocality_name)) {
			$parts2 [] = ($parts['locality']);
		}
		if (isset($parts['country'])) {
			$parts2 [] = ($parts['country']);
		}

		return $parts2;
	}

	public function handleSearch(string $by)
	{
		$r = $this->beerlinks->by($by);
		$this->payload->pubs = $r;
		$this->sendPayload();
	}

	public function handleEnableDescription()
	{
		$this->template->descriptionFormVisible = true;
		$this->redrawControl('description');
	}

	public function handleLocation($lat, $lng)
	{
		$session = $this->getSession('coords');
		$session->setExpiration('10 minutes');
		$session->lat = $lat;
		$session->lng = $lng;
		$this->redrawControl('pubs');
		if (!$this->isAjax()) {
			$this->redirect('this');
		}
	}


	public function actionImage($id)
	{
		if (!$this->user->isAllowed('frontend')) {
			$this->flashMessage("Pro nahrávání obrázků se musíte přihlásit.", "warning");
			$this->redirectLogin();
		}

		$this->loadPub($id);
	}


	protected function createComponentImageForm($name)
	{
		return $this [ $name ] = new Front\ImageForm($this->pubs, $this->pub);
	}

	protected function createComponentRatingForm($name)
	{
		return $this [ $name ] = new Front\RatingForm($this->pubs,
			$this->ratings,
			$this->beers,
			$this->users,
			$this->rating,
			$this->pub->id,
			$this->beerRatings);
	}

	protected function createComponentBeerForm($name)
	{
		$form = new Front\BeerForm($this->beers, null);
		$form->getElementPrototype()->addClass("ajax");

		$presenter = $this;
		$form->onError [] = function ($f) use ($presenter) {
			$presenter->template->beerFormVisible = true;
			$presenter->redrawControl('beerForm');
			$presenter->redrawControl('beerFormVisible');
		};

		$form->onSubmit [] = function ($f) use ($presenter) {
			if ($f->isValid()) {
				$presenter->template->beerFormVisible = false;
				$presenter->flashMessage("Nové pivo úspěšně přidáno");
				$presenter->redrawControl('beerForm');
				$presenter->redrawControl('beerFormVisible');
				// so the new beer shows up in the rating form's "Značka"
				// select without a page reload
				$presenter->redrawControl('ratingForm');
			}
		};

		return $this [ $name ] = $form;
	}

	protected function createComponentPubForm($name)
	{
		$form = new Front\PubForm($this->pubs, $this->users, $this->parameters, $this->descriptions);
		$params = $this->parameters->params;
		$presenter = $this;
		$form->onCreate [] = function ($pub) use ($presenter, $params) {

			$p = [ "presenter" => $presenter,
						"pub" => $pub ];

			$latte = new \Latte\Engine();

			$str = $latte->renderToString(__DIR__ . "/pubApproveEmail.latte", $p);


			if (!array_key_exists("approvePub", $params)) {
				throw new Tulinkry\Exception("Config section 'approvePub' is missing in parameters.");
			}
			$approvePub = $params [ "approvePub" ];

			if (!array_key_exists("to", $approvePub)) {
				throw new Tulinkry\Exception("Config section 'to' is missing in parameters['approvePub'].");
			}
			$to = $approvePub [ "to" ];

			if (!array_key_exists("from", $approvePub)) {
				throw new Tulinkry\Exception("Config section 'from' is missing in parameters['approvePub'].");
			}
			$from = $approvePub [ "from" ];

			$subject_template = "Sdělení stránky - %s";
			if (array_key_exists("subject", $approvePub) &&
				 count(explode("%s", $approvePub [ "subject" ])) === 2) {
				$subject_template = $approvePub [ "subject" ];
			}

			$subject = sprintf($subject_template, "Nová restaurace - " . $pub->whole_name);

			if (!is_array($to)) {
				$to = array( $to );
			}

			$message = new \Nette\Mail\Message();
			$message->setFrom($from)
					 ->setSubject($subject)
					 ->setHtmlBody($str);

			foreach ($to as $recipient) {
				$message->addTo($recipient);
			}

			$presenter->mailer->send($message);

		};
		return $this [ $name ] = $form;
	}

	protected function createComponentAlternatePubForm($name)
	{
		$form = new Front\AlternatePubForm($this->pubs, $this->users, $this->parameters, $this->descriptions);
		$form->onCreate = $this [ "pubForm" ]->onCreate;


		return $this [ $name ] = $form;
	}

	protected function createComponentDescriptionForm($name)
	{
		$lastDescription = $this->pub ? $this->pubs->lastDescription($this->pub) : null;
		$form = new Front\DescriptionForm($this->descriptions, $this->users, $this->pub, null, $lastDescription);
		return $this [ $name ] = $form;
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

}
