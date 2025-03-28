<?php

namespace Wyue;

class MySql
{
    /**
     * @var \PDOStatement
     */
    public $stmt;
    protected $query;

    /**
     * @var \PDO
     */
    protected static $pdo;

    protected static $history = [];
    protected static $columns = [];
    protected static $my_config;
    private $error;
    private $executed = false;

    public function __construct(protected string $sql, protected array $params = [])
    {
        $this->query = $sql;

        if (!empty($params)) {
            $tmp_params = [];
            if (static::is_array($params)) {
                $idxl = count($params);
                foreach ($params as $index => $param) {
                    $idxkey = str_pad($index, strlen($idxl), '0', STR_PAD_LEFT);
                    $param_key = ':p__'.$idxkey;
                    $this->query = preg_replace('/\?/', $param_key, $this->query, 1);
                    $tmp_params[$param_key] = $param;
                }
                $params = &$tmp_params;
            }

            foreach ($params as $key => $param) {
                $key = preg_replace('/^\:/', '', $key);
                if ($param instanceof MySql) {
                    $value = $param;
                } elseif (is_int($param) || is_float($param)) {
                    $value = $param;
                } elseif (is_null($param)) {
                    $value = 'NULL';
                } elseif (is_bool($param)) {
                    $value = $param ? 1 : 0;
                } elseif (is_array($param)) {
                    $value = static::in($param);
                } else {
                    $value = "'".addslashes($param)."'";
                }

                $this->query = str_replace(":{$key}", $value, $this->query);
            }
        }
    }

    public function __toString(): string
    {
        return $this->query ?? $this->sql;
    }

    public function __invoke(): string
    {
        return $this->__toString();
    }

    public static function array_get(array $array, null|array|string $key = null, $default = null)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                $tmp = static::array_get($array, $k, null);
                if (!is_null($tmp)) {
                    return $tmp;
                }
            }

            return $default;
        }

        if (is_null($key) || empty($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        if (
            preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = static::array_get($array, $key);
                if (is_array($value)) {
                    $value = static::array_get($value, $key);
                }

                return $value;
            }, $key) !== $key
        ) {
            $key = preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = static::array_get($array, $key);
                if (is_array($value)) {
                    $value = static::array_get($value, $key);
                }

                return $value;
            }, $key);
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    public static function array_set(array &$array, string $key, $value)
    {
        if (is_null($key) || empty($key)) {
            return $array = $value;
        }

        if (
            preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = static::array_get($array, $key);
                if (is_array($value)) {
                    $value = static::array_get($value, $key);
                }

                return $value;
            }, $key) !== $key
        ) {
            $key = preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = static::array_get($array, $key);
                if (is_array($value)) {
                    $value = static::array_get($value, $key);
                }

                return $value;
            }, $key);
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function is_array($value)
    {
        return array_keys($value) === range(0, count($value) - 1);
    }

    public static function myConfig(array|string $key, $default = null)
    {
        return static::array_get(static::$my_config, $key, $default);
    }

    public static function getMigrationsPath(?string $default = null)
    {
        return static::array_get(static::$my_config, ['migrations_path', 'migration_path', 'migrations', 'migration'], $default);
    }

    public static function getSeedsPath(?string $default = null)
    {
        return static::array_get(static::$my_config, ['seeds_path', 'seed_path', 'seeders_path', 'seeder_path', 'seeder', 'seeds'], $default);
    }

    public static function getMigrationsTable(): string
    {
        return static::array_get(static::$my_config, ['migrations_table', 'migration_table'], 'migrations');
    }

    public static function getModelsPath(): string
    {
        return static::array_get(static::$my_config, ['models_path', 'models'], 'App/Models');
    }

    public static function getModelsNamespace(): string
    {
        return static::array_get(static::$my_config, ['models_namespace'], 'App\Models');
    }

    public static function build(...$args): static
    {
        return new static(...$args);
    }

    public static function raw(self|string $sql, array $params = []): static
    {
        return new static($sql, $params);
    }

    public static function history(): array
    {
        return static::$history;
    }

    public static function columns(array|string $names, $wrapper = '`'): static
    {
        if (empty($names)) {
            return new static('');
        }
        if (!is_array($names)) {
            $names = [$names];
        }

        if (static::is_array($names)) {
            $sql = $wrapper.implode("{$wrapper}, {$wrapper}", array_values($names)).$wrapper;

            return new static($sql);
        }
        $selects = [];
        foreach ($names as $alias => $name) {
            $name = preg_replace('/\./', "{$wrapper}.{$wrapper}", $name);
            if (is_numeric($alias)) {
                $selects[] = "{$wrapper}{$name}{$wrapper}";
            } else {
                $name = preg_match('/\(/', $name) ? new static($name) : "{$wrapper}{$name}{$wrapper}";
                $selects[] = "{$name} AS {$wrapper}{$alias}{$wrapper}";
            }
        }

        $sql = implode(', ', $selects);

        return new static($sql);
    }

    public static function in(array $values): static
    {
        $sql = implode(', ', array_map(function ($value) {
            if ($value instanceof static) {
                return $value;
            }
            if (is_numeric($value)) {
                return $value;
            }
            if (is_null($value)) {
                return 'NULL';
            }
            if (is_bool($value)) {
                return $value ? 1 : 0;
            }

            return "'".addslashes(strval($value))."'";
        }, $values));

        return new static("({$sql})");
    }

    public static function table(self|string $name, ?string $alias = null): static
    {
        $sql = static::select_table($name);
        if ($alias) {
            $sql .= " AS `{$alias}`";
        }

        return new static($sql);
    }

    public static function select(array|self|string $table, array $query)
    {
        $table = is_array($table) ? static::table(...$table) : ($table instanceof static ? $table : static::table($table));
        $cols = static::array_get($query, ['columns', 'column', 'col', 'cols', 'select'], '*');
        if (is_array($cols)) {
            $cols = static::columns($cols);
        }

        $where = static::array_get($query, ['where'], null);
        $where = $where ? static::where($where) : $where;
        $where = $where ? " WHERE {$where}" : '';

        $order = static::array_get($query, ['order', 'orderby', 'sort', 'sortby'], null);
        $order = $order ? static::order($order) : $order;
        $order = $order ? " ORDER BY {$order}" : '';

        $offset = static::array_get($query, ['offset', 'skip'], null);
        $offset = $offset ? static::raw(' OFFSET ?', [intval($offset > 0 ? $offset : 0)]) : '';

        $limit = static::array_get($query, ['limit', 'take'], null);
        $limit = $limit ? static::raw(' LIMIT ?', [intval($limit)]) : '';

        $join = static::array_get($query, ['join', 'joins'], null);
        $join = $join ? ' '.static::join($join) : $join;

        $group = static::array_get($query, ['group', 'groupby'], null);
        $group = $group ? (is_array($group) ? static::columns($group) : $group) : $group;
        $group = $group ? " GROUP BY {$group}" : '';

        $having = static::array_get($query, ['having'], null);
        $having = $having ? static::where($having) : $having;
        $having = $having ? " HAVING {$having}" : '';

        return static::raw("SELECT {$cols} FROM ".$table.$join.$where.$group.$having.$order.$limit.$offset);
    }

    public static function count(array|self|string $table, array $query)
    {
        unset($query['offset'], $query['skip'], $query['limit'], $query['take']);

        return static::raw('SELECT COUNT(*) FROM ('.static::select($table, $query).') as count_table');
    }

    public static function where(array|self|string $data)
    {
        if (is_string($data) || $data instanceof self) {
            return is_string($data) ? static::raw($data) : $data;
        }

        $build = [];
        foreach ($data as $key => $value) {
            $cond = false;
            $adjective = 'AND';

            if ('integer' === gettype($key)) {
                if (is_string($value) || $value instanceof self) {
                    $cond = $data;
                } elseif (is_array($value)) {
                    $cond = static::raw('(?)', [static::where($value)]);
                } else {
                    continue;
                }
            } else {
                if (preg_match('/^(and|or|not)\s+/i', $key, $matches)) {
                    $adjective = strtoupper($matches[1]);
                    $key = preg_replace('/^(and|or|not)\s+/i', '', $key);
                }

                if (is_array($value)) {
                    $sub = [];
                    foreach ($value as $operator => $v) {
                        $adj = 'AND';
                        $cnd = false;
                        if ('integer' === gettype($operator)) {
                            continue;
                        }
                        if (count($sub) > 0 && preg_match('/^(and|or|not)\s+/i', $operator, $matches)) {
                            $adj = strtoupper($matches[1]);
                            $operator = preg_replace('/^(and|or|not)\s+/i', '', $operator);
                        }

                        $operator = strtoupper($operator);
                        $operator = match ($operator) {
                            'GT' => '>',
                            'GTE' => '>=',
                            'LT' => '<',
                            'LTE' => '<=',
                            'E' => '=',
                            'EQ' => '=',
                            'EQUAL' => '=',
                            'EQUALS' => '=',
                            'NEQ' => '!=',
                            'NIN' => 'NOT IN',
                            'IS' => 'IS',
                            'IS NOT' => 'IS NOT',
                            'IS_NOT' => 'IS NOT',
                            default => $operator
                        };

                        $keysql = $key;

                        if (preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)$/i', $key)) {
                            preg_replace_callback('/^([a-z0-9_]+)\.([a-z0-9_]+)$/i', function ($matches) use (&$keysql) {
                                $keysql = "`{$matches[1]}`.`{$matches[2]}`";
                            }, $key);
                        } elseif (preg_match('/^([a-z0-9_]+)$/i', $key)) {
                            preg_replace_callback('/^([a-z0-9_]+)$/i', function ($matches) use (&$keysql) {
                                $keysql = "`{$matches[1]}`";
                            }, $key);
                        } elseif (preg_match('/^([a-z0-9_]+)(\\s+)?\\((.+)\\)$/i', $key)) {
                            preg_replace_callback('/^([a-z0-9_]+)(\\s+)?\\((.+)\\)$/i', function ($matches) use (&$keysql) {
                                $fun = strtoupper($matches[1]);
                                if (preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)$/i', $matches[3])) {
                                    preg_replace_callback('/^([a-z0-9_]+)\.([a-z0-9_]+)$/i', function ($matches) use (&$keysql, &$fun) {
                                        $keysql = "{$fun}(`{$matches[1]}`.`{$matches[2]}`)";
                                    }, $matches[3]);
                                } elseif (preg_match('/^([a-z0-9_]+)$/i', $matches[3])) {
                                    preg_replace_callback('/^([a-z0-9_]+)$/i', function ($matches) use (&$keysql, &$fun) {
                                        $keysql = "{$fun}(`{$matches[1]}`)";
                                    }, $matches[3]);
                                } else {
                                    $keysql = "{$fun}({$matches[3]})";
                                }
                            }, $key);
                        }

                        // preg_replace_callback('/^([a-z0-9_]+)$/i', function ($matches) use (&$keysql) {
                        //     $keysql = "`{$matches[1]}`";
                        // }, $key);

                        // $keysql = preg_replace_callback("/^([a-z0-9_]+)(\s+)?\((.+)\)$/i", function ($matches) use (&$keysql) {

                        // }, $key);

                        if (in_array($operator, ['BETWEEN', 'NOT BETWEEN'])) {
                            $cnd = static::raw("{$keysql} {$operator} ? AND ?", $v);
                        } elseif (in_array($operator, ['IN', 'NOT IN'])) {
                            $cnd = static::raw("{$keysql} {$operator} ?", [is_array($v) ? $v : [$v]]);
                        } elseif (is_null($v) && in_array($operator, ['IS', 'IS NOT'])) {
                            $cnd = static::raw("{$keysql} {$operator} NULL");
                        } elseif (is_null($v)) {
                            $cnd = static::raw("{$keysql} IS NULL");
                        } else {
                            $cnd = static::raw("{$keysql} {$operator} ?", [$v]);
                        }

                        if (count($sub) > 0) {
                            $sub[] = $adj;
                        }

                        $sub[] = $cnd;
                    }

                    if (1 === count($sub)) {
                        $cond = $sub[0];
                    } elseif (count($sub) > 1) {
                        $cond = '('.implode(' ', $sub).')';
                    }
                } else {
                    $cond = static::raw('`'.$key.'` = ?', [$value]);
                }
            }

            if ($cond) {
                if (count($build) > 0) {
                    $build[] = $adjective;
                }
                $build[] = $cond;
            }
        }

        if (count($build) > 0) {
            return static::raw(implode(' ', $build));
        }

        return false;
    }

    public static function order(array|self|string $data)
    {
        if (is_string($data) || $data instanceof self) {
            return $data;
        }

        $build = [];
        foreach ($data as $key => $value) {
            $adjective = 'ASC';
            if ('integer' === gettype($key)) {
                if (is_string($value) || $value instanceof self) {
                    $build[] = $value;
                } elseif (is_array($value)) {
                    $build[] = static::order($value);
                } else {
                    continue;
                }
            } else {
                if (count($build) > 0 && preg_match('/^(asc|desc)\s+/i', $key, $matches)) {
                    $adjective = strtoupper($matches[1]);
                    $key = preg_replace('/^(asc|desc)\s+/i', '', $key);
                }

                $build[] = static::raw('`'.$key.'` '.$adjective);
            }
        }

        if (count($build) > 0) {
            return implode(', ', $build);
        }

        return false;
    }

    public static function join(array|self|string $data)
    {
        if (is_string($data) || $data instanceof self) {
            return $data;
        }

        $build = [];
        foreach ($data as $key => $value) {
            $where = static::where($value);
            if (!$where) {
                continue;
            }
            $build[] = static::raw("{$key} ON {$where}");
        }

        if (count($build) > 0) {
            return ' '.implode(' ', $build);
        }

        return false;
    }

    public static function connect(?array $options = null)
    {
        $driver = static::array_get(static::$my_config, ['driver', 'dbdriver', 'db_driver'], 'mysql');
        $host = static::array_get(static::$my_config, ['host', 'dbhost', 'db_host'], 'localhost');
        $port = static::array_get(static::$my_config, ['port', 'dbport', 'db_port'], 3306);
        $dbname = static::array_get(static::$my_config, ['dbname', 'db_name', 'name', 'db']);
        $username = static::array_get(static::$my_config, ['username', 'user', 'dbuser', 'db_user']);
        $password = static::array_get(static::$my_config, ['password', 'pass', 'dbpass', 'db_pass']);
        $options = is_null($options) ? static::array_get(static::$my_config, ['options', 'pdo_options', 'pdo_opts']) : $options;
        $dsn = "{$driver}:host={$host};port={$port};";
        if ($dbname) {
            $dsn .= "dbname={$dbname};";
        }

        $args = [$dsn, $username];

        if (!empty($password)) {
            $args[] = $password;
        }

        if (!empty($options)) {
            $args[] = $options;
        }

        return new \PDO(...$args);
    }

    /**
     * Set MySql Configuration.
     *
     * @param array{
     *      driver: string,
     *      host: string,
     *      port: int,
     *      username: string,
     *      password: string,
     *      dbname: string,
     *      options: array,
     *      migrations_path: string,
     *      seeds_path: string,
     *      migrations_table: string
     * } $config Configuration array
     */
    public static function set_config(array $config)
    {
        static::$my_config = $config;
    }

    public static function pdo(?array $options = null): ?\PDO
    {
        if (!static::$pdo) {
            static::$pdo = static::connect($options);
        }

        return static::$pdo;
    }

    public function lastError()
    {
        return $this->error;
    }

    public function exec($history = true): false|\PDOStatement
    {
        if ($this->executed) {
            return $this->stmt;
        }

        if ($history) {
            static::$history[] = $this->query;
        }

        static::pdo();
        if ($this->error) {
            return false;
        }

        $this->stmt = static::$pdo->prepare($this->query);
        $success = $this->stmt->execute();
        if (!$success) {
            $this->error = $this->stmt->errorInfo();

            return false;
        }

        $this->executed = true;

        return $this->stmt;
    }

    public static function insert(string $table, array $data)
    {
        $table = static::select_table($table);
        $cols = static::getTableColumns($table);
        $data = array_intersect_key($data, array_flip($cols));

        $keys = array_keys($data);
        $values = array_values($data);
        $sql = "INSERT INTO {$table} (`".implode('`, `', $keys).'`) VALUES ('.implode(', ', array_fill(0, count($values), '?')).')';
        for ($i = 0; $i < count($values); ++$i) {
            if (is_array($values[$i])) {
                $values[$i] = count($values[$i]) ? json_encode($values[$i]) : null;
            }
        }

        return static::raw($sql, $values);
    }

    public static function insert_many(string $table, array $data)
    {
        $table = static::select_table($table);
        $cols = static::getTableColumns($table);
        $keys = array_keys($data[0]);
        $values = [];
        foreach ($data as $d) {
            $d = array_intersect_key($d, array_flip($cols));
            $rowdata = array_values($d);
            for ($i = 0; $i < count($rowdata); ++$i) {
                if (is_array($rowdata[$i])) {
                    $rowdata[$i] = count($rowdata[$i]) ? json_encode($rowdata[$i]) : null;
                }
            }
            $values = array_merge($values, $rowdata);
        }
        $sql = "INSERT INTO {$table} (`".implode('`, `', $keys).'`) VALUES '.implode(', ', array_fill(0, count($data), '('.implode(', ', array_fill(0, count($keys), '?')).')'));

        return static::raw($sql, $values);
    }

    public static function select_table(self|string $table): static
    {
        if ($table instanceof self) {
            return $table;
        }

        if (preg_match('/^([a-z0-9_]+)$/i', $table)) {
            return static::raw("`{$table}`");
        }
        if (preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)$/i', $table, $matches)) {
            return static::raw("`{$matches[1]}`.`{$matches[2]}`");
        }
        if (preg_match('/^([a-z0-9_]+)(\\s+)?\\((.+)\\)$/i', $table, $matches)) {
            return static::raw("{$matches[1]}({$matches[3]})");
        }

        return static::raw($table);
    }

    public static function update(self|string $table, array $data, array|self|string $where = '1')
    {
        $table = static::select_table($table);
        $cols = static::getTableColumns($table);
        $data = array_intersect_key($data, array_flip($cols));
        $keys = array_keys($data);
        $values = array_values($data);
        $where = static::where($where);
        $set = implode(',', array_map(fn ($key) => "`{$key}` = ?", $keys));

        $sql = "UPDATE {$table} SET {$set}".($where ? " WHERE {$where}" : '');

        for ($i = 0; $i < count($values); ++$i) {
            if (is_array($values[$i])) {
                $values[$i] = count($values[$i]) ? json_encode($values[$i]) : null;
            }
        }

        return static::raw($sql, $values);
    }

    public static function delete(string $table, array|self|string $where = '1')
    {
        $table = static::select_table($table);
        $where = static::where($where);
        $sql = "DELETE FROM {$table}".($where ? " WHERE {$where}" : '');

        return static::raw($sql);
    }

    public function fetch(...$args)
    {
        $this->exec();

        return $this->stmt->fetch(...$args);
    }

    public function fetchAll(...$args)
    {
        $this->exec();

        return $this->stmt->fetchAll(...$args);
    }

    public function fetchColumn(...$args)
    {
        $this->exec();

        return $this->stmt->fetchColumn(...$args);
    }

    public function fetchObject(...$args)
    {
        $this->exec();

        return $this->stmt->fetchObject(...$args);
    }

    public function fetchAllObject(...$args)
    {
        $this->exec();

        return $this->stmt->fetchAll(\PDO::FETCH_CLASS, ...$args);
    }

    public function affected()
    {
        $this->exec();

        return $this->stmt->rowCount();
    }

    public static function id(?string $name = null)
    {
        static::pdo();

        return static::$pdo->lastInsertId($name);
    }

    public static function beginTransaction()
    {
        static::pdo();

        return static::$pdo->beginTransaction();
    }

    public static function commit()
    {
        static::pdo();

        return MySql::pdo()->inTransaction() && static::$pdo->commit();
    }

    public static function rollback()
    {
        static::pdo();

        return MySql::pdo()->inTransaction() && static::$pdo->rollBack();
    }

    public function close()
    {
        static::$pdo = null;
    }

    public static function extract_dbntable(self|string $table)
    {
        $dbname = Venv::get('DB_NAME', 'knights');
        if (preg_match('/^`([a-z0-9_]+)`$/i', $table, $matches)) {
            $table = $matches[1];
        } elseif (preg_match('/^`([a-z0-9_]+)`\.`([a-z0-9_]+)`$/i', $table, $matches)) {
            $dbname = $matches[1];
            $table = $matches[2];
        } elseif (preg_match('/^`([a-z0-9_]+)`\.`([a-z0-9_]+)`\s+as\s+`([a-z0-9_]+)`$/i', $table, $matches)) {
            $dbname = $matches[1];
            $table = $matches[2];
        } elseif (preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)$/i', $table, $matches)) {
            $dbname = $matches[1];
            $table = $matches[2];
        } elseif (preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)\s+as\s+([a-z0-9_]+)$/i', $table, $matches)) {
            $dbname = $matches[1];
            $table = $matches[2];
        }

        return [$dbname, $table];
    }

    public static function tableColumns(self|string $table)
    {
        [$dbname, $table] = static::extract_dbntable($table);
        $sql = 'SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION';

        return static::raw($sql, [$dbname, $table]);
    }

    public static function getTableColumns(null|self|string $table = null)
    {
        if (is_null($table)) {
            return static::$columns;
        }

        [$dbname, $table] = static::extract_dbntable($table);
        if (isset(static::$columns[$table])) {
            return static::$columns[$table];
        }
        $sql = 'SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION';
        $stmt = static::raw($sql, [$dbname, $table])->exec();
        static::$columns[$table] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return static::$columns[$table];
    }
}
