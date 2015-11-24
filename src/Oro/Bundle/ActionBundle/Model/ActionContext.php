<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionContext extends AbstractStorage implements EntityAwareInterface
{
    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->offsetGet('data');
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl()
    {
        return $this->offsetGet('redirectUrl');
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }
}
