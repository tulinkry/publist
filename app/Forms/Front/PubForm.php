<?php

namespace App\Forms\Front;

use Tulinkry\Forms;
use Nette\Utils\Html;
use Nette\Neon\Neon;
use Tulinkry;

class PubForm extends Forms\Form
{
	protected $model;
	protected $users;
	protected $parametres;
	protected $descriptions;

	public $onCreate = array();

	protected $types = array(
		//[Aa][Uu][Rr][Aa]([Cc][Ee]|[Nn][Tt]
		"Restaurace" => "\s*?Rest[^-\s]*\s*",
		"Bar" => "\s*?Bar[^-\s]*\s*",
		"Klub" => "\s*?[CK]lub\s*",
		"Pizzerie" => "\s*?Piz[^-\s]*\s*",
		"Hospoda" => "\s*?Hosp[^-\s]*\s*",
		//"Hospůdka" => "\s*?Hospů[^-\s]*\s*",
		"Pivnice" => "\s*?Pivni[^-\s]*\s*",
		"Bufet" => "\s*?Buf[^-\s]*\s*",
		"Kavárna" => "\s*?(Kavár|Kaf|Caf)[^-\s]*\s*",
	);

	public function __construct($model, $users, $parametres, $descriptions = null)
	{
		parent::__construct();

		if (!(isset($parametres->params['pub']) && isset($parametres->params['pub']['typeFile']))) {
			throw new \Nette\InvalidArgumentException("PubForm: configuration section 'pub' and parameter 'typeFile' are missing");
		}

		$typeFile = $parametres->params['pub']['typeFile'];
		if (file_exists($typeFile)) {
			$res = @file_get_contents($typeFile);
			if ($res !== false) {
				$string = Neon::decode($res);
				if (isset($string['types'])) {
					$this->types = $string['types'];
				}
			}
		}


		$this->getElementPrototype()->class = "pubForm";

		$this->model = $model;
		$this->users = $users;
		$this->parametres = $parametres;
		$this->descriptions = $descriptions;

		$agreement = $this->addCheckbox("agreement", "Souhlasím s duplicitním názvem")
			  ->setAttribute("class", "switch")
			  ->setAttribute('data-on-text', 'Ano')
			  ->setAttribute('data-off-text', 'Ne')
			  ->setDefaultValue(0)
			  ->setOption("hidden", true);
		$agreementPrototype = $agreement->getControlPrototype();
		$agreementPrototype->data [ 'content' ] = "Zaklikněte, pokud jste si jistí, že chcete vložit restauraci se stejným jménem jako již existující.";
		$agreementPrototype->data [ 'heading' ] = "Pomocník";


		$whole_name = $this->addText("whole_name", "Celé jméno")
					  ->setAttribute("placeholder", "Hospůdka U Raka")
					  ->setRequired()
					  ->setOption('description', "Zadejte celé jméno restaurace, po vyplnění se vám následující dvě políčka vyplní automaticky.");
		$whole_name = $whole_name->getControlPrototype();
		$whole_name->id = "pubForm-whole_name";
		$whole_name->data [ 'content' ] = "Napište celé jméno restaurace např. 'Restaurace Na Kopečku', v lepším případě se vám automaticky předvyplní následující dvě položky.";
		$whole_name->data [ 'heading' ] = "Pomocník";


		$select = $this->addMultiSelect('type', "Typ zařízení", array_keys($this->types))
					  //-> setAttribute ( '' );
					  //-> setPrompt ( 'Vyberte' )
					  ->setRequired();
		$select = $select->getControlPrototype();
		// main.js reads this via $slc.data('types') to auto-detect the
		// establishment type from the whole name - must use the data()
		// method call (not ->data['types']) so Html JSON-encodes it into
		// a real data-types="..." attribute instead of the raw "data" one.
		$select->data('types', $this->types);
		$select->data [ 'content' ] = "Vyberte typ stravovacího zařízení, pokud typ není v nabídce, zvolte jemu nejbližší podobný.";
		$select->data [ 'heading' ] = "Pomocník";

		$select->id = "pubForm-type";

		$name = $this->addText("name", "Zkrácené jméno")
					  ->setAttribute("placeholder", "U Raka")
					  ->setRequired();
		$name = $name->getControlPrototype();
		$name->id = "pubForm-name";
		$name->data [ 'content' ] = "Napište krátké jméno vašeho zařízení, krátké jméno je ta část bez typu. "
									  . "Například z 'Restaurace U Hada' je krátké jméno 'U Hada'. "
									  . "Pokud si nepřejete použít automatické našeptávání (například protože název je složitější"
									  . " a nelze ho s ním správně zadat), vypněteho tlačítkem 'Vypnout našeptávání'.";
		$name->data [ 'heading' ] = "Pomocník";

		$long_name = $this->addTextArea("long_name", "Popisek")
						   ->setAttribute("placeholder", "Delší popisek restauračního zařízení");
		$long_name = $long_name->getControlPrototype();
		$long_name->data [ 'content' ] = "Krátký popisek by měl obsahovat např. umístění zařízení vzhledem okolnímu prostředí, jeho dostupnost a jiné"
										   . " informace, které by mohly ostatní zajímat a nedají se jim jinak sdělit.";
		$long_name->data [ 'heading' ] = "Pomocník";

		$open = $this->addTextArea('opening_hours', "Otevírací hodiny");
		$open = $open->getControlPrototype();
		$open->data [ 'content' ] = "Pokud je to možné, zadejte prosím otevírací dobu, aby to umožnilo ostatním návštěvníkům se přizpůsobit.";
		$open->data [ 'heading' ] = "Pomocník";

		$website = $this->addText('website', "Webová stránka")
						 ->setAttribute('placeholder', "http://webovastrankarestaurace.cz");
		$website = $website->getControlPrototype();
		$website->data [ 'content' ] = "Pokud je to možné, zadejte prosím internetovou adresu, aby se ostatní dozvěděli více o restauraci.";
		$website->data [ 'heading' ] = "Pomocník";



		$this->addGpsPicker('coords', 'Adresa')
			  ->disableSearch()
			  ->setSize("100%", 300);

		$this->addSubmit("submit", "Vložit")
			  ->setAttribute('class', 'form-control');

	}

	public function process($form)
	{
		$values = $form->getValues('array');
		$this->processValues($form, $values);
	}


	public function processValues($form, $values)
	{
		if (!$this->presenter->user->isAllowed('frontend')) {
			$form->presenter->flashMessage("Na vkládání restaurací musíte být přihlášeni.", "warning");
			$this->presenter->redirectLogin();
			return;
		}

		if (!($user = $this->users->item($this->presenter->user->id))) {
			$form->presenter->flashMessage("Neexistující uživatel!", "error");
			$this->presenter->user->logout(true);
			$form->presenter->redirect("Sign:login");
			return;
		}

		/*if ( ! preg_match ( "/" . $values["name"] . "/", $values["whole_name"] ) )
		{
			$this -> presenter -> flashMessage ( "Krátké jméno nemá nic společného se dlouhým jménem! "
												."Předpokládá se, že krátké jméno je obsaženo ve dlouhém jménu.", "error" );
			return;
		}*/

		$date = new Tulinkry\DateTime();
		$date->modify("-1 hour");
		if (count($this->model->time([ "whole_name" => $values ["whole_name" ] ], $date)) &&
			 !$values["agreement"]) {
			$this->presenter->flashMessage("Před chvílí došlo ke vložení restaurace se stejným jménem. "
												 ."Jste si jistí, že se nejedná o překlik? Pokud chcete opravdu vložit tuto restauraci, "
												 ."zaklikněte políčko 'Souhlasím s duplicitním názvem'.", "error");
			$form [ 'agreement' ]->setOption("hidden", false);
			return;
		}

		if ($values["agreement"]) {
			$form["agreement"]->setOption("hidden", false);
		}


		$values [ "user_id" ] = $user->id;
		$values [ "latitude" ] = $values [ "coords" ]->lat;
		$values [ "longitude" ] = $values [ "coords" ]->lng;
		$values [ "address" ] = $values [ "coords" ]->address;
		// GpsPositionPicker (vendor/vojtech-dobes/nette-forms-gpspicker) only
		// exposes lat/lng/address - there's no separate "location" field in
		// the form, so use the same address string here too.
		$values [ "location" ] = $values [ "coords" ]->address;
		$values [ "hidden" ] = true;
		$values [ "updated" ] = new Tulinkry\DateTime();
		$values [ "inserted" ] = $values [ "updated" ];
		// NOT NULL columns with no DB default - MySQL's strict mode (the
		// server's actual default sql_mode) rejects an insert that omits
		// them entirely.
		$values [ "markVoted" ] = 0;
		$values [ "beerMarkVoted" ] = 0;
		$values [ "beerPriceVoted" ] = 0;
		$values [ "wineMarkVoted" ] = 0;
		$values [ "winePriceVoted" ] = 0;
		$values [ "foodMarkVoted" ] = 0;
		$values [ "foodPriceVoted" ] = 0;

		$that = $this;
		$values [ "type" ] = array_map(function ($el) use ($that) {
			return array_keys($that->types) [ $el ];
		}, $values["type"]);

		$values [ "type" ] = implode(", ", $values [ "type" ]);

		$longName = $values [ "long_name" ];

		unset($values [ "coords" ]);
		unset($values [ "agreement" ]);

		try {

			$pub = $this->model->insert($values);

			if ($this->descriptions) {
				$this->descriptions->insert([
					'pub_id' => $pub->id,
					'user_id' => $user->id,
					'version' => 1,
					'text' => $longName,
				]);
			}
		} catch (\Exception $e) {
			$this->presenter->flashMessage("Selhalo přidávání do databáze.", "error");
			return;
		}

		try {
			// Nette\Forms\Container defines its own __call() (for
			// extensionMethod()), which shadows SmartObject's magic
			// event-array-as-method-call convention - invoke explicitly.
			\Nette\Utils\Arrays::invoke($this->onCreate, $pub);

		} catch (\Exception $e) {
			$this->presenter->flashMessage("Selhala notifikace správci.", "error");
			return;
		}

		$this->presenter->flashMessage("Restaurace vložena.", "success");

		$el = Html::el('span', 'Zatím je ve skrytém stavu a čeká na schválení, naleznete jí pod trvalým odkazem');
		$el->addHtml(Html::el('a', " zde")->href($this->presenter->link("Pub:detail", $pub->id)));
		$el->addHtml(Html::el('span', ', kde jí můžete i ohodnotit nebo poslat kamarádům.'));

		$this->presenter->flashMessage($el);
		$this->presenter->redirect("Pub:detail", $pub->id);
	}

}
