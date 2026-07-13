<?php

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

/**
 * Base repository built on top of Nette Database Explorer.
 *
 * Replaces the former Doctrine-backed Tulinkry\Model\Doctrine\BaseModel.
 * There is no entity manager, no lazy loading, no unit-of-work - every
 * method here operates directly on the underlying table via SQL and
 * returns Nette\Database\Table\Selection / ActiveRow instances.
 *
 * Every row returned by all()/by()/limit()/item() carries a virtual "id"
 * column aliased from the real primary key (e.g. "pub_id AS id") so
 * existing "->id" access across the app keeps working unchanged.
 */
abstract class Repository
{
	public function __construct(
		protected readonly Explorer $database,
	) {
	}

	/**
	 * Real table name, e.g. "pubs".
	 */
	abstract protected function tableName(): string;

	/**
	 * Real primary key column name, e.g. "pub_id".
	 */
	abstract protected function primaryKey(): string;

	/**
	 * Extra column aliases beyond "id" (e.g. camelCase names some templates
	 * still expect), as [alias => real column name].
	 */
	protected function columnAliases(): array
	{
		return [];
	}

	/**
	 * Raw table selection - real columns, no aliases.
	 */
	protected function table(): Selection
	{
		return $this->database->table($this->tableName());
	}

	/**
	 * Fetch a single row by its real primary key.
	 */
	public function item($id): ?ActiveRow
	{
		return $this->all()->where($this->primaryKey(), $id)->fetch();
	}

	/**
	 * "*, pub_id AS id, whole_name AS wholeName, ..." - the id + columnAliases()
	 * expression, for subclasses building their own custom select() (e.g. a
	 * raw distance calculation) that still need the same aliases applied.
	 */
	protected function baseSelect(): string
	{
		$select = '*, ' . $this->primaryKey() . ' AS id';

		foreach ($this->columnAliases() as $alias => $column) {
			$select .= ", $column AS $alias";
		}

		return $select;
	}

	/**
	 * All rows, with the aliased "id" column (and any columnAliases()).
	 */
	public function all(): Selection
	{
		return $this->table()->select($this->baseSelect());
	}

	/**
	 * All rows matching $by (real column names) and ordered by $order
	 * (real column names => "ASC"/"DESC").
	 */
	public function by(array $by = [], array $order = []): Selection
	{
		$selection = $this->all();

		if ($by) {
			$selection->where($by);
		}

		foreach ($order as $column => $direction) {
			$selection->order("$column $direction");
		}

		return $selection;
	}

	/**
	 * Same as by(), with LIMIT/OFFSET applied.
	 */
	public function limit(int $limit, int $offset, array $by = [], array $order = []): Selection
	{
		return $this->by($by, $order)->limit($limit, $offset);
	}

	/**
	 * Number of rows matching $by (real column names).
	 */
	public function count(array $by = []): int
	{
		$selection = $this->table();

		if ($by) {
			$selection->where($by);
		}

		return $selection->count('*');
	}

	/**
	 * Inserts $data and returns the freshly inserted row with the aliased
	 * "id" column.
	 */
	public function insert(array $data): ActiveRow
	{
		$row = $this->table()->insert($data);

		if (!$row instanceof ActiveRow) {
			throw new \RuntimeException(sprintf('%s::insert(): insert into "%s" did not return a row.', static::class, $this->tableName()));
		}

		return $this->item($row->{$this->primaryKey()}) ?? $row;
	}
}
