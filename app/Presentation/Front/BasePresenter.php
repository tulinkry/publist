<?php

namespace App\Presentation\Front;

use Nette;
use App\Presentation\Accessory;
use App\Forms\Front;
use Tulinkry;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Tulinkry\Application\UI\Presenter
{
	/** @inject @var \App\Model\UserModel */
	public $users;
	/** @inject @var \App\Model\PubModel */
	public $pubs;

	/** @var \Nette\Database\Table\ActiveRow|null */
	protected $userClass;

	public function startup()
	{
		parent::startup();

		if ($this->getUser()->isLoggedIn()) {
			if (($this->userClass = $this->users->item($this->user->id)) === null) {
				$this->userClass = null;
				$this->user->logout(true);
			}
		}

		$this->template->last = $this->pubs->limit(10, 0, [ "hidden" => false ], [ "inserted" => "DESC" ]);
		$this->template->rated = $this->pubs->lastRated(10, 0, [ "hidden" => false ]);
		$this->template->lastOffset = $this->template->ratedOffset = 10;
		$this->template->debugMode = isset($this->parameters->params['debugMode']) ? $this->parameters->params['debugMode'] : true;
		$this->template->googleMapsApiKey = $this->parameters->params['googleMapsApiKey'] ?? '';

	}

	public function beforeRender()
	{
		/*$pole = [ "username" => "Kryštof",
				  "password" => \App\Core\Authenticator::calculateHash ( "armagedon", "Kryštof" ),
				  "email" => "k.tulinger@seznam.cz" ];
		$u = $this -> context -> getService ( "users" ) -> create ( $pole );
		$this -> context -> getService ( "users" ) -> insert ( $u );*/

		parent::beforeRender();

		$this->template->sliderPictures = $this->getSlideShow();

		$this->template->userClass = $this->userClass;


	}

	public function handleNextLast($offset)
	{
		$limit = 10;
		$this->template->last = $this->pubs->limit($limit, $offset, [ "hidden" => false ], [ "inserted" => "DESC" ]);
		$this->template->lastOffset = $offset + $limit;
		$this->redrawControl('last');
	}

	public function handleNextRated($offset)
	{
		$limit = 10;
		$this->template->rated = $this->pubs->lastRated($limit, $offset, [ "hidden" => false ]);
		$this->template->ratedOffset = $offset + $limit;
		$this->redrawControl('rated');
	}


	protected function getSlideShow($max = 200)
	{
		$files = [];
		$sliderDir = $this->parameters->params [ "sliderSrc" ];
		$dirname = WWW_DIR . "/" . $sliderDir . "/" . $this->parameters->params [ "slider" ] [ "md" ];
		if ($handle = opendir($dirname)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != ".." && !is_dir($dirname ."/".$entry)) {
					$std = new \StdClass();
					$std->sliderDir = $sliderDir;
					$std->path = $entry;
					$std->sliderSettings = $this->parameters->params [ "slider" ];
					$files [] = $std;
				}
			}
			closedir($handle);
		}
		return $files;
	}

	protected function createComponentSearchForm($name)
	{
		$session = $this->getSession()->getSection('search');
		return new Front\SearchForm($this, $name, $session);
	}

	protected function createComponentRating()
	{
		return new Accessory\RatingControl();
	}


	public function createComponentDialog($name)
	{
		return new Accessory\Dialog();
	}

	public function dialog()
	{
		return $this['dialog'];
	}


}
