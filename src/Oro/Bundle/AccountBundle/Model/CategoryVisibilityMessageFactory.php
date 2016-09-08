<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityMessageFactory implements MessageFactoryInterface
{
    const CATEGORY = 'category';

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
     * @param object|CategoryVisibility $visibility
     * @return array
     */
    public function createMessage($visibility)
    {
        return [
            self::CATEGORY => $visibility->getCategory()->getId(),
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

        $visibility = $this->registry->getManagerForClass(CategoryVisibility::class)
            ->getRepository(CategoryVisibility::class)
            ->findOneBy(['category' => $data[self::CATEGORY]]);

        if (!$visibility) {
            $visibility = $this->createDefaultVisibility($data);
        }

        return $visibility;
    }

    /**
     * @param array $data
     * @return ProductVisibility
     */
    protected function createDefaultVisibility(array $data)
    {
        $category = $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->find($data[self::CATEGORY]);
        if (!$category) {
            throw new InvalidArgumentException('Required objects was not found.');
        }
        $visibility = new CategoryVisibility();
        $visibility->setCategory($category);
        $visibility->setVisibility(CategoryVisibility::getDefault($category));

        return $visibility;
    }
}
