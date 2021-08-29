<?php
declare(strict_types=1);

namespace App\Collection;


abstract class AbstractCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $_values = [];

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value): void
    {
        $this->_values[$key] = $value;
    }

    /**
     * @param mixed $key
     * @return mixed|null
     */
    public function get($key)
    {
        return $this->has($key) ?
            $this->_values[$key] :
            null;
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->_values);
    }

    /**
     * @param mixed $key
     */
    public function remove($key): void
    {
        if ($this->has($key))
        {
            unset($this->_values[$key]);
        }
    }

    public function __construct(array $data = [])
    {
        $this->_values = $data;
    }

    #region ArrayAccess implementation

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
    #endregion

    #region Default PHP magic methods
    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value): void
    {
        $this->set($name, $value);
    }

    /**
     * @param mixed $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param mixed $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return $this->has($name);
    }

    /**
     * @param mixed $name
     */
    public function __unset($name): void
    {
        $this->remove($name);
    }
    #endregion

    #region Countable implementation
    public function count(): int
    {
        return count($this->_values);
    }
    #endregion

    #region IteratorAggregate implementation
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->_values);
    }
    #endregion
}
