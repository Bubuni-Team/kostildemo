<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 05.07.2021
 * Time: 20:21
 * Made with <3 by West from Bubuni Team
 */

namespace App;


use App;
use ArrayAccess;
use PDO;
use PDOStatement;

/**
 * @template-implements ArrayAccess<string,mixed>
 */
class DataRegistry implements ArrayAccess
{
    /** @var App */
    protected $app;

    /** @var PDO */
    protected $db;

    /** @var array */
    protected $registry = [];

    /** @var PDOStatement */
    protected $registryInsertStatement;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->db = $db = $app->db();

        $dr = $db->query("SELECT * FROM `data_registry`", PDO::FETCH_ASSOC)->fetchAll();
        foreach ($dr as $data)
        {
            $this->registry[$data['data_key']] = json_decode($data['data_value'], true);
        }

        $this->registryInsertStatement = $db->prepare(
            "INSERT IGNORE INTO `data_registry` (data_key, data_value) VALUES (:key, :value) 
                    ON DUPLICATE KEY UPDATE `data_value` = :value"
        );
    }

    public function offsetExists($offset): bool
    {
        return isset($this->registry[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->registry[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->registry[$offset] = $value;

        $stmt = $this->registryInsertStatement;
        $stmt->bindValue(':key', $offset);
        $stmt->bindValue(':value', @json_encode($value));

        $stmt->execute();
    }

    public function offsetUnset($offset): void
    {
        unset($this->registry[$offset]);
    }

    protected function app(): App
    {
        return $this->app;
    }
}