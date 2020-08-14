<?php

/**
 * @author      Jannie Theunissen <jannie@onesheep.org>
 * @copyright   Copyright (c), 2020 Jannie Theunissen
 * @license     MIT public license
 */

namespace Skateboard\Wheels;

trait Database
{
    private $dbh;

    protected function db()
    {
        if ($this->dbh == null) {
            $connection = getenv('DB_CONNECTION');
            $host = getenv('DB_HOST');
            $port = getenv('DB_PORT');
            $database = getenv('DB_DATABASE');
            $username = getenv('DB_USERNAME');
            $password = getenv('DB_PASSWORD');
            $options = [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ];

            $dsn = "$connection:host=$host;port=$port;dbname=$database";
            $this->dbh = new \PDO($dsn, $username, $password, $options);
        }

        return $this->dbh;
    }

    /**
     * Formats the current time stamp in MySQL DateTime format.
     *
     * @return string
     */
    public function now()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Execute any select query.
     *
     * @throws InvalidArgumentException when the number of query parameters don't match up
     *
     * @param string $sql
     * @param array $params optional. they are all strings, so can't be used for "limit ?" clause for example
     * @return array of rows or empty array if no rows are returned
     */
    public function query(string $sql, array $params = null)
    {
        $cmd = $this->dbh->prepare($sql);
        try {
            if ($cmd->execute($params) && ($rows = $cmd->fetchAll(\PDO::FETCH_ASSOC))) {
                return $rows;
            } else {
                return [];
            }
        } catch (Exception $ex) {
            throw new \InvalidArgumentException();
        }
    }
}
