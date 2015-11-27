<?php

namespace FrontModule\Forms;


use Tulinkry\Forms;
use Model;
use Nette\Utils\Html;
use Nette\Utils\Neon;
use Entity;
use Tulinkry;

class PubForm extends Forms\Form
{
	protected $model;
	protected $users;
	protected $parametres;

	public $onCreate = array ();

	protected $types = array (
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

	public function __construct ( $model, $users, $parametres )
	{
		parent::__construct ();

		if(! (isset($parametres->params['pub']) && isset($parametres->params['pub']['typeFile']))) {
			throw \Nette\InvalidArgumentException("PubForm: configuration section 'pub' and parameter 'typeFile' are missing" );
		}

		$typeFile = $parametres->params['pub']['typeFile'];
		if(file_exists($typeFile)){
			$res = @file_get_contents($typeFile);
			if($res !== FALSE){
				$string = Neon::decode ( $res );
				if(isset($string['types'])) {
					$this->types = $string['types'];
				}
			}
		}


		$this->getElementPrototype()->class="pubForm";

		$this -> model = $model;
		$this -> users = $users;
		$this -> parametres = $parametres;

		$this -> addCheckbox ( "agreement", "Souhlasím s duplicitním názvem" )
			  -> setAttribute ( "class", "switch" )
			  -> setAttribute ( 'data-on-text', 'Ano' )
			  -> setAttribute ( 'data-off-text', 'Ne' )
			  -> setDefaultValue ( 0 )
			  -> setOption ( "hidden", true );
					 

		$whole_name = $this -> addText ( "whole_name", "Celé jméno" )
			  		  -> setAttribute ( "placeholder", "Hospůdka U Raka" )
					  -> setRequired ()
					  -> setOption ( 'description', "Zadejte celé jméno restaurace, po vyplnění se vám následující dvě políčka vyplní automaticky." );
		$whole_name = $whole_name -> getControlPrototype ();
		$whole_name -> id = "pubForm-whole_name";
		$whole_name -> data [ 'content' ] = "Napište celé jméno restaurace např. 'Restaurace Na Kopečku', v lepším případě se vám automaticky předvyplní následující dvě položky.";
		$whole_name -> data [ 'heading' ] = "Pomocník";


		$select = $this -> addMultiSelect ( 'type', "Typ zařízení", array_keys ( $this -> types ) )
					  //-> setAttribute ( '' );
					  //-> setPrompt ( 'Vyberte' )
					  -> setRequired ();
		$select = $select -> getControlPrototype ();
		$select -> data [ 'types' ] = $this -> types;
		$select -> data [ 'content' ] = "Vyberte typ stravovacího zařízení, pokud typ není v nabídce, zvolte jemu nejbližší podobný.";
		$select -> data [ 'heading' ] = "Pomocník";

		$select -> id = "pubForm-type";

		$name = $this -> addText ( "name", "Zkrácené jméno" )
			  		  -> setAttribute ( "placeholder", "U Raka" )
					  -> setRequired ();
		$name = $name -> getControlPrototype ();
		$name -> id = "pubForm-name";
		$name -> data [ 'content' ] = "Napište krátké jméno vašeho zařízení, krátké jméno je ta část bez typu. "
									  . "Například z 'Restaurace U Hada' je krátké jméno 'U Hada'. "
									  . "Pokud si nepřejete použít automatické našeptávání (například protože název je složitější"
									  . " a nelze ho s ním správně zadat), vypněteho tlačítkem 'Vypnout našeptávání'.";
		$name -> data [ 'heading' ] = "Pomocník";

		$long_name = $this -> addTextArea ( "long_name", "Popisek" )
						   -> setAttribute ( "placeholder", "Delší popisek restauračního zařízení" );
		$long_name = $long_name -> getControlPrototype ();
		$long_name -> data [ 'content' ] = "Krátký popisek by měl obsahovat např. umístění zařízení vzhledem okolnímu prostředí, jeho dostupnost a jiné"
										   . " informace, které by mohly ostatní zajímat a nedají se jim jinak sdělit.";
		$long_name -> data [ 'heading' ] = "Pomocník";

		$open = $this -> addTextArea ( 'opening_hours', "Otevírací hodiny" );
		$open = $open -> getControlPrototype ();
		$open -> data [ 'content' ] = "Pokud je to možné, zadejte prosím otevírací dobu, aby to umožnilo ostatním návštěvníkům se přizpůsobit.";
		$open -> data [ 'heading' ] = "Pomocník";

		$website = $this -> addText ( 'website', "Webová stránka" )
						 -> setAttribute ( 'placeholder', "http://webovastrankarestaurace.cz" );
		$website = $website -> getControlPrototype ();
		$website -> data [ 'content' ] = "Pokud je to možné, zadejte prosím internetovou adresu, aby se ostatní dozvěděli více o restauraci.";
		$website -> data [ 'heading' ] = "Pomocník";



		$this -> addGpsPicker( 'coords', 'Adresa' )
			  -> disableSearch ()
			  -> setSize ( "100%", 300 );

		$this -> addSubmit ( "submit", "Vložit" )
			  -> setAttribute ( 'class', 'form-control' );

	}

	public function process ( $form )
	{
		$values = $form -> values;
		$this -> processValues ( $form, $values );
	}


	public function processValues ( $form, $values )
	{
		if ( ! $this -> presenter -> user -> isAllowed ( 'frontend' ) )
		{
			$form -> presenter -> flashMessage ( "Na vkládání restaurací musíte být přihlášeni.", "warning" );
			$this -> presenter -> redirectLogin ();
			return;
		}

		if ( ! ( $user = $this -> users -> item ( $this -> presenter -> user -> id ) ) )
		{
			$form -> presenter -> flashMessage ( "Neexistující uživatel!", "error" );
			$this -> presenter -> user -> logout ( true );
			$form -> presenter -> redirect ( "Sign:login" );
			return;
		}

		/*if ( ! preg_match ( "/" . $values["name"] . "/", $values["whole_name"] ) ) 
		{
			$this -> presenter -> flashMessage ( "Krátké jméno nemá nic společného se dlouhým jménem! "
												."Předpokládá se, že krátké jméno je obsaženo ve dlouhém jménu.", "error" );
			return;
		}*/

		$date = new Tulinkry\DateTime;
		$date -> modify ( "-1 hour" );
		if ( count ( $this -> model -> time ( [ "whole_name" => $values ["whole_name" ] ], $date ) ) &&
			 ! $values["agreement"] )
		{
			$this -> presenter -> flashMessage ( "Před chvílí došlo ke vložení restaurace se stejným jménem. "
												 ."Jste si jistí, že se nejedná o překlik? Pokud chcete opravdu vložit tuto restauraci, "
												 ."zaklikněte políčko 'Souhlasím s duplicitním názvem'.", "error" );
			$form [ 'agreement' ] -> setOption ( "hidden", false );
			return;
		}

		if ($values["agreement"]) {
			$form["agreement"]->setOption("hidden", false);
		}


		$values [ "user" ] = $user;
		$values [ "latitude" ] = $values [ "coords" ] -> lat;
		$values [ "longitude" ] = $values [ "coords" ] -> lng;
		$values [ "address" ] = $values [ "coords" ] -> address;
		$values [ "location" ] = $values [ "coords" ] -> location;
		$values [ "hidden" ] = true;
		$values [ "updated" ] = new Tulinkry\DateTime;
		$values [ "inserted" ] = $values [ "updated" ];

		$that = $this;
		$values [ "type" ] = array_map ( function ($el) use ($that) {
			return array_keys ( $that -> types ) [ $el ];
		}, $values["type"]);

		$values [ "type" ] = implode ( ", ", $values [ "type" ] );

		$desc = new Entity\Description;
		$desc -> version = 1;
		$desc -> user = $user;
		$desc -> text = $values [ "long_name" ];


		try {

			$entity = $this -> model -> create ( $values );
			$entity -> addDescription($desc);

			$this -> model -> insert ( $entity );
		} catch ( \Exception $e )
		{
			$this -> presenter -> flashMessage ( "Selhalo přidávání do databáze.", "error" );
			return;
		}

		try {
			$this -> onCreate ( $entity );

		} catch ( \Exception $e )
		{
			$this -> presenter -> flashMessage ( "Selhala notifikace správci.", "error" );
			return;
		}

		$this -> presenter -> flashMessage ( "Restaurace vložena.", "success" );
		
		$el = Html::el('span', 'Zatím je ve skrytém stavu a čeká na schválení, naleznete jí pod trvalým odkazem' );
		$el->add( Html::el('a', " zde" )->href($this->presenter->link("Pub:detail", $entity->id) ) );
		$el->add( Html::el('span', ', kde jí můžete i ohodnotit nebo poslat kamarádům.') );

		$this -> presenter -> flashMessage ( $el );
		$this -> presenter -> redirect ( "Pub:detail", $entity -> id );		
	}

}
