<?php

namespace App\Presentation\Admin\Cron;

use Nette;
use Tulinkry;

/**
 */
class CronPresenter extends \App\Presentation\Admin\BasePresenter
{
	/** @inject @var \App\Model\EmailModel */
	public $emails;
	/** @inject @var \App\Model\PubModel */
	public $pubs;
	/** @inject @var \App\Model\RatingModel */
	public $ratings;


	public function renderDefault()
	{
		$emails = $this->template->emails = $this->emails->by('UNSEEN');
		foreach ($emails as $email) {
			if (preg_match("/Re:/", $email->subject)) {
				if (preg_match("/ds5fsd54fssf6sdf4\[ID:/", $email->message->plain)) {
					$parts = explode("ds5fsd54fssf6sdf4[ID:", $email->message->plain);
					if (count($parts) === 2) {
						$id = explode("]", $parts [ 1 ]) [ 0 ];
						if (($pub = $this->pubs->item($id)) !== null) {
							$pub->update([ 'hidden' => false ]);
							$this->emails->read($email->id);
						}
					}
				}
			}
		}
	}

	public function renderRating()
	{
		$ratings = $this->ratings->last();
		$pubs = [];
		foreach ($ratings as $rating) {
			$pub = $rating->ref('pubs', 'pub_id');
			$rating->update([ 'calculated' => true ]);
			\App\Model\PubModel::recomputeAndTouch($pub);
		}

		$this->template->ratings = $ratings;
	}


}
