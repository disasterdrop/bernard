<?php

declare(strict_types=1);

namespace Bernard\Message;

use Bernard\Message;

/**
 * Simple message that gets you started.
 * It has a name and an array of arguments.
 * It does not enforce any types or properties so be careful on relying them
 * being there.
 */
final class PlainMessage implements Message, \ArrayAccess
{
    private $name;
    private $arguments;

    /**
     * @param string $name
     */
    public function __construct($name, array $arguments = [])
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->arguments;
    }

    /**
     * Returns the argument if found or null.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name): mixed
    {
        return $this->has($name) ? $this->arguments[$name] : null;
    }

    /**
     * Checks whether the arguments contain the given key.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name): bool
    {
        return \array_key_exists($name, $this->arguments);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetSet($offset, $value): void
    {
        throw new \LogicException('Message is immutable');
    }

    public function offsetUnset($offset): void
    {
        throw new \LogicException('Message is immutable');
    }

    public function __get($property): mixed
    {
        return $this->get($property);
    }

    public function __isset($property): bool
    {
        return $this->has($property);
    }

    public function __set($property, $value): void
    {
        throw new \LogicException('Message is immutable');
    }

    public function __unset($property): void
    {
        throw new \LogicException('Message is immutable');
    }
}
