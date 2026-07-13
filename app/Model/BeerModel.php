<?php

namespace App\Model;

class BeerModel extends Repository
{
	protected function tableName(): string
	{
		return 'beers';
	}

	protected function primaryKey(): string
	{
		return 'beer_id';
	}
}
