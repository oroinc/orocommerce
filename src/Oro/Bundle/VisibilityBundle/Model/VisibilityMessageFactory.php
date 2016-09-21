<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;

class VisibilityMessageFactory implements MessageFactoryInterface
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
     * {@inheritdoc}
     */
    public function createMessage($visibility)
    {
        return [
            self::ID => $visibility->getId(),
            self::ENTITY_CLASS_NAME => ClassUtils::getClass($visibility)
        ];
    }

    /**
     * {@inheritdoc}
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

        $visibility = $this->registry->getManagerForClass($data[self::ENTITY_CLASS_NAME])
            ->getRepository($data[self::ENTITY_CLASS_NAME])
            ->find($data[self::ID]);

        if (!$visibility) {
            throw new InvalidArgumentException('Visibility not found.');
        }

        return $visibility;
    }
}
