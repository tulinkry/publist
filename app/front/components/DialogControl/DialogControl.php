<?php

namespace FrontModule\Controls;

use Tulinkry\Application\UI\Control;
use Tulinkry;
use Forms;
use Model;
use Nette;


class Dialog extends Control
{

	protected $form;

	public function __construct ()
	{
		parent::__construct ();


		//$form -> getElementPrototype () -> addClass ( "ajax" );
	}

	public function errorHandler ( $form )
	{
		print_r ( "AAAAAAAAAAAAAAAAAAAAAAA" );
		if ( 1 ) {
			$this -> show ( $form );
			$this -> invalidateControl ();
		}
	}
	

	public function show ( $form )
	{
		$this -> template -> _form = $this -> form = $form;

		//$form -> action = "?action=dialog&do=showDialog";
		//$form->setAction(new Nette\Application\UI\Link($this, 'this', array( "do" => "showDialog" ) ) );
		//$form->removeComponent($form['do']);
		$form -> onSuccess [] = function ( $form ) {
			echo "ahooooj";
			file_put_contents( __DIR__ . "/out.txt", "ahooooj" );
		};

		//echo \Tracy\Dumper::dump ( $form );
        $this->invalidateControl();
        return $this;
	}

	public function title ( $title )
	{
		$this -> template -> title = $title;
		return $this;
	}


	public function close ()
	{
		$this -> template -> close = true;
		$this -> invalidateControl ();
	}



	public function render ()
	{	
		$this -> template -> setFile ( __DIR__  . "/dialogControl.latte" );
		$this -> template -> _form = $this -> form;
		$this -> template -> render ();
	}

}