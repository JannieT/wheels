<?php

/**
 * @author      Jannie Theunissen <jannie@onesheep.org>
 * @copyright   Copyright (c), 2020 Jannie Theunissen
 * @license     MIT public license
 */

namespace Skateboard\Wheels;

class Model
{
    /**
     * the name of the underlying table.
     * @var string
     */
    protected $tableName;

    /**
     * Name of the table's primary key field.
     * @var string
     */
    protected $pk;

    /**
     * All the table's column names without the primary key.
     * @var array
     */
    protected $columnNames;

    /**
     * @var PDO
     */
    protected static $db;

    /**
     * @param array $members optional field=>value members to populate the model
     */
    public function __construct($members = [])
    {
        $this->tableName = static::TABLE;
        $this->columnNames = array_map(function ($field) {
            return $field;
        }, static::COLUMNS);
        $this->pk = array_shift($this->columnNames);
        $this->populate($members);
    }

    public static function use(\PDO $db)
    {
        self::$db = $db;
    }

    /**
     * Populate field members via an array.
     * If members do not exist, they are created
     * Other members not passed in are not cleared
     * This method does not access the database.
     *
     * @param array $data (member=>value) data to use to populate object members
     */
    public function populate($data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Generic method to persist model properties to the database
     * If the model already has an id, it updates the row
     * otherwise it inserts a row.
     *
     * @throws \LogicException if model has no data
     * @throws \RuntimeException if the query was unsuccessful
     * @return void
     */
    public function save()
    {
        if ($this->hasId()) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    /**
     * Used to create a new row in the table and set the object id
     * The values of any hydrated object members will also be inserted into the table row
     * There has to be at least one hydrated object member.
     *
     * @throws LogicException if the Model already has an id or if it has no set members
     * @throws RuntimeException if the insert was unsuccessful
     * @return void
     */
    private function insert()
    {
        $data = $this->attributes();
        if ($this->hasId() || empty($data)) {
            throw new \LogicException('Model should have some members and no id');
        }
        $fields = [];
        $values = [];
        $placeholders = [];

        foreach ($data as $name => $value) {
            $fields[] = $name;
            $values[] = $value;
            $placeholders[] = '?';
        }
        $fieldClause = implode('`, `', $fields);
        $valueClause = implode(', ', $placeholders);
        $sql = "INSERT INTO {$this->tableName} (`$fieldClause`) VALUES ($valueClause)";
        $cmd = self::$db->prepare($sql);

        // a PDOException might be generated if this goes wrong
        if ($cmd->execute($values) == false) {
            throw new \RuntimeException('Unsuccessful insert: '.$sql);
        }

        $this->{$this->pk} = self::$db->lastInsertId();
    }

    /**
     * Internal method called by save()
     * Saves an array of data to the database
     * Only objects with a valid id can be updated.
     * @param array $fields object members to persist to the database,
     *          if null then update all loaded attributes
     * @throws LogicException if the model doesn't have any data or an id
     * @throws RuntimeException if the update was unsuccessful
     * @return void
     */
    private function update($fields = null)
    {
        $data = $this->attributes($fields);
        if (! $this->hasId() || empty($data)) {
            throw new \LogicException('Model should have some members and an id');
        }

        $updates = [];
        $values = [];
        foreach ($data as $name => $value) {
            $updates[] = "t.{$name}=?";
            $values[] = $value;
        }
        $sql = sprintf(
            'UPDATE %s t SET %s WHERE t.%s=?',
            $this->tableName,
            implode(', ', $updates),
            $this->pk
        );
        $cmd = self::$db->prepare($sql);
        $values[] = $this->{$this->pk};

        // a PDOException might be generated if this goes wrong
        if ($cmd->execute($values) == false) {
            throw new \RuntimeException('Unsuccessful update: '.$sql);
        }
    }

    /**
     * Uses the id to delete the object from the database
     * All object members are set to null.
     *
     * @throws LogicException if the object doesn't have a primary key value
     * @throws RuntimeException if the object wasn't deleted
     */
    public function delete()
    {
        if ($this->hasId() == false) {
            throw new \LogicException('Trying to delete an uninitialised object');
        }

        $cmd = self::$db->prepare("DELETE FROM {$this->tableName} WHERE {$this->pk}=?");

        $id = $this->pk;
        if ($cmd->execute([$this->$id])) {
            $this->$id = null;
            foreach ($this->columnNames as $attribute) {
                if (property_exists($this, $attribute)) { // As opposed to isset(), property_exists() returns TRUE even if the property has the value NULL.
                    $this->$attribute = null;
                }
            }

            return;
        }
        throw new \RuntimeException();
    }

    /**
     * Hydrates an object from a table using a primary key to look up the values.
     *
     * Current populated members are overwritten
     * @param mixed $id the primary key value of the row to load into the object
     * @throws InvalidArgumentException if the object wasn't deleted
     */
    public function load($id)
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE {$this->pk} = ?";
        $cmd = self::$db->prepare($sql);
        if ($cmd->execute([$id]) && ($row = $cmd->fetch(\PDO::FETCH_ASSOC))) {
            $this->populate($row);

            return;
        }

        throw new \InvalidArgumentException();
    }

    /**
     * To check if this is a new object or one coming from the database.
     * @return bool true if the object has a non-empty primary key
     */
    public function hasId()
    {
        $id = $this->pk;

        return isset($this->$id);
    }

    /**
     * Converts the hydrated data members of the object to an array.
     *
     * @param array $fields a filter to limit the scope, otherwise it uses the model's columnNames
     * @return array of methodName=>value pairs
     *
     * TODO: might try using http://www.php.net/manual/en/function.get-object-vars.php
     */
    public function attributes($fields = null)
    {
        $scope = is_null($fields) ? $this->columnNames : $fields;
        $data = [];
        foreach ($scope as $attribute) {
            if (property_exists($this, $attribute)) { // As opposed to isset(), property_exists() returns TRUE even if the property has the value NULL.
                $data[$attribute] = $this->$attribute;
            }
        }

        return $data;
    }
}
