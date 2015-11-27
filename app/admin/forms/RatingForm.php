<?php

namespace AdminModule\Forms;


use FrontModule\Forms;
use Tulinkry;

class RatingForm extends Forms\RatingForm
{

	public function __construct ( $pubs, $ratings, $beers, $id )
	{
		$rating = $ratings -> item ( $id );

		parent::__construct ( $pubs, $ratings, $beers, null, $rating, null );
		
		$entity =  $rating -> toArray ();

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

		$this -> removeComponent ( $this [ "submit" ] );
		$this -> addSubmit ( "submit", "Uložit hodnocení" )
			-> onClick [] = array ( $this, "ratingFormUpdated" );

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

	public function ratingFormUpdated ( $button )
	{
		$values = $button -> form -> getValues(TRUE);
		$form = $button -> form;

		//\Tracy\Dumper::dump($values);

		//return;
		if ( ! $this -> presenter -> user -> isAllowed ( 'backend' ) )
		{
            $this->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
            $this->redirect(":Front:Pub:default");
		}

		$entity = $this -> ratings -> item ( $values [ "id" ] );

		if ( ! $entity )
		{
			$form -> presenter -> flashMessage ( "Neexistující hodnocení!", "error" );
			return;
		}

		foreach ( $values [ "mandatory" ]  as $key => $value )
		{
			$values [ $key ] = $value;
		}

		foreach ( [ "wine",
					"food" ]  as $key => $value )
		{
			if ( $values [ "optional" ] [ $value . "Criteria" ] == 0 )
				// no rating
				$values [ $value . "Criteria" ] = NULL;
			else
				$values [ $value . "Criteria" ] = $values [ "optional" ] [ $value . "Criteria" ];
		}

		foreach ( [ "winePrice" ] as $key => $value )
		{
			$values [ $value ] = $values [ "optional" ] [ $value ];
		}

		if (isset($values['optional']['beers'])){
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

			$contains = function ($id) use ($entity) {
				foreach ($entity->beers as $beerRating) {
					if ($beerRating->beer->id === $id)
						return true;
				}
				return false;
			};

			foreach ( $values [ 'optional' ] [ 'beers' ] as $key => $beer ) {
				if ( ! ( $b = $this -> beers -> item ( $beer [ 'brand' ] ) ) ) {
					$form -> presenter -> flashMessage ( "Jedno z uvedených piv neexistuje!", "error" );
					return;
				}

				if($contains($b->id)){
					$form -> presenter -> flashMessage ( "Pokoušíte se zadat vícekrát to stejné pivo.", "error" );
					return;
				}

				$beerRating = new \Entity\BeerRating;
				$beerRating -> beerPrice = $beer [ 'price' ];
				$beerRating -> beerCriteria = $beer [ 'beerCriteria' ] == 0 ? NULL : $beer [ 'beerCriteria' ];
				$beerRating -> beer = $b;
				$beerRating -> rating = $entity;
				$entity->addBeer($beerRating);
			}
		}

		//$this -> users -> refresh ();

		$this -> ratings -> update_array ( $entity, $values );

		$entity -> pub -> recompute ();
		$entity -> pub -> updated = new Tulinkry\DateTime;
		$this -> pubs -> update ( $entity -> pub );

		$this -> presenter -> flashMessage ( "Hodnocení bylo uloženo", "success" );

		$this -> presenter -> redirect ( "Pub:detail", [ "id" => $entity -> pub -> id,
														 "paginator-page" => $this -> presenter -> paginator -> page,
														 "paginator2-page" => $this -> presenter["paginator2"] -> page ] );
	

	}

}


