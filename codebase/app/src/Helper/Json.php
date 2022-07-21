<?php

namespace App\Helper;

use App\Exceptions\ExceptionWithStatusCode;
use Exception;
use JsonException;
use Traversable;

class Json implements \ArrayAccess, \IteratorAggregate
{
    private array $json;

    /**
     * Json constructor.
     * @param string $json
     * @throws ExceptionWithStatusCode
     */
    public function __construct(string $json) {
        try {
            $this->json = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ExceptionWithStatusCode("Json malformatted", 400);
        }
    }

    /**
     * @param mixed $key
     * @return mixed
     * @throws ExceptionWithStatusCode
     */
    public function require($key) {
        if (!isset($this->json[$key])) {
            throw new ExceptionWithStatusCode("Key $key not found or null", 400);
        }
        return $this->json[$key];
    }

    /**
     * @param mixed $key
     * @return mixed
     * @throws ExceptionWithStatusCode
     */
    public function requireKey($key) {
        if (!array_key_exists($key, $this->json)) {
            throw new ExceptionWithStatusCode("Key $key not found in JSON payload", 400);
        }
        return $this->json[$key];
    }

    /**
     * @param mixed $key
     * @return mixed
     * @throws ExceptionWithStatusCode
     */
    public function requireNotEmpty($key) {
        $val = $this->require($key);
        if ($val === null || $val === '') {
            throw new ExceptionWithStatusCode("Key $key was set but was null or empty", 400);
        }
        return $val;
    }

    /**
     * @param mixed $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null) {
        return $this->offsetGet($key) ?? $default;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->json);
    }

    public function offsetGet($offset)
    {
        return $this->json[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->json[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->json[$offset]);
    }

    public function pop($offset, $default = null)
    {
        $value = $this->offsetGet($offset);
        unset($this->json[$offset]);
        return $value ?? $default;
    }

    public function popRequired($offset)
    {
        $value = $this->require($offset);
        unset($this->json[$offset]);
        return $value;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->json);
    }
}