<?php

namespace FrontModule\Forms;


use Tulinkry\Forms;
use Model;
use Nette\Utils\Html;
use Tulinkry;

class AlternatePubForm extends PubForm
{
	public function __construct ( $model, $users, $parametres )
	{
		parent::__construct ( $model, $users, $parametres );

		$lat = $this -> addText ( "lat", "Latitude" )
			  -> addRule ( self::RANGE, "Latitude musí být ve správném rozsahu od %d do %d.", array ( -90, 90 ) )
			  -> setRequired ()
			  //-> setType ( "number" )
			  -> setAttribute ( "placeholder", "50.083" );

		$lat = $lat -> getControlPrototype ();
		$lat -> data [ 'content' ] = "Zadejte latitude v číselném rozsahu od -90 do 90. Desetinná čárka se zadává jako tečka.";
		$lat -> data [ 'heading' ] = "Pomocník";
		$lat -> id = "alternatePubForm-lat";

		$lng = $this -> addText ( "lng", "Longitude" )
			  -> addRule ( self::RANGE, "Latitude musí být ve správném rozsahu od %d do %d.", array ( -180, 180 ) )
			  -> setRequired ()
			  //-> setType ( "number" )
			  -> setAttribute ( "placeholder", "14.423" );

		$lng = $lng -> getControlPrototype ();
		$lng -> data [ 'content' ] = "Zadejte longitude v rozsahu od -180 do 180. Desetinná čárka se zadává jako tečka.";
		$lng -> data [ 'heading' ] = "Pomocník";
		$lng -> id = "alternatePubForm-lng";

		$this -> removeComponent ( $this [ "coords" ] );

	}


	public function process ( $form )
	{
		$values = $form -> values;

		if ( ! $this -> presenter -> user -> isAllowed ( 'frontend' ) )
		{
			$form -> presenter -> flashMessage ( "Na vkládání restaurací musíte být přihlášeni.", "warning" );
			$this -> presenter -> redirectLogin ();
			return;
		}


		$lat = $values [ "lat" ];
		$lng = $values [ "lng" ];

		/* geocode */
		$url = "https://maps.googleapis.com/maps/api/geocode/json";

		$query = array ( 
						 'key' => 'AIzaSyCBNGn9ADxqF-jlhzkzq1c0P3cBu_XdM0s',
						 'latlng' => $lat . "," . $lng,
						 'language' => 'cs',
						  );

		$url = $url . "?" . http_build_query ( $query );

		if ( ( $res = @file_get_contents ( $url ) ) === FALSE )
		{
			$this -> presenter -> flashMessage ( "Nebylo možné zjistit lokalitu oblasti, zkuste zadat jiné souřadnice nebo to zkuste později vyhledat v mapě na lepším připojení. (selhalo čtení vzdáleného serveru)", 'danger');
			return;
		}

		if ( ( $res = json_decode ( $res ) ) === NULL )
		{
			$this -> presenter -> flashMessage ( "Nebylo možné zjistit lokalitu oblasti, zkuste zadat jiné souřadnice nebo to zkuste později vyhledat v mapě na lepším připojení. (selhalo dekódování zprávy od vzdáleného serveru)", 'danger');
			return;
		}

		if ( $res -> status !== "OK" )
		{
			$this -> presenter -> flashMessage ( "Nebylo možné zjistit lokalitu oblasti, zkuste zadat jiné souřadnice nebo to zkuste později vyhledat v mapě na lepším připojení. (požadavek selhal)", 'danger');
			return;
		}

		$results = $res -> results;

		if ( count ( $results ) > 0 ) {
			$address = $results [ 0 ] -> formatted_address;
			$location = $this -> parseAddressComponents ( $results );
			$location = count($location) ? implode ( ", ", $location ) : $address;
			$values [ "address" ] = $address;
			$values [ "location" ] = $location;

			$values [ "coords" ] = new \StdClass;
			$values [ "coords" ] -> lat = $values [ "lat" ];
			$values [ "coords" ] -> lng = $values [ "lng" ];
			$values [ "coords" ] -> address = $values [ "address" ];
			$values [ "coords" ] -> location = $values [ "location" ];
		}
		else {
			$form -> addError ( "Nepodařilo se zjistit adresu restaurace, jste si jistí souřadnicemi?" );
			return;
		}

		/* geocode end */

		parent::processValues ( $form, $values );
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

}
