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
     * @var array The fillable fields for this model, keep it null to allow all fields
     */
    protected $fillable = [];

    /**
     * @var array The hidden fields for this model, keep it null to allow all fields
     */
    protected $hidden = [];

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
    public function __construct(null|array $data = null, null|string $table = null)
    {
        if (is_array($data)) {
            $this->data = $data;
        }

        if ($table) {
            $this->table = $table;
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
            yield new static($result, $this->table);
        }
    }

    /**
     * Get one data from database
     * @param array $query The query to get data from
     * @param bool $history Whether to save the query history
     * @return false|null|static
     * @throws Exception
     */
    function find(array $query = [], $history = false): false|null|static
    {
        $result = MySql::select($this->table, $query)->exec($history)?->fetch(PDO::FETCH_ASSOC);
        return is_array($result) ? new static($result, $this->table) : $result;
    }

    /**
     * Get many data from database, use this if you want to get all data at once, instead of using Generator
     * @param array $query The query to get data from
     * @param bool $history Whether to save the query history
     * @return array<static>
     * @throws Exception
     */
    public function findMany(array $query = [], $history = false): array
    {
        return array_map(fn($row) => new static($row, $this->table), MySql::select($this->table, $query)->exec($history)?->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * Insert single row data into database
     * @param array $data The data to insert
     * @param bool $history Whether to save the query history
     * @return false|string The id of the inserted data
     * @throws Exception
     */
    public function insert(null|array $data = null, bool $history = false): false|string
    {
        if (is_null($data)) {
            $data = $this->data;
        }

        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        $data = $this->beforeInsert($data);
        $data = $this->beforeInsertInternal($data);

        if (!!MySql::insert($this->table, $data)?->exec($history)?->rowCount()) {
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
     * @param bool $history Whether to save the query history
     * @return int The number of rows inserted
     * @throws Exception
     */
    public function insertMany(array $data, bool $history = false): int
    {
        foreach ($data as &$row) {
            if (!empty($this->fillable)) {
                $row = array_intersect_key($row, array_flip($this->fillable));
                $row = $this->beforeInsert($row);
                $row = $this->beforeInsertInternal($row);
            }
        }

        return intval(MySql::insert_many($this->table, $data)?->exec($history)?->rowCount());
    }

    /**
     * Update data in database
     * @param array $data The data to update
     * @param MySql|string|array $where The where clause
     * @param bool $history Whether to save the query history
     * @return int The number of rows updated
     * @throws Exception
     */
    public function update(null|array $data = null, null|MySql|string|array $where = null, bool $history = false): int
    {
        if (is_null($data) && is_null($where) && !empty($this->primaryKey) && isset($this->data[$this->primaryKey])) {
            $data = $this->data;
            $where = [$this->primaryKey => $this->data[$this->primaryKey]];
        }

        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        $data = $this->beforeUpdate($data);
        $data = $this->beforeUpdateInternal($data);
        return intval(MySql::update($this->table, $data, $where)?->exec($history)?->rowCount());
    }

    /**
     * Delete data in database
     * @param MySql|string|array $where The where clause
     * @param bool $history Whether to save the query history
     * @return int The number of rows deleted
     * @throws Exception
     */
    public function delete(null|MySql|string|array $where = null, bool $history = false): int
    {
        if (is_null($where) && !empty($this->primaryKey) && isset($this->data[$this->primaryKey])) {
            $where = [$this->primaryKey => $this->data[$this->primaryKey]];
        }
        return intval(MySql::delete($this->table, $where)?->exec($history)?->rowCount());
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
    public function __toArray()
    {
        return array_diff_key($this->data, array_flip($this->hidden ?? []));
    }

    /**
     * Parse data before insert
     * @param array $data
     * @return array
     */
    public function beforeInsert(array $data): array
    {
        return $data;
    }

    /**
     * Parse data before insert (for framework use)
     * @param array $data
     * @return array
     */
    public function beforeInsertInternal(array $data): array
    {
        // TODO: Implement beforeInsertInternal() method.
        return $data;
    }

    /**
     * Parse data before update
     * @param array $data
     * @return array
     */
    public function beforeUpdate(array $data): array
    {
        return $data;
    }

    /**
     * Parse data before update (for framework use)
     * @param array $data
     * @return array
     */
    public function beforeUpdateInternal(array $data): array
    {
        // TODO: Implement beforeUpdateInternal() method.
        return $data;
    }
}
