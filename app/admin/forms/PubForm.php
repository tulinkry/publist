<?php

namespace AdminModule\Forms;


use FrontModule\Forms;
use Model;
use Nette\Utils\Html;
use Entity;
use Tulinkry;

class PubForm extends Forms\PubForm
{
	protected $id;

	public function __construct ( $model, $users, $parameters, $id )
	{
		parent::__construct ( $model, $users, $parameters );
		$this -> id = $id;

		$this -> addHidden ( "id" );

		$this -> getElementPrototype() -> data ['type'] = "adminForm";
		
		$entity = $model -> item ( $id );
		if ( ! $entity )
			return;

		$entity_array = $entity -> toArray ();
		$entity_array [ "coords" ] = new \StdClass;
		$entity_array [ "coords" ] -> lat = $entity_array [ "latitude" ];
		$entity_array [ "coords" ] -> lng = $entity_array [ "longitude" ];
		$entity_array [ "coords" ] -> address = $entity_array [ "address" ];
		$entity_array [ "coords" ] -> location = $entity_array [ "location" ];

		$types = [];
		foreach ( explode(", ", $entity_array["type"]) as $type ) {
			if (!trim($type))
				continue;
			$types[] = array_flip ( array_keys ( $this -> types ) )[$type];
		}

		$entity_array [ "type" ] = $types;

		$this -> setDefaults ( $entity_array );

		$this -> removeComponent ( $this [ "submit" ] );
		$this -> removeComponent ( $this [ "agreement" ] );
		$this -> addSubmit ( "submit", "Uložit" );
	}

	public function process ( $form )
	{
		$values = $form -> values;

		if ( ! $this -> presenter -> user -> isAllowed ( 'backend' ) )
		{
            $this->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
            $this->redirect(":Front:Pub:default");
		}

		if ( ! ( $user = $this -> users -> item ( $this -> presenter -> user -> id ) ) )
		{
			$form -> presenter -> flashMessage ( "Neexistující uživatel!", "error" );
			$this -> presenter -> user -> logout ( true );
			$form -> presenter -> redirect ( "Sign:login" );
			return;
		}


		$that = $this;
		$values [ "type" ] = array_map ( function ($el) use ($that) {
			return array_keys ( $that -> types ) [ $el ];
		}, $values["type"]);
		
		$values [ "type" ] = implode ( ", ", $values [ "type" ] );
		$values [ "latitude" ] = $values [ "coords" ] -> lat;
		$values [ "longitude" ] = $values [ "coords" ] -> lng;
		$values [ "address" ] = $values [ "coords" ] -> address;
		$values [ "location" ] = $values [ "coords" ] -> location;
		$values [ "updated" ] = new Tulinkry\DateTime;

		$entity = $this -> model -> item ( $values [ "id" ] );


		if($entity->lastDescription->text !== $values['long_name']){
			$desc = new Entity\Description;
			$desc -> version = $entity->lastDescription->version + 1;
			$desc -> user = $user;
			$desc -> text = $values [ "long_name" ];
			$entity -> addDescription($desc);			
		}

		$this -> model -> update_array ( $entity, $values );

		$this -> presenter -> flashMessage ( "Nastavení bylo uloženo", "success" );

		$this -> presenter -> redirect ( "Pub:default", [ "paginator-page" => $this -> presenter -> paginator -> page ] );
	}

}
