<?php

namespace FrontModule\Presenters;

use Nette,
	Model,
	FrontModule\Controls,
	FrontModule\Forms,
	Tulinkry;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Tulinkry\Application\UI\Presenter
{
	/** @inject @var Model\UserModel */
	public $users;
	/** @inject @var Model\PubModel */
	public $pubs;

	/* @var Entity\User */
	protected $userClass;

	public function startup ()
	{
		parent::startup ();
		
		if ( $this -> getUser () -> isLoggedIn () )
		{
			if ( ( $this -> userClass = $this -> users -> item ( $this -> user -> id ) ) === NULL )
			{
				$this -> userClass = NULL;
				$this -> user -> logout ( true );
			}
		}

		$this -> template -> last = $this -> pubs -> fetchLimit ( 10, 0, [ "hidden" => false ], [ "inserted" => "DESC" ] );
		$this -> template -> rated = $this -> pubs -> lastRated ( 10, 0, [ "hidden" => false ] );
		$this -> template -> lastOffset = $this -> template -> ratedOffset = 10;
		$this -> template -> debugMode = isset($this -> parameters -> params['debugMode']) ? $this -> parameters -> params['debugMode'] : true;

	}

	public function startu ()
	{
		parent::startup ();
		/**
		 * Check if the user is logged in and set the member variable
		 * Otherwise set the variable to NULL
		 */
		if ( $this -> getUser () -> isLoggedIn () && 
			( $data =  $this->getUser()->getIdentity()->getData() ) &&
			isset ( $data ["userClass"] ) )
		{
			try
			{
				$this -> userClass = $this -> users -> merge ( $this->getUser()->getIdentity()->getData()["userClass"] );
				$this -> users -> refresh ( $this -> userClass );
				$this -> userClass -> setKlik ( new DateTime () );
				$this -> users -> update ( $this -> userClass );


				/*if ( ! preg_match ( "/".User::USERNAME_PATTERN."/", $this -> userClass -> getUsername () ) &&
					 ! ( $this -> name == "Front:Sign" && $this -> action == "change" ) )
				{
					$this -> flashMessage ( "Ve vašem uživatelském jméně se nacházejí zakázané znaky. Změnte si uživatelské jméno. 
								Pokud do 7 dní od zobrazení této zprávy nedojde ke změně uživatelského jména, váš účet bude smazán.");
					$this -> redirect ( ":Front:Sign:change" );
				}*/

				if ( $this -> userClass -> needLogout () )
				{
					$this -> userClass -> unsetNeedLogout ();
					$this -> users -> update ( $this -> userClass );
					$this -> userClass = NULL;
					$this -> getUser()->logout( true );
					$this -> flashMessage ( "Vaše data se změnila. Přihlašte se znovu." );
					$this -> redirect ( ":Front:Sign:login" );
				}

			} catch ( \Doctrine\ORM\EntityNotFoundException $e )
			{
				$this -> userClass = NULL;
				$this->getUser()->logout( true );
			}
		}
		else
		{
			$this->getUser()->logout( true );
			$this -> userClass = NULL;
		}		
	}



	public function beforeRender ()
	{
		/*$pole = [ "username" => "Kryštof",
				  "password" => \Authenticator\Authenticator::calculateHash ( "armagedon", "Kryštof" ),
				  "email" => "k.tulinger@seznam.cz" ];
		$u = $this -> context -> getService ( "users" ) -> create ( $pole );
		$this -> context -> getService ( "users" ) -> insert ( $u );*/

		parent::beforeRender ();

		$this -> template -> sliderPictures = $this -> getSlideShow ();

		$this -> template -> userClass = $this -> userClass;


	}

	public function handleNextLast ( $offset )
	{
		$limit = 10;
		$this -> template -> last = $this -> pubs -> limit ( $limit, $offset, [ "hidden" => false ], [ "inserted" => "DESC" ] );
		$this -> template -> lastOffset = $offset + $limit;
		$this -> invalidateControl ( 'last' );
	}

	public function handleNextRated ( $offset )
	{
		$limit = 10;
		$this -> template -> rated = $this -> pubs -> lastRated ( $limit, $offset, [ "hidden" => false ] );
		$this -> template -> ratedOffset = $offset + $limit;
		$this -> invalidateControl ( 'rated' );
	}

	
	protected function getSlideShow ( $max = 200 )
	{
		$files = [];
		$sliderDir = $this -> parameters -> params [ "sliderSrc" ];
		$dirname = WWW_DIR . "/" . $sliderDir . "/" . $this -> parameters -> params [ "slider" ] [ "md" ];
		if ( $handle = opendir( $dirname ) ) 
		{
		    while ( false !== ( $entry = readdir($handle) ) ) 
		    {
		        if ($entry != "." && $entry != ".." && ! is_dir ($dirname ."/".$entry) ) 
		        {
		        	$std = new \StdClass;
		        	$std -> sliderDir = $sliderDir;
		        	$std -> path = $entry;
		        	$std -> sliderSettings = $this -> parameters -> params [ "slider" ];
		            $files [] = $std;
		        }
		    }
		    closedir( $handle );
		}
		return $files;
	}

	protected function createComponentSearchForm ( $name )
	{
		$session = $this->session->getSection('search');
		return new Forms\SearchForm($this,$name,$session);
	}

	protected function createComponentRating ()
	{
		return new Controls\RatingControl;
	}	


	public function createComponentDialog($name) 
	{
		return new Controls\Dialog($this, $name);
	}

	public function dialog()
	{
		return $this['dialog'];
	}


}
