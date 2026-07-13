<?php

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

/**
 * Wraps the rating_beer join table (composite primary key beer_id+rating_id).
 * Does not fit the single-primaryKey Repository shape, so it is its own
 * small class wrapping Explorer directly.
 */
class BeerRatingModel
{
	public function __construct(
		private readonly Explorer $database,
	) {
	}

	private function table(): Selection
	{
		return $this->database->table('rating_beer');
	}

	public function find(int $ratingId, int $beerId): ?ActiveRow
	{
		return $this->table()
			->where('rating_id', $ratingId)
			->where('beer_id', $beerId)
			->fetch();
	}

	public function forRating(int $ratingId): Selection
	{
		return $this->table()->where('rating_id', $ratingId);
	}

	/**
	 * Inserts a new rating_beer row or updates the existing one identified
	 * by the composite key.
	 */
	public function upsert(int $ratingId, int $beerId, array $data): ActiveRow
	{
		$row = $this->find($ratingId, $beerId);

		if ($row !== null) {
			$row->update($data);
			return $row;
		}

		return $this->table()->insert($data + [
			'rating_id' => $ratingId,
			'beer_id' => $beerId,
		]);
	}

	public function delete(int $ratingId, int $beerId): bool
	{
		$row = $this->find($ratingId, $beerId);

		return $row !== null && $row->delete() > 0;
	}
}
