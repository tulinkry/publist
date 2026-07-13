<?php

namespace App\Model;

class DescriptionModel extends Repository
{
	protected function tableName(): string
	{
		return 'pub_descriptions';
	}

	protected function primaryKey(): string
	{
		return 'description_id';
	}
}
