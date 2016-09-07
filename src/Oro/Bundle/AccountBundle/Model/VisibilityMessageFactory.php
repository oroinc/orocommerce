<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;

class VisibilityMessageFactory
{
    const ID = 'id';
    const ENTITY_CLASS_NAME = 'entity_class_name';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }
    
    /**
     * @param object $visibility
     * @return array
     */
    public function createMessage($visibility)
    {
        return [
            self::ID => $visibility->getId(),
            self::ENTITY_CLASS_NAME => ClassUtils::getClass($visibility)
        ];
    }

    /**
     * @param array|null $data
     * @return object
     */
    public function getEntityFromMessage($data)
    {
        if (!is_array($data) || empty($data)) {
            throw new InvalidArgumentException('Message should not be empty.');
        }

        if (!isset($data[self::ID]) || !$data[self::ID]) {
            throw new InvalidArgumentException('Visibility id is required.');
        }

        if (!isset($data[self::ENTITY_CLASS_NAME]) || !$data[self::ENTITY_CLASS_NAME]) {
            throw new InvalidArgumentException('Visibility class name is required.');
        }
        
        return $this->registry->getManagerForClass($data[self::ENTITY_CLASS_NAME])
            ->getRepository($data[self::ENTITY_CLASS_NAME])
            ->find($data[self::ID]);
    }
}
