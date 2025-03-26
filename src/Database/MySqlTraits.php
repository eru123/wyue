<?php

namespace Wyue\Database;

use PDOStatement;
use Wyue\MySql;

trait MySqlTraits
{
    /**
     * Executes the SQL and return the PDOStatement.
     *
     * @param string $sql    The SQL to execute
     * @param array  $params The parameters to bind
     * @param bool   $dryrun Dryrun mode, returns MySql Object instead of PDOStatement
     *
     * @return MySql|\PDOStatement Returns MySql Object if dryrun is true, else returns PDOStatement
     */
    public function query(string $sql, array $params = [], bool $dryrun = false): MySql|\PDOStatement
    {
        if ($dryrun) {
            return new MySql($sql, $params);
        }

        return (new MySql($sql, $params))->exec();
    }

    /**
     * Executes the SQL and return the number of rows affected.
     *
     * @param string $sql    The SQL to execute
     * @param array  $params The parameters to bind
     * @param bool   $dryrun Dryrun mode, returns MySql Object instead of PDOStatement
     *
     * @return int|MySql Returns MySql Object if dryrun is true, else returns number of rows affected
     */
    public function execute(string $sql, array $params = [], bool $dryrun = false): int|MySql
    {
        $result = $this->query($sql, $params, $dryrun);

        return $result instanceof \PDOStatement ? $result->rowCount() : $result;
    }
}
