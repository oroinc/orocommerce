<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class VisibilityMessageFactory
{
    const ID = 'id';
    const VISIBILITY_CLASS = 'visibility_class';

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
     * @param VisibilityInterface|ProductVisibility $visibility
     * @return array
     */
    public function createMessage(VisibilityInterface $visibility)
    {
        return [
            self::ID => $visibility->getId(),
            self::VISIBILITY_CLASS => ClassUtils::getClass($visibility)
        ];
    }

    /**
     * @param array|null $data
     * @return VisibilityInterface
     */
    public function getVisibilityFromMessage($data)
    {
        if (!is_array($data) || empty($data)) {
            throw new InvalidArgumentException('Message should not be empty.');
        }

        if (!isset($data[self::ID]) || !$data[self::ID]) {
            throw new InvalidArgumentException('Visibility id is required.');
        }

        if (!isset($data[self::VISIBILITY_CLASS]) || !$data[self::VISIBILITY_CLASS]) {
            throw new InvalidArgumentException('Visibility class name is required.');
        }
        
        return $this->registry->getManagerForClass($data[self::VISIBILITY_CLASS])
            ->getRepository($data[self::VISIBILITY_CLASS])
            ->find($data[self::ID]);
    }
}
