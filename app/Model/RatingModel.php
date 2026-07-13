<?php

namespace App\Model;

use Nette\Database\Table\Selection;

class RatingModel extends Repository
{
	public const RATING_CLOSURE = 86400; //day
	public const RATING_INTERVAL = 7776000; //3 * 30 * 24 * 60 * 60 three months

	protected function tableName(): string
	{
		return 'ratings';
	}

	protected function primaryKey(): string
	{
		return 'rating_id';
	}

	/**
	 * Formats a duration in seconds (typically RATING_CLOSURE/RATING_INTERVAL,
	 * or time remaining until one of them elapses) as a single "N unit" string
	 * in the largest whole unit that fits, e.g. "3 dní", "2 hodin".
	 * @param array{year: string, month: string, day: string, hour: string, minute: string, second: string} $names
	 */
	public static function formatDuration($num, array $names): string
	{
		switch (true) {
			case $num > (365 * 24 * 60 * 60):
				return floor($num / (365 * 24 * 60 * 60)) . " " . $names['year'];
			case $num > (30 * 24 * 60 * 60):
				return floor($num / (30 * 24 * 60 * 60)) . " " . $names['month'];
			case $num > (24 * 60 * 60):
				return floor($num / (24 * 60 * 60)) . " " . $names['day'];
			case $num > (60 * 60):
				return floor($num / (60 * 60)) . " " . $names['hour'];
			case $num > 60:
				return floor($num / (60)) . " " . $names['minute'];
			default:
				return floor($num) . " " . $names['second'];
		}
	}

	// Templates still use the old Doctrine entity's camelCase property names.
	// beerCriteria wasn't a real column (it lived on the rating_beer join
	// table) - PubModel::singleBeerRating() computes it as
	// AVG(beer_criteria) * BEER_WEIGHT, and BEER_WEIGHT is 1, so a plain
	// AVG() subquery reproduces it exactly (MySQL's AVG already ignores NULLs).
	protected function columnAliases(): array
	{
		return [
			'wineCriteria' => 'wine_criteria',
			'winePrice' => 'wine_price',
			'foodCriteria' => 'food_criteria',
			'foodPriceCriteria' => 'food_price_criteria',
			'toaletsCriteria' => 'toalets_criteria',
			'serviceCriteria' => 'service_criteria',
			'overallCriteria' => 'overall_criteria',
			'interierCriteria' => 'interier_criteria',
			'exterierCriteria' => 'exterier_criteria',
			'beerCriteria' => '(SELECT AVG(beer_criteria) FROM rating_beer WHERE rating_id = ratings.rating_id)',
		];
	}

	/**
	 * Ratings not yet calculated whose date is older than RATING_CLOSURE.
	 * Ported from the former DQL query, dropping the unused pub join
	 * (the original DQL never referenced the joined alias).
	 */
	public function last(): Selection
	{
		return $this->all()
			->where('calculated', false)
			->where('date < ?', date('Y-m-d H:i:s', time() - self::RATING_CLOSURE))
			->order('date DESC');
	}
}
