<?php

namespace AdminModule\Presenters;

use Nette,
	Model,
	FrontModule\Controls,
	Nette\Application\UI\Multiplier,
	AdminModule\Forms,
	Oli,
	Tulinkry;


/**
 */
class CronPresenter extends \FrontModule\Presenters\BasePresenter
{
	/** @inject @var Model\EmailModel */
	public $emails;
	/** @inject @var Model\PubModel */
	public $pubs;
	/** @inject @var Model\RatingModel */
	public $ratings;


	public function renderDefault()
	{
		$emails = $this -> template -> emails = $this -> emails -> by ( 'UNSEEN' );
		foreach ( $emails as $email ) {
			if ( preg_match ( "/Re:/", $email -> subject ) ) {
				if ( preg_match ( "/ds5fsd54fssf6sdf4\[ID:/", $email -> message -> plain ) ) {
					$parts = explode ( "ds5fsd54fssf6sdf4[ID:", $email -> message -> plain );
					if ( count ($parts) === 2 ) {
						$id = explode ( "]", $parts [ 1 ] ) [ 0 ];
						if ( ( $pub = $this -> pubs -> item ( $id ) ) !== NULL ) {
							$pub -> hidden = false;
							$this -> pubs -> update ( $pub );
							$this -> emails -> read ( $email -> id );
						}
					}
				}
			}
		}
	}

	public function renderRating()
	{
		$ratings = $this -> ratings -> last ();
		$pubs = [];
		foreach ( $ratings as $rating ) {
			$rating -> pub -> recompute ();
			$rating -> calculated = true;
			$rating -> pub -> updated = new Tulinkry\DateTime;

		}

		$this -> pubs -> flush ();
		$this -> ratings -> flush ();

		$this -> template -> ratings = $ratings;
	}


}