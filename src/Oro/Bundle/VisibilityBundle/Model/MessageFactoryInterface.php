<?php

namespace Oro\Bundle\VisibilityBundle\Model;

interface MessageFactoryInterface
{
    const ID = 'id';
    const ENTITY_CLASS_NAME = 'entity_class_name';

    /**
     * @param object $visibility
     */
    public function createMessage($visibility);

    /**
     * @param array $data
     * @return object
     */
    public function getEntityFromMessage($data);
}
