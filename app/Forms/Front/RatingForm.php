<?php

namespace App\Forms\Front;

use Tulinkry\Forms;
use Nette\Utils\Html;
use Tulinkry\Utils\Strings;
use Tulinkry;

class RatingForm extends Forms\Form
{
	/**
	 * Maps real "ratings" table columns (snake_case) to the form field
	 * names declared below (camelCase *Criteria fields). Used both to
	 * prefill an existing rating's ActiveRow::toArray() into the form and
	 * to translate submitted form values back into column names for
	 * insert()/update().
	 */
	public const FIELD_MAP = [
		'interier_criteria' => 'interierCriteria',
		'exterier_criteria' => 'exterierCriteria',
		'service_criteria' => 'serviceCriteria',
		'overall_criteria' => 'overallCriteria',
		'food_price_criteria' => 'foodPriceCriteria',
		'wine_criteria' => 'wineCriteria',
		'toalets_criteria' => 'toaletsCriteria',
		'food_criteria' => 'foodCriteria',
		'wine_price' => 'winePrice',
	];

	/** mandatory-container fields, all always filled by RadioList (never NULL) */
	public const MANDATORY_FIELDS = [
		'interier_criteria' => 'interierCriteria',
		'exterier_criteria' => 'exterierCriteria',
		'service_criteria' => 'serviceCriteria',
		'overall_criteria' => 'overallCriteria',
		'food_price_criteria' => 'foodPriceCriteria',
	];

	/** optional-container criteria fields where "0" means "not rated" -> NULL */
	public const OPTIONAL_CRITERIA_FIELDS = [
		'wine_criteria' => 'wineCriteria',
		'toalets_criteria' => 'toaletsCriteria',
		'food_criteria' => 'foodCriteria',
	];

	protected $pubs;
	protected $ratings;
	protected $beers;
	protected $users;
	protected $beerRatings;
	protected $pub;
	protected $rating;

	public function __construct($pubs, $ratings, $beers, $users, $rating, $pub_id, $beerRatings = null)
	{
		parent::__construct();

		$this->getElementPrototype()->class = "ratingForm";

		$this->pubs = $pubs;
		$this->ratings = $ratings;
		$this->beers = $beers;
		$this->users = $users;
		$this->rating = $rating;
		$this->beerRatings = $beerRatings;

		if ($pub_id) {
			$this->pub = $this->pubs->item($pub_id);
		}

		$this->addHidden("id", $pub_id);

		$this->addGroup("Povinné prvky");
		$mandatory = $this->addContainer("mandatory");



		foreach ([ "interier" => "Interiér",
					"exterier" => "Exteriér",
					"service" => "Personál",
					"overall" => "Celkový dojem",
					"foodPrice" => "Celková spokojenost s cenou" ] as $key => $criteria) {
			$min = constant("\App\Model\PubModel::" . strtoupper(Strings::decamelize($key)) . "_MIN");
			$max = constant("\App\Model\PubModel::" . strtoupper(Strings::decamelize($key)) . "_MAX");

			// Reset per criteria - min/max can differ between criteria, and
			// without this stale keys from a previous iteration's range leak
			// into the next criteria's radio list.
			$stars = array();
			for ($i = $min; $i <= $max; $i++) {
				$stars [ $i ] = "$i/$max";
			}

			$mandatory->addRadioList($key . "Criteria", $criteria, $stars)
				->setAttribute('class', 'range mandatory')
				->getSeparatorPrototype()
					->setName('');

			$mandatory [ $key . "Criteria" ]
				->setDefaultValue(1);

			/*$star = Controls\StarRatingHelper::IMAGE_PATH;*/

		}


		$this->addGroup("Nepovinné prvky");
		$optional = $this->addContainer("optional");

		$beerModel = $this->beers;
		$replicator = $optional->addDynamic('beers', function ($beer) use ($beerModel) {
			//$beer->currentGroup = $beer->form->addGroup('Pivo', FALSE);
			$min = constant("\App\Model\PubModel::BEER_MIN");
			$max = constant("\App\Model\PubModel::BEER_MAX");

			$stars = array(
				0 => "Nehodnotit"
			);

			for ($i = $min; $i <= $max; $i++) {
				$stars [ $i ] = "$i/$max";
			}

			$brands = $beerModel->by([], [ "name" => "ASC", "degree" => "ASC" ])->fetchPairs('id', 'name');


			$beer->addSelect('brand', "Značka", $brands)
				  ->setRequired()
				  ->setOption("description", Html::el("span")->setText(""))
				  ->setPrompt("Vyberte značku piva")
				  ->addRule(Forms\Form::FILLED, "Značka piva musí být vyplněna.");

			$beer->addRadioList('beerCriteria', "Pivo", $stars)
				  ->setAttribute('class', 'range optional')
				  ->setDefaultValue(0)
				  ->setValue(0)
				 // -> setRequired ()
				  ->getSeparatorPrototype()
					->setName('');


			$beer->addSelect('price', "Cena 0.5l", Tulinkry\Utils\Arrays::createSequence(10, 100))
				  ->setOption("description", Html::el("span")->setText("Kč"))
				  ->setPrompt("Nehodnotit");
			//-> getControlPrototype() -> insert(0, Html::el( "div class=input-group-addon" ) -> setText( "$" ) );

			$beer->addSubmit('deleteBeer', 'Smazat pivo')
				  ->setValidationScope([])
				  ->setAttribute("class", "ajax deleteBeer btn btn-danger")
				  ->setAttribute("data-confirm", "Opravdu?")
				  ->onClick [] = function ($button) {
				  	$beers = $button->getParent()->getParent();
				  	$beers->form->presenter->redrawControl('ratingForm');
				  	$beers->remove($button->getParent(), true);
				  };
		}, 0);

		$replicator->addSubmit('addBeer', 'Přidat pivo')
					->setValidationScope([])
					->setAttribute("class", "ajax addBeer btn btn-success")
					->onClick [] = function ($button) {
						$beers = $button->getParent();
						if ($beers->isAllFilled() && $beers->isValid()) {
							$button->getParent()->createOne();
						} else {
							$button->form->presenter->flashMessage("Nejdříve vyplňte předchozí piva.", "error");
						}

						$button->form->presenter->redrawControl('ratingForm');
					};


		foreach ([ "wine" => "Víno",
					"toalets" => "Toalety",
					"food" => "Jídlo" ] as $key => $criteria) {
			$min = constant("\App\Model\PubModel::" . strtoupper(Strings::decamelize($key)) . "_MIN");
			$max = constant("\App\Model\PubModel::" . strtoupper(Strings::decamelize($key)) . "_MAX");

			$stars = array(
				0 => "Nehodnotit"
			);

			for ($i = $min; $i <= $max; $i++) {
				$stars [ $i ] = "$i/$max";
			}

			$optional->addRadioList($key . "Criteria", $criteria, $stars)
					  ->setDefaultValue(0)
					  ->setAttribute('class', 'range optional')
					  ->getSeparatorPrototype()
						->setName('');

			//$star = Controls\StarRatingHelper::IMAGE_PATH;

		}


		foreach ([ "winePrice" => "Cena vína 0.2l" ] as $key => $criteria) {
			$min = constant("\App\Model\PubModel::" . strtoupper(Strings::decamelize($key)) . "_MIN");
			$max = constant("\App\Model\PubModel::" . strtoupper(Strings::decamelize($key)) . "_MAX");
			$seq = Tulinkry\Utils\Arrays::createSequence($min, $max);
			$optional->addSelect($key, $criteria, $seq)
				  ->setPrompt("Nehodnotit")
				  ->setOption("description", Html::el("span")->setText("Kč"))
				  // Regression: the extra "$optional[$key]" argument here used
				  // to be passed as addCondition()'s $value, and Nette
				  // auto-dereferences a Control argument to its own current
				  // value - so this compared the control's value to itself
				  // (always EQUAL, so the negated condition was always false)
				  // and the RANGE rule below could never actually run.
				  ->addCondition(~Forms\Form::EQUAL, "")
					  ->addRule(Forms\Form::RANGE, "Hodnota musí být mezi %d a %d.", array( $min, $max ));
		}

		$optional [ "winePrice" ]->setDefaultValue(null);


		$optional->addCheckbox('garden', "Zahrádka")
				  ->setAttribute('class', 'switch')
				  ->setAttribute('data-on-text', 'Ano')
				  ->setAttribute('data-off-text', 'Ne')
				  ->setDefaultValue(0);

		/*$this -> addGroup ( "Osobní informace" );

		$this -> addText ( "name", "Jméno" )
			  -> setAttribute ( "placeholder", "Petr Novák" )
			  -> addRule ( Forms\Form::FILLED, "Zadejte vaše jméno." );

		$this -> addText ( "email", "Email" )
			  -> setAttribute ( "placeholder", "vas@email.cz" )
			  -> addRule ( Forms\Form::EMAIL, "Zadejte validní formát emailové adresy." );*/

		$this->addGroup();


		$this->addSubmit("submit", "Odeslat hodnocení")
			  ->setAttribute("class", "btn-primary")
			  ->onClick [] = array( $this, "ratingFormSubmitted" );

		if ($rating) {
			// fill entity - $rating is a Nette\Database\Table\ActiveRow now,
			// toArray() returns real (snake_case) column names, so remap
			// them to the camelCase *Criteria form field names before
			// handing them to setDefaults().
			$entity = $rating->toArray();
			foreach (self::FIELD_MAP as $column => $field) {
				if (array_key_exists($column, $entity)) {
					$entity [ $field ] = $entity [ $column ];
				}
			}
			$this [ "mandatory" ]->setDefaults($entity);
			$this [ "optional" ]->setDefaults($entity);


			$this [ "optional" ] [ "beers" ]->createDefault = 0;
			foreach ($rating->related('rating_beer.rating_id') as $p) {
				$one = $this [ "optional" ] [ "beers" ] [ $p->beer_id ];
				$one [ "brand" ]->setDefaultValue($p->beer_id);
				$one [ "price" ]->setDefaultValue($p->beer_price);
				$one [ "beerCriteria" ]->setDefaultValue($p->beer_criteria);
			}


			$this->setDefaults($entity);

			$form = $this;
			$beerRatingsModel = $this->beerRatings;
			foreach ($this['optional']['beers']->containers as $cnt) {
				$cnt['deleteBeer']->onClick[] = function ($button) use ($rating, $beerRatingsModel, $form) {
					try {
						$beerRatingsModel->delete($rating->id, (int) $button->parent->name);
					} catch (\Exception $e) {
						// probably is not attached now but will be hopefully
						$form->presenter->flashMessage('Nastala chyba při mazání piva, operaci se nepodařilo dokončit.', 'danger');
						$form->presenter->redirect('this');
					}
				};

			}

		}



	}

	public function ratingFormSubmitted($button)
	{
		$form = $button->form;
		$values = $form->getValues('array');

		//\Tracy\Dumper::dump ( $values );

		//return;

		if (!$this->presenter->user->isAllowed('frontend')) {
			$form->presenter->flashMessage("Na hodnocení musíte být přihlášeni.", "warning");
			$this->presenter->redirectLogin();
			return;
		}

		if (!($user = $this->users->item($this->presenter->user->id))) {
			$form->presenter->flashMessage("Neexistující uživatel!", "error");
			$this->presenter->user->logout(true);
			$form->presenter->redirect("Sign:login");
			return;
		}

		if (!$this->pub) {
			$form->presenter->flashMessage("Restaurace neexistuje!", "error");
			$form->presenter->redirect("Pub:default");
			return;
		}

		$existing = $this->pub->related('ratings.pub_id')
			->where('user_id', $user->id)
			->where('date > ?', date('Y-m-d H:i:s', time() - \App\Model\RatingModel::RATING_INTERVAL))
			->where('date < ?', date('Y-m-d H:i:s', time() - \App\Model\RatingModel::RATING_CLOSURE))
			->fetch();
		if ($existing) {
			$n = [ "year" => "letech", "month" => "měsících", "day" => "dnech", "hour" => "hodinách", "minute" => "minutách", "second" => "sekundách" ];
			$str = sprintf("Už jste hlasoval v posledních %s", \App\Model\RatingModel::formatDuration(\App\Model\RatingModel::RATING_INTERVAL, $n));
			$this->presenter->flashMessage($str, "error");
			return;
		}

		// $beerValues: beer_id (real "beers" PK) => [ 'beer_price' => .., 'beer_criteria' => .. ]
		// for every beer entry still present in the submission (both
		// beers that already existed on this rating and brand-new ones -
		// beers removed via the "deleteBeer" AJAX button were already
		// deleted directly against the DB at click-time, see the
		// constructor's deleteBeer onClick wiring above).
		$beerValues = [];
		if (isset($values['optional']['beers'])) {
			foreach ($values [ 'optional' ] [ 'beers' ] as $beer) {
				if (!($b = $this->beers->item($beer [ 'brand' ]))) {
					$this->presenter->flashMessage("Jedno z uvedených piv neexistuje!", "error");
					return;
				}

				if (array_key_exists($b->id, $beerValues)) {
					$this->presenter->flashMessage("Pokoušíte se zadat vícekrát to stejné pivo.", "error");
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
		$ratingColumnValues = [
			'pub_id' => $this->pub->id,
			'user_id' => $user->id,
			'date' => new Tulinkry\DateTime(),
			'calculated' => false,
		];

		foreach (self::MANDATORY_FIELDS as $column => $field) {
			$ratingColumnValues [ $column ] = $values [ "mandatory" ] [ $field ];
		}

		foreach (self::OPTIONAL_CRITERIA_FIELDS as $column => $field) {
			$ratingColumnValues [ $column ] = $values [ "optional" ] [ $field ] == 0 ? null : $values [ "optional" ] [ $field ];
		}

		$ratingColumnValues [ 'wine_price' ] = $values [ "optional" ] [ "winePrice" ];
		$ratingColumnValues [ 'garden' ] = $values [ "optional" ] [ "garden" ];

		try {
			if ($this->rating) {
				// ActiveRow::update() refetches with select('*') (raw columns
				// only), dropping the aliased "id" column - grab it first.
				$ratingId = $this->rating->id;
				$this->rating->update($ratingColumnValues);
			} else {
				$ratingRow = $this->ratings->insert($ratingColumnValues);
				$ratingId = $ratingRow->id;
			}

			foreach ($beerValues as $beerId => $data) {
				$this->beerRatings->upsert($ratingId, $beerId, $data);
			}
		} catch (\Exception $e) {
			$this->presenter->flashMessage("Hodnocení se nepodařilo uložit do databáze.", "error");
			return;
			//$this -> presenter -> redirect ( "this" );
		}

		if ($this->rating) {
			$this->presenter->flashMessage("Hodnocení bylo úspěšně uloženo.");
		} else {
			$this->presenter->flashMessage("Hodnocení bylo úspěšně vloženo.");

			$n = [ "year" => "roky", "month" => "měsíce", "day" => "dny", "hour" => "hodin", "minute" => "minut", "second" => "sekund" ];
			$this->presenter->flashMessage(sprintf("Nyní zůstane otevřené pro volné úpravy po %s, a poté se uzavře a systém ho započítá do hodnocení celé restaurace.", \App\Model\RatingModel::formatDuration(\App\Model\RatingModel::RATING_CLOSURE, $n)));
			$n = [ "year" => "roky", "month" => "měsíce", "day" => "dny", "hour" => "hodiny", "minute" => "minuty", "second" => "sekundy" ];
			$this->presenter->flashMessage(sprintf("Toto restaurační zařízení budete moci ohodnotit opět až za %s.", \App\Model\RatingModel::formatDuration(\App\Model\RatingModel::RATING_INTERVAL, $n)));
		}
		$this->presenter->redirect("Pub:detail", [ "id" => $this->pub->id ]);
	}

}
