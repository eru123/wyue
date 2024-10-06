<?php

namespace Wyue\Database;

use Wyue\MySql;
use Exception;
use Generator;
use PDO;

abstract class AbstractModel
{

    use MySqlTraits;

    /**
     * @var string The table name for this model
     */
    protected $table = '';

    /**
     * @var null|array The fillable fields for this model, keep it null to allow all fields
     */
    protected $fillable = null;

    /**
     * @var null|array The hidden fields for this model, keep it null to allow all fields
     */
    protected $hidden = null;

    /**
     * @var null|string|int The primary key for this model, if using any
     */
    protected $primaryKey = null;

    /**
     * @var array The data and default values for this model
     */
    protected $data = [];


    public function __get(string $name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    public function __set(string $name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param null|array $data The data and default values for this model, represented as an array and the current row
     * @param null|string $table The table name for this model
     * @param null|string $primaryKey The primary key for this model
     */
    public function __construct(null|array $data = null, null|string $table = null, null|string $primaryKey = null)
    {
        if (is_array($data)) {
            $this->data = $data;
        }

        if ($table) {
            $this->table = $table;
        }

        if ($primaryKey) {
            $this->primaryKey = $primaryKey;
        }
    }

    /**
     * Get many data from database using Generator to yield the data
     * @param array $query The query to get data from
     * @param bool $history Whether to save the query history
     * @return Generator<static>
     */
    public function select(string|array|MySql $query = [], $history = false): Generator
    {
        $stmt = MySql::select($this->table, $query)->exec($history);
        while ($result = $stmt?->fetch(PDO::FETCH_ASSOC)) {
            yield new static($result, $this->table, $this->primaryKey);
        }
    }

    /**
     * Get one data from database
     * @param array $query The query to get data from
     * @param bool $history Whether to save the query history
     * @return false|null|static
     * @throws Exception
     */
    function find(string|array|MySql $query = [], $history = false): false|null|static
    {
        $result = MySql::select($this->table, $query)->exec($history)?->fetch(PDO::FETCH_ASSOC);
        return is_array($result) ? new static($result, $this->table, $this->primaryKey) : $result;
    }

    /**
     * Get many data from database, use this if you want to get all data at once, instead of using Generator
     * @param array $query The query to get data from
     * @param bool $history Whether to save the query history
     * @return array<static>
     * @throws Exception
     */
    public function findMany(string|array|MySql $query = [], $history = false): array
    {
        return array_map(fn($row) => new static($row, $this->table, $this->primaryKey), MySql::select($this->table, $query)->exec($history)?->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * Insert single row data into database
     * @param array $data The data to insert
     * @return false|string The id of the inserted data
     * @throws Exception
     */
    public function insert(null|array $data = null): false|string
    {
        if (is_null($data)) {
            $data = $this->data;
        }

        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        if (!!MySql::insert($this->table, $data)?->exec()?->rowCount()) {
            $id = MySql::id();
            if (!$this->primaryKey) {
                $this->data[$this->primaryKey] = $id;
            }
            return $id;
        }

        return false;
    }

    /**
     * Insert multiple rows data into database
     * @param array $data The data to insert
     * @return int The number of rows inserted
     * @throws Exception
     */
    public function insertMany(array $data): int
    {
        foreach ($data as &$row) {
            if (!empty($this->fillable)) {
                $row = array_intersect_key($row, array_flip($this->fillable));
            }
        }

        return intval(MySql::insert_many($this->table, $data)?->exec()?->rowCount());
    }

    /**
     * Update data in database
     * @param array $data The data to update
     * @param MySql|string|array $where The where clause
     * @return int The number of rows updated
     * @throws Exception
     */
    public function update(null|array $data = null, null|MySql|string|array $where = null): int
    {
        if (is_null($data) && is_null($where) && !empty($this->primaryKey) && isset($this->data[$this->primaryKey])) {
            $data = $this->data;
            $where = [$this->primaryKey => $this->data[$this->primaryKey]];
        }

        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        return intval(MySql::update($this->table, $data, $where)?->exec()?->rowCount());
    }

    /**
     * Delete data in database
     * @param MySql|string|array $where The where clause
     * @return int The number of rows deleted
     * @throws Exception
     */
    public function delete(null|MySql|string|array $where = null): int
    {
        if (is_null($where) && !empty($this->primaryKey) && isset($this->data[$this->primaryKey])) {
            $where = [$this->primaryKey => $this->data[$this->primaryKey]];
        }
        return intval(MySql::delete($this->table, $where)?->exec()?->rowCount());
    }

    /**
     * Check if column exists in table
     * @param string $column The column to check
     * @return bool
     * @throws Exception
     */
    public function hasColumn(string $column): bool
    {
        return MySql::raw("SHOW COLUMNS FROM {$this->table} LIKE '{$column}'")->exec()?->rowCount() > 0;
    }

    /**
     * Check if table exists
     * @return bool
     * @throws Exception
     */
    public function exists(): bool
    {
        return MySql::raw("SHOW TABLES LIKE '{$this->table}'")->exec()?->rowCount() > 0;
    }

    /**
     * Convert to array
     * @return array
     */
    public function __toArray(): array
    {
        return $this->data;
    }
}
