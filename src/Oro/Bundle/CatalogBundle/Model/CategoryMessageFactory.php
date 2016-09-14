<?php

namespace Oro\Bundle\CatalogBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Model\Exception\InvalidArgumentException;

class CategoryMessageFactory
{
    const ID = 'id';

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
     * @param Category|null $category
     * @return array
     */
    public function createMessage(Category $category = null)
    {
        $message = [self::ID => null];
        if ($category) {
            $message[self::ID] = $category->getId();
        }
        
        return $message;
    }

    /**
     * @param array|null $data
     * @return Category
     */
    public function getCategoryFromMessage($data)
    {
        $category = null;
        if (isset($data[self::ID])) {
            $category = $this->registry->getManagerForClass(Category::class)
                ->getRepository(Category::class)
                ->find($data[self::ID]);
            if (!$category) {
                throw new InvalidArgumentException();
            }
        }

        return $category;
    }
}
