<?php

namespace App\Model;

use Nette\Database\ResultSet;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Image;
use Tulinkry\Utils\Strings;
use Nette\Utils\Random;

class PubModel extends Repository
{
	public const BEER_WEIGHT = 1;
	public const WINE_WEIGHT = 1;
	public const FOOD_WEIGHT = 1;
	public const TOALETS_WEIGHT = 1;
	public const INTERIER_WEIGHT = 1;
	public const EXTERIER_WEIGHT = 1;
	public const SERVICE_WEIGHT = 1;
	public const OVERALL_WEIGHT = 1;

	public const BEER_PRICE_WEIGHT = 1;
	public const WINE_PRICE_WEIGHT = 1;
	public const FOOD_PRICE_WEIGHT = 1;

	public const BEER_MIN = 1;
	public const BEER_PRICE_MIN = 10;
	public const WINE_MIN = 1;
	public const WINE_PRICE_MIN = 10;
	public const FOOD_MIN = 1;
	public const FOOD_PRICE_MIN = 1;
	public const TOALETS_MIN = 1;
	public const INTERIER_MIN = 1;
	public const EXTERIER_MIN = 1;
	public const SERVICE_MIN = 1;
	public const OVERALL_MIN = 1;

	public const BEER_MAX = 10;
	public const BEER_PRICE_MAX = 100;
	public const WINE_MAX = 10;
	public const WINE_PRICE_MAX = 500;
	public const FOOD_MAX = 10;
	public const FOOD_PRICE_MAX = 10;
	public const TOALETS_MAX = 10;
	public const INTERIER_MAX = 10;
	public const EXTERIER_MAX = 10;
	public const SERVICE_MAX = 10;
	public const OVERALL_MAX = 10;

	public const GALLERY_PATH = "images/pubs";


	protected function tableName(): string
	{
		return 'pubs';
	}

	protected function primaryKey(): string
	{
		return 'pub_id';
	}

	// Templates still use the old Doctrine entity's camelCase property names
	// for these three columns.
	protected function columnAliases(): array
	{
		return [
			'wholeName' => 'whole_name',
			'longName' => 'long_name',
			'openingHours' => 'opening_hours',
		];
	}


	/**
	 * @return array of Nette\Utils\Image
	 */
	public static function getImages(?ActiveRow $pub = null)
	{
		if (!$pub) {
			return array();
		}

		$dirname = WWW_DIR . "/" . self::GALLERY_PATH . "/" . $pub->id;
		$thumbnailDir = $dirname . "/thumbnails";
		if (!is_dir($dirname)) {
			return array();
		}

		$files = [];
		if ($handle = opendir($dirname)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != ".." && is_file(self::GALLERY_PATH . "/" . $pub->id . "/" . $entry)) {
					$thb =  self::GALLERY_PATH . "/" . $pub->id . "/thumbnails/" . $entry;
					$pic = self::GALLERY_PATH . "/" . $pub->id . "/" . $entry;
					$files [ $entry ] = new \StdClass();
					$files [ $entry ]->path = $pic;
					$files [ $entry ]->thumbnail = $thb;
					$files [ $entry ]->lastUpdated = filemtime($dirname . "/" . $entry);
				}
			}
			closedir($handle);
		}

		return $files;
	}

	/**
	 * saves one image into filesystem
	 */
	public static function saveImage(Image $img, ?ActiveRow $pub = null): bool
	{
		if (!$pub || !$img) {
			return false;
		}

		$dirname = WWW_DIR . "/" . self::GALLERY_PATH . "/" . $pub->id;
		$thumbnailDir = $dirname . "/thumbnails";
		if (!file_exists($dirname)) {
			mkdir($dirname, 0777, true);
		}

		if (!file_exists($thumbnailDir)) {
			mkdir($thumbnailDir, 0777, true);
		}

		do {
			$name = Random::generate(10) . ".jpg";
		} while (file_exists($dirname . "/" . $name));

		// Image::save() is void and throws on failure in current Nette (it
		// used to return bool), and resize() no longer accepts a "300px"
		// suffixed string (plain int or a "N%" string only) - both bugs
		// meant the thumbnail save silently never even ran (short-circuited
		// by the first save()'s now-always-falsy return value).
		try {
			$img->save($dirname . "/" . $name, 80, Image::JPEG);
			$img->resize(300, null)->save($thumbnailDir . "/" . $name, 80, Image::JPEG);
		} catch (\Nette\Utils\ImageException $e) {
			return false;
		}

		return true;
	}

	/**
	 * deletes image from filesystem
	 * @return boolean for success or failure
	 */
	public static function deleteImage(string $filename, ?ActiveRow $pub = null): bool
	{
		if (!$pub) {
			return false;
		}

		$filename = basename($filename);
		$dirname = WWW_DIR . "/" . self::GALLERY_PATH . "/" . $pub->id;
		$thumbnailDir = $dirname . "/thumbnails";

		$ret = false;

		if (file_exists($dirname . "/" . $filename)) {
			$ret = @unlink($dirname . "/" . $filename);
		}

		if (file_exists($thumbnailDir . "/" . $filename)) {
			$ret = $ret && @unlink($thumbnailDir . "/" . $filename);
		}

		return $ret;
	}

	/**
	 * deletes all images associated to particular $pub
	 * @return boolean
	 */
	public static function deleteImages(?ActiveRow $pub = null): bool
	{
		$images = self::getImages($pub);
		$ret = true;
		foreach ($images as $name => $image) {
			$ret2 = self::deleteImage($name, $pub);
			$ret = $ret && $ret2;
		}
		return $ret;
	}


	/**
	 * rotates image and saves it back to the same file
	 * @return boolean
	 */
	public static function rotateImage(string $filename, ?ActiveRow $pub = null, int $angle = 90): bool
	{
		if (!$pub) {
			return false;
		}

		$filename = basename($filename);
		$dirname = WWW_DIR . "/" . self::GALLERY_PATH . "/" . $pub->id;
		$thumbnailDir = $dirname . "/thumbnails";

		if (!file_exists($dirname . "/" . $filename) || !file_exists($thumbnailDir . "/" . $filename)) {
			return false;
		}

		try {
			$img = Image::fromFile($dirname . "/" . $filename);
			$img->rotate(-$angle, 0);
			$img->save($dirname . "/" . $filename);

			$img = Image::fromFile($thumbnailDir . "/" . $filename);
			$img->rotate(-$angle, 0);
			$img->save($thumbnailDir . "/" . $filename);
		} catch (\Nette\Utils\ImageException $e) {
			return false;
		}

		return true;
	}



	public static function rating(ActiveRow $pub)
	{
		$ratings = $pub->related('ratings.pub_id');
		$cnt = count($ratings);
		if ($cnt <= 0) {
			return 0;
		}

		$sum = 0;
		foreach ($ratings as $rating) {
			$sum += self::singleRating($rating);
		}

		return $sum / $cnt;
	}

	public static function singleRating(ActiveRow $rating)
	{
		$ratingLoc = 0;
		$ratingMax = 0;

		if (($ret = self::singleBeerRating($rating)) !== null) {
			$ratingLoc += $ret;
			$ratingMax += self::BEER_MAX * self::BEER_WEIGHT;
		}
		if (($ret = self::singleWineRating($rating)) !== null) {
			$ratingLoc += $ret;
			$ratingMax += self::WINE_MAX * self::WINE_WEIGHT;
		}
		if (($ret = self::singleFoodRating($rating)) !== null) {
			$ratingLoc += $ret;
			$ratingMax += self::FOOD_MAX * self::FOOD_WEIGHT;
		}
		if (($ret = self::singleToaletsRating($rating)) !== null) {
			$ratingLoc += $ret;
			$ratingMax += self::TOALETS_MAX * self::TOALETS_WEIGHT;
		}
		if (($ret = self::singleInterierRating($rating)) !== null) {
			$ratingLoc += $ret;
			$ratingMax += self::INTERIER_MAX * self::INTERIER_WEIGHT;
		}
		if (($ret = self::singleExterierRating($rating)) !== null) {
			$ratingLoc += $ret;
			$ratingMax += self::EXTERIER_MAX * self::EXTERIER_WEIGHT;
		}
		if (($ret = self::singleServiceRating($rating)) !== null) {
			$ratingLoc += $ret;
			$ratingMax += self::SERVICE_MAX * self::SERVICE_WEIGHT;
		}
		if (($ret = self::singleOverallRating($rating)) !== null) {
			$ratingLoc += $ret;
			$ratingMax += self::OVERALL_MAX * self::OVERALL_WEIGHT;
		}
		if (($ret = self::singleFoodPriceRating($rating)) !== null) {
			$ratingLoc += $ret;
			$ratingMax += self::FOOD_PRICE_MAX * self::FOOD_PRICE_WEIGHT;
		}

		return $ratingMax > 0 ? $ratingLoc / $ratingMax : 0;
	}

	public static function commodityRating($commodity, ActiveRow $pub)
	{
		$ratings = $pub->related('ratings.pub_id');

		if ($commodity == "" || $pub == null || count($ratings) <= 0) {
			return 0;
		}
		$sum = 0;
		$cnt = 0;
		$methodName = static::class . '::' . $commodity . 'Rating';
		foreach ($ratings as $rating) {
			if (($res = forward_static_call($methodName, $rating)) === null) {
				continue;
			}
			$sum += $res;
			$cnt++;
		}
		if (!$cnt) {
			return null;
		}

		return $sum / $cnt;
	}


	public static function beerRating(ActiveRow $pub)
	{
		if (($ret = self::commodityRating("singleBeer", $pub)) === null) {
			return null;
		}
		return  $ret / (self::BEER_MAX * self::BEER_WEIGHT);
	}

	public static function wineRating(ActiveRow $pub)
	{
		if (($ret = self::commodityRating("singleWine", $pub)) === null) {
			return null;
		}
		return $ret / (self::WINE_MAX * self::WINE_WEIGHT);
	}

	public static function foodRating(ActiveRow $pub)
	{
		if (($ret = self::commodityRating("singleFood", $pub)) === null) {
			return null;
		}
		return $ret / (self::FOOD_MAX * self::FOOD_WEIGHT);
	}
	public static function toaletsRating(ActiveRow $pub)
	{
		if (($ret = self::commodityRating("singleToalets", $pub)) === null) {
			return null;
		}
		return $ret / (self::TOALETS_MAX * self::TOALETS_WEIGHT);
	}

	public static function interierRating(ActiveRow $pub)
	{
		if (($ret = self::commodityRating("singleInterier", $pub)) === null) {
			return null;
		}
		return $ret / (self::INTERIER_MAX * self::INTERIER_WEIGHT);
	}

	public static function exterierRating(ActiveRow $pub)
	{
		if (($ret = self::commodityRating("singleExterier", $pub)) === null) {
			return null;
		}
		return $ret / (self::EXTERIER_MAX * self::EXTERIER_WEIGHT);
	}


	public static function serviceRating(ActiveRow $pub)
	{
		if (($ret = self::commodityRating("singleService", $pub)) === null) {
			return null;
		}
		return $ret / (self::SERVICE_MAX * self::SERVICE_WEIGHT);
	}

	public static function overallRating(ActiveRow $pub)
	{
		if (($ret = self::commodityRating("singleOverall", $pub)) === null) {
			return null;
		}
		return $ret / (self::OVERALL_MAX * self::OVERALL_WEIGHT);
	}

	public static function foodPriceRating(ActiveRow $pub)
	{
		if (($ret = self::commodityRating("singleFoodPrice", $pub)) === null) {
			return null;
		}
		return $ret / (self::FOOD_PRICE_MAX * self::FOOD_PRICE_WEIGHT);
	}


	public static function beerPriceRating(ActiveRow $pub)
	{
		// Always NULL, exactly as in the original Doctrine-era code: pubs
		// no longer carry an aggregate beerPrice column (see schema note),
		// beer price now only exists per-beer on rating_beer.
		return null;
	}

	public static function winePriceRating(ActiveRow $pub)
	{
		return self::commodityRating("singleWinePrice", $pub);
	}


	public static function singleBeerRating(ActiveRow $rating)
	{
		$beerRatings = $rating->related('rating_beer.rating_id');

		if (count($beerRatings) <= 0) {
			return null;
		}
		$sum = 0;
		$cnt = 0;
		foreach ($beerRatings as $beer_rating_ref) {
			if ($beer_rating_ref->beer_criteria === null) {
				continue;
			}
			$sum += $beer_rating_ref->beer_criteria;
			$cnt++;
		}

		if (!$cnt) {
			return null;
		}

		return ($sum / $cnt) * self::BEER_WEIGHT;
	}

	public static function singleWineRating(ActiveRow $rating)
	{
		if ($rating->wine_criteria === null) {
			return null;
		}
		return ($rating->wine_criteria * self::WINE_WEIGHT);
	}

	public static function singleFoodRating(ActiveRow $rating)
	{
		if ($rating->food_criteria === null) {
			return null;
		}
		return ($rating->food_criteria * self::FOOD_WEIGHT);
	}

	public static function singleToaletsRating(ActiveRow $rating)
	{
		if ($rating->toalets_criteria === null) {
			return null;
		}
		return ($rating->toalets_criteria * self::TOALETS_WEIGHT);
	}

	public static function singleInterierRating(ActiveRow $rating)
	{
		if ($rating->interier_criteria === null) {
			return null;
		}
		return ($rating->interier_criteria * self::INTERIER_WEIGHT);
	}

	public static function singleExterierRating(ActiveRow $rating)
	{
		if ($rating->exterier_criteria === null) {
			return null;
		}
		return ($rating->exterier_criteria * self::EXTERIER_WEIGHT);
	}


	public static function singleServiceRating(ActiveRow $rating)
	{
		if ($rating->service_criteria === null) {
			return null;
		}
		return ($rating->service_criteria * self::SERVICE_WEIGHT);
	}

	public static function singleOverallRating(ActiveRow $rating)
	{
		if ($rating->overall_criteria === null) {
			return null;
		}
		return ($rating->overall_criteria * self::OVERALL_WEIGHT);
	}

	public static function singleFoodPriceRating(ActiveRow $rating)
	{
		if ($rating->food_price_criteria === null) {
			return null;
		}
		return ($rating->food_price_criteria * self::FOOD_PRICE_WEIGHT);
	}

	#[\Deprecated('unreachable - beerPriceRating() always returns NULL before ever calling this')]
	public static function singleBeerPriceRating(ActiveRow $rating)
	{
		return null;
	}

	public static function singleWinePriceRating(ActiveRow $rating)
	{
		if ($rating->wine_price === null) {
			return null;
		}
		return $rating->wine_price;
	}


	/**
	 * Recomputes the aggregate mark/vote columns for $pub and returns them
	 * as an assoc array of column => value, ready to be passed straight
	 * into $pub->update(...). Ported from the former Entity\Pub::recompute().
	 */
	public static function recompute(ActiveRow $pub): array
	{
		$result = [
			'beerMark' => self::beerRating($pub),
			'wineMark' => self::wineRating($pub),
			'winePrice' => self::winePriceRating($pub),
			'foodMark' => self::foodRating($pub),
			'foodPrice' => self::foodPriceRating($pub),
			'toaletsMark' => self::toaletsRating($pub),
			'interierMark' => self::interierRating($pub),
			'exterierMark' => self::exterierRating($pub),
			'serviceMark' => self::serviceRating($pub),
			'overallMark' => self::overallRating($pub),
		];
		$result [ 'mark' ] = self::rating($pub);

		$result [ 'beerMarkVoted' ] = $result [ 'beerPriceVoted' ] = $result [ 'wineMarkVoted' ] = $result [ 'winePriceVoted' ] = 0;
		$result [ 'foodMarkVoted' ] = $result [ 'foodPriceVoted' ] = $result [ 'markVoted' ] = 0;

		foreach ($pub->related('ratings.pub_id') as $rating) {
			// beerMarkVoted / beerPriceVoted are intentionally left at 0,
			// matching the original (commented-out) Doctrine entity logic.
			$result [ 'wineMarkVoted' ] += $rating->wine_criteria === null ? 0 : 1;
			$result [ 'winePriceVoted' ] += $rating->wine_price === null ? 0 : 1;
			$result [ 'foodMarkVoted' ] += $rating->food_criteria === null ? 0 : 1;
			$result [ 'foodPriceVoted' ] += $rating->food_price_criteria === null ? 0 : 1;
			$result [ 'markVoted' ] += 1;
		}

		return $result;
	}

	/**
	 * recompute() + persisting it plus an "updated" timestamp bump - the
	 * standard "a rating changed, refresh the pub's aggregates" sequence
	 * used after inserting/deleting/recalculating a rating.
	 */
	public static function recomputeAndTouch(ActiveRow $pub): void
	{
		$pub->update(self::recompute($pub) + [ 'updated' => new \Tulinkry\DateTime() ]);
	}

	/**
	 * Returns the distance in metres between $pub and the given point.
	 * Ported verbatim from Entity\Pub::distance() (same haversine formula
	 * and 6372.795*1000 constant).
	 */
	public static function distance(ActiveRow $pub, float $lat, float $lng): float
	{
		return acos(
			cos(deg2rad($pub->latitude)) * cos(deg2rad($pub->longitude)) * cos(deg2rad($lat)) * cos(deg2rad($lng))
			+ cos(deg2rad($pub->latitude)) * sin(deg2rad($pub->longitude)) * cos(deg2rad($lat)) * sin(deg2rad($lng))
			+ sin(deg2rad($pub->latitude)) * sin(deg2rad($lat))
		) * 6372.795 * 1000;
	}

	/**
	 * Whether $lat/$lng fall within valid GPS coordinate ranges.
	 */
	public static function isValidCoordinate(float $lat, float $lng): bool
	{
		return $lat <= 90 && $lat >= -90 && $lng <= 180 && $lng >= -180;
	}

	/**
	 * Per-beer running-average price for a single rating. Ported from
	 * Entity\Rating::getBeerPrice().
	 */
	public static function beerPriceForRating(ActiveRow $rating): array
	{
		if (!$rating->calculated) {
			return [];
		}

		$beerDistinct = [];

		foreach ($rating->related('rating_beer.rating_id') as $beerRating) {
			$beer = $beerRating->ref('beers', 'beer_id');

			$p = new \StdClass();
			$p->count = 0;
			$p->price = 0;
			$p->beer = $beer;

			if ($beerRating->beer_price !== null) {
				$p->price = $p->price * $p->count;
				$p->price += $beerRating->beer_price;
				$p->count++;
				$p->price = $p->price / $p->count;
			}

			$beerDistinct [ $beer->beer_id ] = $p;
		}

		return $beerDistinct;
	}

	/**
	 * Aggregate per-beer price across all calculated ratings of $pub.
	 * Ported from Entity\Pub::getBeerPrice(), merging running averages
	 * per beer id.
	 *
	 * NOTE: the original Doctrine-era method double-counted the first
	 * calculated rating (it seeded $price with that rating's beer prices
	 * and then merged the same rating's beer prices into it again). That
	 * is fixed here - each rating now contributes to the running average
	 * exactly once.
	 */
	public static function beerPrice(ActiveRow $pub): array
	{
		$price = [];

		foreach ($pub->related('ratings.pub_id') as $rating) {
			if (!$rating->calculated) {
				continue;
			}

			foreach (self::beerPriceForRating($rating) as $beerId => $p) {
				if (array_key_exists($beerId, $price) && $p->count > 0) {
					$price [ $beerId ]->price = $price [ $beerId ]->price * $price [ $beerId ]->count;
					$price [ $beerId ]->price += $p->price;
					$price [ $beerId ]->count += $p->count;
					$price [ $beerId ]->price = $price [ $beerId ]->price / $price [ $beerId ]->count;
				} else {
					$price [ $beerId ] = $p;
				}
			}
		}

		foreach ($price as $k => $p) {
			$price [ $k ]->price = $price [ $k ]->count > 0 ? $price [ $k ]->price : null;
		}

		return $price;
	}

	/**
	 * Ported from Entity\Pub::getLastDescription().
	 */
	public static function lastDescription(ActiveRow $pub): ?ActiveRow
	{
		return $pub->related('pub_descriptions.pub_id')->order('version DESC')->limit(1)->fetch();
	}

	public function sort(int $limit, int $offset, string $order, string $by, bool $hidden = false): Selection
	{
		static $allowed_order = array(
			"mark",
			"name",
			"location",
			"beerMark",
			"beerPrice",
			"wineMark",
			"winePrice",
			"foodMark",
			"toaletsMark",
			"interierMark",
			"exterierMark",
			"serviceMark",
			"overallMark",
			"foodPrice",
			"updated",
			"inserted"
			);

		static $allowed_by = array( "ASC", "DESC" );

		if (!in_array($order, $allowed_order)) {
			throw new \InvalidArgumentException("sort(): order is not a valid column");
		}

		if (!in_array($by, $allowed_by)) {
			throw new \InvalidArgumentException("sort(): by is not a valid option for sorting [ASC, DESC]");
		}

		// NB: the original Doctrine-era code negated the column and ordered
		// by "-column DESC" for ascending sort, as a workaround for a DQL
		// quirk. There is no material behavior difference to a plain
		// ORDER BY here, so that trick is dropped.
		return $this->by([ 'hidden' => $hidden ], [ $order => $by ])->limit($limit, $offset);
	}


	public function closest(float $lat, float $lng, int $limit, int $offset, bool $ascending = true): Selection
	{
		return $this->table()
			->select(
				$this->baseSelect() . ', (ACOS('
				. 'COS(? * PI() / 180) * COS(? * PI() / 180) * COS(latitude * PI() / 180) * COS(longitude * PI() / 180) + '
				. 'COS(? * PI() / 180) * SIN(? * PI() / 180) * COS(latitude * PI() / 180) * SIN(longitude * PI() / 180) + '
				. 'SIN(? * PI() / 180) * SIN(latitude * PI() / 180)'
				. ') * 6372.795 * 1000) AS distance',
				$lat, $lng, $lat, $lng, $lat
			)
			->order('distance ' . ($ascending ? 'ASC' : 'DESC'))
			->limit($limit, $offset);
	}

	public function time(array $by, string $time): Selection
	{
		$selection = $this->all();

		if ($by) {
			$selection->where($by);
		}

		return $selection->where('inserted >= ?', $time);
	}

	/**
	 * Pubs with their most recent calculated rating date, matching $by.
	 * Raw SQL just picks the ordered pub ids (Selection can't express the
	 * per-pub MAX(date) join); real ActiveRow rows are then fetched via by()
	 * since templates need $pub->ratings relation access.
	 */
	public function lastRated(int $limit, int $offset, array $by = []): array
	{
		$conditions = [];
		$params = [];

		foreach ($by as $column => $value) {
			$conditions [] = "p.$column = ?";
			$params [] = $value;
		}

		$sql = 'SELECT p.pub_id'
			. ' FROM pubs p'
			. ' JOIN (SELECT pub_id, MAX(date) AS max_date FROM ratings WHERE calculated = 1 GROUP BY pub_id) m'
			. ' ON m.pub_id = p.pub_id';

		if ($conditions) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}

		$sql .= ' ORDER BY m.max_date DESC LIMIT ? OFFSET ?';
		$params [] = $limit;
		$params [] = $offset;

		$ids = array_column(iterator_to_array($this->database->query($sql, ...$params)), 'pub_id');

		if (!$ids) {
			return [];
		}

		$rowsByPubId = [];
		foreach ($this->by([ 'pub_id' => $ids ]) as $row) {
			$rowsByPubId [ $row->pub_id ] = $row;
		}

		return array_values(array_filter(array_map(fn ($id) => $rowsByPubId [ $id ] ?? null, $ids)));
	}

	public function search(string $by, array $fields, ?int $limit = null, ?int $offset = null): Selection
	{
		$selection = $this->all()->where('hidden', 0);

		foreach ($fields as $field) {
			$selection->where("$field LIKE ?", '%' . $by . '%');
		}

		foreach ($fields as $field) {
			$selection->order("$field ASC");
		}

		if ($limit !== null || $offset !== null) {
			$selection->limit($limit, $offset);
		}

		return $selection;
	}
}
