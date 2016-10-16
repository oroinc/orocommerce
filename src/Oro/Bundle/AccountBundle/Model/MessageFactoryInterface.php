<?php

namespace Oro\Bundle\AccountBundle\Model;

interface MessageFactoryInterface
{
    /**
     * @param object $object
     */
    public function createMessage($object);

    /**
     * @param array $data
     * @return object
     */
    public function getEntityFromMessage($data);
}
