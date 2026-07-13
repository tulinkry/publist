<?php

namespace App\Model;

use Nette\Database\Table\ActiveRow;

class UserModel extends Repository
{
	protected function tableName(): string
	{
		return 'users';
	}

	protected function primaryKey(): string
	{
		return 'user_id';
	}

	/**
	 * Port of the former Entity\User::hasRated(). Static (like PubModel's
	 * calculation helpers) so templates can call it directly.
	 */
	public static function hasRated(ActiveRow $user, int $pubId): bool
	{
		return $user->related('ratings.user_id')->where('pub_id', $pubId)->count() > 0;
	}
}
