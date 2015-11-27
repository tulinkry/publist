<?php

namespace FrontModule\Forms;


use Tulinkry\Forms;
use FrontModule\Controls;
use Nette\Utils\Html;
use Tulinkry\Utils\Strings;
use Tulinkry;
use Model;


class RatingForm extends Forms\Form
{
	protected $pubs;
	protected $ratings;
	protected $beers;
	protected $users;
	protected $pub;
	protected $rating;

	public function __construct ( $pubs, $ratings, $beers, $users, $rating, $pub_id )
	{
		parent::__construct ();

		$this->getElementPrototype()->class="ratingForm";

		$this -> pubs = $pubs;
		$this -> ratings = $ratings;
		$this -> beers = $beers;
		$this -> users = $users;
		$this -> rating = $rating;

		if ($pub_id){
			$this -> pub = $this -> pubs -> item ( $pub_id );
		}

		$this -> addHidden ( "id", $pub_id );

		$this -> addGroup ( "Povinné prvky" );
		$mandatory = $this -> addContainer ( "mandatory" );



		foreach ( [ "interier" => "Interiér",
					"exterier" => "Exteriér",
					"service" => "Personál", 
					"overall" => "Celkový dojem",
					"foodPrice" => "Celková spokojenost s cenou" ] as $key => $criteria )
		{
			$min = constant ( "Model\PubModel::" . strtoupper ( Strings::decamelize ( $key ) ) . "_MIN" );
			$max = constant ( "Model\PubModel::" . strtoupper ( Strings::decamelize ( $key ) ) . "_MAX" );


			for ( $i = $min; $i <= $max; $i ++ )
				$stars [ $i ] = "$i/$max";

			$mandatory -> addRadioList ( $key . "Criteria", $criteria, $stars )
				-> setAttribute( 'class', 'range mandatory' )
				-> getSeparatorPrototype() 
					-> setName(NULL);

			$mandatory [ $key . "Criteria" ]
				-> setDefaultValue ( 1 );

			/*$star = Controls\StarRatingHelper::IMAGE_PATH;*/

		}


		$this -> addGroup ( "Nepovinné prvky" );
		$optional = $this -> addContainer ( "optional" );

		$beerModel = $this -> beers;
		$replicator = $optional -> addDynamic ( 'beers', function ( $beer ) use ( $beerModel ) {
			//$beer->currentGroup = $beer->form->addGroup('Pivo', FALSE);
			$min = constant ( "Model\PubModel::BEER_MIN" );
			$max = constant ( "Model\PubModel::BEER_MAX" );

			$stars = array (
				0 => "Nehodnotit"
			);

			for ( $i = $min; $i <= $max; $i ++ )
				$stars [ $i ] = "$i/$max";

			$brands = $beerModel -> by ( [], [ "name" => "ASC", "degree" => "ASC" ]);


			$beer -> addSelect ( 'brand', "Značka", $brands )
				  -> setRequired ()
				  -> setOption ( "description", Html::el ( "span" ) -> setText ( "" ) )
				  -> setPrompt ( "Vyberte značku piva" )
				  -> addRule ( Forms\Form::FILLED, "Značka piva musí být vyplněna." );

			$beer -> addRadioList ( 'beerCriteria', "Pivo", $stars )
				  -> setAttribute( 'class', 'range optional' )
				  -> setDefaultValue ( 0 )
				  -> setValue ( 0 )
				 // -> setRequired ()
				  -> getSeparatorPrototype() 
				  	-> setName(NULL);


			$beer -> addSelect ( 'price', "Cena 0.5l", Tulinkry\Utils\Arrays::createSequence ( 10, 100 ) )
				  -> setOption ( "description", Html::el ( "span" ) -> setText ( "Kč" ) )
				  -> setPrompt ( "Nehodnotit" );
				  //-> getControlPrototype() -> insert(0, Html::el( "div class=input-group-addon" ) -> setText( "$" ) );

			$beer -> addSubmit ( 'deleteBeer', 'Smazat pivo' )
				  -> setValidationScope ( FALSE )
				  -> setAttribute ( "class", "ajax deleteBeer btn btn-danger" )
				  -> setAttribute ( "data-confirm", "Opravdu?" )				  
				  -> onClick [] = function ( $button ) {
					  $beers = $button -> parent -> parent;
					  $beers -> form -> presenter -> invalidateControl ( 'ratingForm' );
					  $beers -> remove( $button -> parent, TRUE);
				  };
		}, 0 );

		$replicator -> addSubmit ( 'addBeer', 'Přidat pivo' )
					-> setValidationScope ( FALSE )
					-> setAttribute ( "class", "ajax addBeer btn btn-success" )
					-> onClick [] = function ( $button ) {
					    $beers = $button->parent;
					    if ($beers->isAllFilled() && $beers->isValid()) {
					    	$button->parent->createOne();
					    } else {
					    	$button -> form -> presenter -> flashMessage ( "Nejdříve vyplňte předchozí piva.", "error" );
					    }

					    $button -> form -> presenter -> invalidateControl ( 'ratingForm' );
					};


		foreach ( [ "wine" => "Víno",
					"toalets" => "Toalety",  
					"food" => "Jídlo" ] as $key => $criteria )
		{
			$min = constant ( "Model\PubModel::" . strtoupper ( Strings::decamelize ( $key ) ) . "_MIN" );
			$max = constant ( "Model\PubModel::" . strtoupper ( Strings::decamelize ( $key ) ) . "_MAX" );

			$stars = array (
				0 => "Nehodnotit"
			);

			for ( $i = $min; $i <= $max; $i ++ )
				$stars [ $i ] = "$i/$max";

			$optional -> addRadioList ( $key . "Criteria", $criteria, $stars )
					  -> setDefaultValue ( 0 )
					  -> setAttribute( 'class', 'range optional' )
					  -> getSeparatorPrototype() 
						-> setName(NULL);

			//$star = Controls\StarRatingHelper::IMAGE_PATH;

		}


		foreach ( [ "winePrice" => "Cena vína 0.2l" ] as $key => $criteria )
		{
			$min = constant ( "Model\PubModel::" . strtoupper ( Strings::decamelize ( $key ) ) . "_MIN" );
			$max = constant ( "Model\PubModel::" . strtoupper ( Strings::decamelize ( $key ) ) . "_MAX" );
			$seq = Tulinkry\Utils\Arrays::createSequence ( $min, $max );
			$optional -> addSelect ( $key, $criteria, $seq )
				  -> setPrompt ( "Nehodnotit" )
				  -> setOption ( "description", Html::el ( "span" ) -> setText ( "Kč" ) )
				  -> addCondition ( ~Forms\Form::EQUAL, $optional [ $key ], "" )
					  -> addRule ( Forms\Form::RANGE, "Hodnota musí být mezi %d a %d.", array ( $min, $max ) );
		}

		$optional [ "winePrice" ] -> setDefaultValue ( NULL );


		$optional -> addCheckbox ( 'garden', "Zahrádka" )
				  -> setAttribute ( 'class', 'switch' )
				  -> setAttribute ( 'data-on-text', 'Ano' )
				  -> setAttribute ( 'data-off-text', 'Ne' )
				  -> setDefaultValue ( 0 );

		/*$this -> addGroup ( "Osobní informace" );

		$this -> addText ( "name", "Jméno" )
			  -> setAttribute ( "placeholder", "Petr Novák" )
			  -> addRule ( Forms\Form::FILLED, "Zadejte vaše jméno." );

		$this -> addText ( "email", "Email" )
			  -> setAttribute ( "placeholder", "vas@email.cz" )
			  -> addRule ( Forms\Form::EMAIL, "Zadejte validní formát emailové adresy." );*/

		$this -> addGroup ();


		$this -> addSubmit ( "submit", "Odeslat hodnocení" )
			  -> setAttribute ( "class", "btn-primary" )
			  -> onClick [] = array ( $this, "ratingFormSubmitted" );

		if($rating){
			// fill entity
			$entity = $rating -> toArray ();
			$this [ "mandatory" ] -> setDefaults ( $entity );
			$this [ "optional" ] -> setDefaults ( $entity );


			$this [ "optional" ] [ "beers" ] -> createDefault = 0;
			foreach ( $rating -> getBeers() as $p )
			{
				$one = $this [ "optional" ] [ "beers" ] [ $p -> beer -> id ];
				$one [ "brand" ] -> setDefaultValue( $p -> beer -> id );
				$one [ "price" ] -> setDefaultValue ( $p -> beerPrice );
				$one [ "beerCriteria" ] -> setDefaultValue ( $p -> beerCriteria );
			}

		
			$this -> setDefaults ( $entity );

			$form = $this;
			foreach ($this['optional']['beers']->containers as $cnt){
				$cnt['deleteBeer']->onClick[] = function ( $button ) use ( $rating, $ratings, $form ) {
					foreach ($rating->beers as $beerRating) {
						if ( $beerRating->beer->id == $button->parent->name ) {
							try {
								$rating -> removeBeer($beerRating);
								$ratings -> remove($beerRating);
							} catch ( \Exception $e ) {
								// probably is not attached now but will be hopefully
								$form->presenter->flashMessage ('Nastala chyba při mazání piva, operaci se nepodařilo dokončit.', 'danger');
								$form->presenter->redirect('this');
							}
							return;
						}
					}
				};
				
			}
			
		}



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


	public function ratingFormSubmitted ( $button )
	{
		$form = $button -> form;
		$values = $form -> getValues(TRUE);

		//\Tracy\Dumper::dump ( $values );

		//return;

		if ( ! $this -> presenter -> user -> isAllowed ( 'frontend' ) )
		{
			$form -> presenter -> flashMessage ( "Na hodnocení musíte být přihlášeni.", "warning" );
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

		if ( ! $this -> pub )
		{
			$form -> presenter -> flashMessage ( "Restaurace neexistuje!", "error" );
			$form -> presenter -> redirect ( "Pub:default" );
			return;
		}

		$existing = $this -> pub -> ratings -> filter ( function ( $obj ) use ( $user ) { 
			return $obj -> user === $user && 
				   $obj -> date -> getTimestamp() > (time () - Model\RatingModel::RATING_INTERVAL) &&
				   $obj -> date -> getTimestamp() < (time () - Model\RatingModel::RATING_CLOSURE); 
		});
		if ( count ( $existing ) )
		{
			$n = [ "year" => "letech", "month" => "měsících", "day" => "dnech", "hour" => "hodinách", "minute" => "minutách", "second" => "sekundách" ];
			$str = sprintf ("Už jste hlasoval v posledních %s", $this->convertTimeNumber(Model\RatingModel::RATING_INTERVAL, $n) );
			$this -> presenter -> flashMessage ( $str, "error" );
			return;
		}

		if (isset($values['optional']['beers'])){
			$values['beers'] = [];
			if($this->rating){

				$entity = $this->rating;
				foreach ( $entity->beers as $beerRating ) {
					foreach ( $values [ 'optional' ] [ 'beers' ] as $key => $beer ) {
						if ( $beerRating->beer->id === $key ) {
							$beerRating->beerPrice = $beer['price'];
							$beerRating->beerCriteria = $beer [ 'beerCriteria' ] == 0 ? NULL : $beer [ 'beerCriteria' ];
							unset($values['optional']['beers'][$key]);
						}
					}
					if ( ! in_array ( $beerRating->beer->id, $values [ 'optional' ] [ 'beers' ]) ) {
						// deleted beer, done as a callback to deleteBeer button
					}
				}
			}

			$contains = function ($beers, $id) {
				foreach ($beers as $beerRating) {
					if ($beerRating->beer->id === $id)
						return true;
				}
				return false;
			};

			foreach ( $values [ 'optional' ] [ 'beers' ] as $key => $beer ) {
				if ( ! ( $b = $this -> beers -> item ( $beer [ 'brand' ] ) ) ) {
					$this -> presenter -> flashMessage ( "Jedno z uvedených piv neexistuje!", "error" );
					return;
				}

				if($this->rating) {
					if($contains($entity->beers, $b->id)){
						$this -> presenter -> flashMessage ( "Pokoušíte se zadat vícekrát to stejné pivo.", "error" );
						return;
					}
				} else {
					if($contains($values['beers'], $b->id)){
						$this -> presenter -> flashMessage ( "Pokoušíte se zadat vícekrát to stejné pivo.", "error" );
						return;
					}
				}

				$beerRating = new \Entity\BeerRating;
				$beerRating -> beerPrice = $beer [ 'price' ];
				$beerRating -> beerCriteria = $beer [ 'beerCriteria' ] == 0 ? NULL : $beer [ 'beerCriteria' ];
				$beerRating -> beer = $b;
				//$beerRating -> rating = $entity;
				if($this->rating){
					$entity->addBeer($beerRating);
				}
				$values['beers'][] = $beerRating;
			}
		}

		//$this -> users -> refresh ();

		unset ( $values [ "id" ] );
		$values['calculated'] = false;
		$values['date'] = new Tulinkry\DateTime;


		foreach ( $values [ "mandatory" ]  as $key => $value )
		{
			$values [ $key ] = $value;
		}

		foreach ( [ "wine",
					"toalets",
					"food" ]  as $key => $value )
		{
			if ( $values [ "optional" ] [ $value . "Criteria" ] == 0 )
				// no rating
				$values [ $value . "Criteria" ] = NULL;
			else
				$values [ $value . "Criteria" ] = $values [ "optional" ] [ $value . "Criteria" ];
		}

		foreach ( [ "winePrice",
					"garden" ] as $key => $value )
		{
			$values [ $value ] = $values [ "optional" ] [ $value ];
		}


		if ($this->rating) {
			unset($values['beers']);
			$this -> ratings -> update_array ( $this->rating, $values );
			$rating = $this->rating;
			foreach ( $rating -> beers as $beer )
				$beer -> rating = $rating;
		} else {
			$rating = $this -> ratings -> create ( $values );
			$user -> addRating ( $rating );
			$this -> pub -> addRating ( $rating );
			foreach ( $rating -> beers as $beer )
				$beer -> rating = $rating;
		}

		//\Tracy\Dumper::dump($rating);	

		//return;


		try {
			if ($this->rating) {
				$this -> ratings -> update ( $rating );
				$this -> ratings -> flush (); // flush beer_rating price
			} else {
				$this -> ratings -> insert ( $rating );
				$this -> ratings -> flush (); // flush beer_rating price
			}
			$this -> users -> update ( $user );
		} catch ( \Exception $e )
		{
			$this -> presenter -> flashMessage ( "Hodnocení se nepodařilo uložit do databáze.", "error" );
			return;
			//$this -> presenter -> redirect ( "this" );
		}
	
		if ($this->rating) {
			$this -> presenter -> flashMessage ( "Hodnocení bylo úspěšně uloženo." );
		} else {
			$this -> presenter -> flashMessage ( "Hodnocení bylo úspěšně vloženo." );

			$n = [ "year" => "roky", "month" => "měsíce", "day" => "dny", "hour" => "hodin", "minute" => "minut", "second" => "sekund" ];
			$this -> presenter -> flashMessage ( sprintf ("Nyní zůstane otevřené pro volné úpravy po %s, a poté se uzavře a systém ho započítá do hodnocení celé restaurace.", $this->convertTimeNumber( Model\RatingModel::RATING_CLOSURE, $n) ) );
			$n = [ "year" => "roky", "month" => "měsíce", "day" => "dny", "hour" => "hodiny", "minute" => "minuty", "second" => "sekundy" ];
			$this -> presenter -> flashMessage ( sprintf ("Toto restaurační zařízení budete moci ohodnotit opět až za %s.", $this->convertTimeNumber(Model\RatingModel::RATING_INTERVAL, $n ) ) );
		}
		$this -> presenter -> redirect ( "Pub:detail", [ "id" => $this -> pub -> id ] );
	}

}


