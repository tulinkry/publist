<?php

namespace Model;

use Tulinkry\Model\Doctrine\BaseModel;
use Entity;

class RatingModel extends BaseModel
{
	const RATING_CLOSURE = 86400; //day
	const RATING_INTERVAL = 7776000; //3 * 30 * 24 * 60 * 60 three months

	public function last ()
	{
		$cmd = "SELECT r FROM Entity\Rating r";
		$cmd .= " JOIN Entity\Pub p WITH r . pub = p";
		$cmd .= " WHERE r.calculated = ?1 AND r.date < ?2";
		$cmd .= " ORDER BY r.date DESC";

		$query = $this -> em -> createQuery (
			$cmd
		);

		$query -> setParameter ( 1, false );
		$query -> setParameter ( 2, date ( 'Y-m-d H:i:s', time() - self::RATING_CLOSURE ) );


		return $query -> getResult ();
	}
}