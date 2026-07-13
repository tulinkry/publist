<?php

namespace App\Forms\Admin;

use Tulinkry;

class RatingForm extends \App\Forms\Front\RatingForm
{
	public function __construct($pubs, $ratings, $beers, $id, $beerRatings = null)
	{
		$rating = $ratings->item($id);

		// Passing $rating (and $beerRatings) through lets the parent
		// constructor's "if ($rating)" branch do all the defaults-filling
		// (mandatory/optional containers, beer rows via
		// rating_beer.rating_id, the "id" hidden field) and wires up the
		// per-beer "deleteBeer" buttons against $beerRatings->delete() -
		// there is nothing left to duplicate here.
		parent::__construct($pubs, $ratings, $beers, null, $rating, null, $beerRatings);

		$this->removeComponent($this [ "submit" ]);
		$this->addSubmit("submit", "Uložit hodnocení")
			->onClick [] = array( $this, "ratingFormUpdated" );
	}

	public function ratingFormUpdated($button)
	{
		$form = $button->form;
		$values = $form->getValues('array');

		if (!$this->presenter->user->isAllowed('backend')) {
			$this->presenter->flashMessage("Nemáte dostatečná oprávnění pro vstup do této časti webu.", "warning");
			$this->presenter->redirect(":Front:Pub:default");
		}

		// The "id" hidden field holds $pub_id (see App\Forms\Front\RatingForm's
		// constructor), not the rating's own id - Admin's constructor always
		// passes pub_id as null, so looking the rating up via $values["id"]
		// never found anything. $this->rating is the entity already fetched
		// by the constructor for this exact edit.
		$entity = $this->rating;

		if (!$entity) {
			$form->presenter->flashMessage("Neexistující hodnocení!", "error");
			return;
		}

		// beer_id (real "beers" PK) => [ 'beer_price' => .., 'beer_criteria' => .. ]
		// for every beer entry still present in the submission (both
		// beers that already existed on this rating and brand-new ones -
		// beers removed via the "deleteBeer" AJAX button were already
		// deleted directly against the DB at click-time, see the
		// constructor's deleteBeer onClick wiring inherited from
		// App\Forms\Front\RatingForm).
		$beerValues = [];
		if (isset($values [ 'optional' ] [ 'beers' ])) {
			foreach ($values [ 'optional' ] [ 'beers' ] as $beer) {
				if (!($b = $this->beers->item($beer [ 'brand' ]))) {
					$form->presenter->flashMessage("Jedno z uvedených piv neexistuje!", "error");
					return;
				}

				if (array_key_exists($b->id, $beerValues)) {
					$form->presenter->flashMessage("Pokoušíte se zadat vícekrát to stejné pivo.", "error");
					return;
				}

				$beerValues [ $b->id ] = [
					'beer_price' => $beer [ 'price' ],
					'beer_criteria' => $beer [ 'beerCriteria' ] == 0 ? null : $beer [ 'beerCriteria' ],
				];
			}
		}

		// Build the "ratings" row column values directly (real column
		// names), instead of the old Doctrine entity-property dance.
		$ratingColumnValues = [];

		foreach (self::MANDATORY_FIELDS as $column => $field) {
			$ratingColumnValues [ $column ] = $values [ "mandatory" ] [ $field ];
		}

		foreach (self::OPTIONAL_CRITERIA_FIELDS as $column => $field) {
			$ratingColumnValues [ $column ] = $values [ "optional" ] [ $field ] == 0 ? null : $values [ "optional" ] [ $field ];
		}

		$ratingColumnValues [ 'wine_price' ] = $values [ "optional" ] [ "winePrice" ];
		$ratingColumnValues [ 'garden' ] = $values [ "optional" ] [ "garden" ];

		try {
			// ActiveRow::update() refetches with select('*') (raw columns
			// only), dropping the aliased "id" column - grab it first.
			$ratingId = $entity->id;
			$entity->update($ratingColumnValues);

			foreach ($beerValues as $beerId => $data) {
				$this->beerRatings->upsert($ratingId, $beerId, $data);
			}
		} catch (\Exception $e) {
			$form->presenter->flashMessage("Hodnocení se nepodařilo uložit do databáze.", "error");
			return;
		}

		$pub = $entity->ref('pubs', 'pub_id');
		\App\Model\PubModel::recomputeAndTouch($pub);

		$this->presenter->flashMessage("Hodnocení bylo uloženo", "success");

		// $pub came from ->ref(), a raw Nette relation lookup that bypasses
		// Repository's "pub_id AS id" alias - use the real column name.
		$this->presenter->redirect("Pub:detail", [ "id" => $pub->pub_id,
														 "paginator-page" => $this->presenter->getPaginator()->page,
														 "paginator2-page" => $this->presenter["paginator2"]->page ]);


	}

}
