<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;

class CategoryVisibilityMessageFactory implements MessageFactoryInterface
{
    const ID = 'id';
    const ENTITY_CLASS_NAME = 'entity_class_name';
    const CATEGORY_ID = 'category';
    const SCOPE_ID = 'scope';

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
     * @param object|VisibilityInterface $visibility
     * @return array
     */
    public function createMessage($visibility)
    {
        if ($visibility instanceof CategoryVisibility
            || $visibility instanceof AccountCategoryVisibility
            || $visibility instanceof AccountGroupCategoryVisibility
        ) {
            return [
                self::ID => $visibility->getId(),
                self::ENTITY_CLASS_NAME => ClassUtils::getClass($visibility),
                self::CATEGORY_ID => $visibility->getTargetEntity()->getId(),
                self::SCOPE_ID => $visibility->getScope()->getId(),
            ];
        }
        throw new InvalidArgumentException('Unsupported entity class.');
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityFromMessage($data)
    {
        if (!is_array($data) || empty($data)) {
            throw new InvalidArgumentException('Message should not be empty.');
        }
        if (empty($data[self::ENTITY_CLASS_NAME])) {
            throw new InvalidArgumentException('Message should contain entity name.');
        }
        if (empty($data[self::ID])) {
            throw new InvalidArgumentException('Message should contain entity id.');
        }

        $visibility = $this->registry->getManagerForClass($data[self::ENTITY_CLASS_NAME])
            ->getRepository($data[self::ENTITY_CLASS_NAME])
            ->find($data[self::ID]);
        if (!$visibility) {
            switch ($data[self::ENTITY_CLASS_NAME]) {
                case CategoryVisibility::class:
                    $visibility = $this->createDefaultCategoryVisibility($data);
                    break;
                case AccountCategoryVisibility::class:
                    $visibility = $this->createDefaultAccountCategoryVisibility($data);
                    break;
                case AccountGroupCategoryVisibility::class:
                    $visibility = $this->createDefaultAccountGroupCategoryVisibility($data);
                    break;
            }
        }

        return $visibility;
    }

    /**
     * @param array $data
     * @return CategoryVisibility
     */
    protected function createDefaultCategoryVisibility(array $data)
    {
        $category = $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->find($data[self::CATEGORY_ID]);
        $scope = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class)
            ->find($data[self::SCOPE_ID]);
        if (!$category) {
            throw new InvalidArgumentException('Category object was not found.');
        }
        if (!$scope) {
            throw new InvalidArgumentException('Scope object was not found.');
        }
        $visibility = new CategoryVisibility();
        $visibility->setCategory($category);
        $visibility->setScope($scope);
        $visibility->setVisibility(CategoryVisibility::getDefault($category));

        return $visibility;
    }

    /**
     * @param array $data
     * @return AccountCategoryVisibility
     */
    protected function createDefaultAccountCategoryVisibility(array $data)
    {
        $category = $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->find($data[self::CATEGORY_ID]);
        $scope = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class)
            ->find($data[self::SCOPE_ID]);
        if (!$category) {
            throw new InvalidArgumentException('Category object was not found.');
        }
        if (!$scope) {
            throw new InvalidArgumentException('Scope object was not found.');
        }
        $visibility = new AccountCategoryVisibility();
        $visibility->setCategory($category);
        $visibility->setScope($scope);
        $visibility->setVisibility(AccountCategoryVisibility::getDefault($category));

        return $visibility;
    }

    /**
     * @param array $data
     * @return AccountGroupCategoryVisibility
     */
    protected function createDefaultAccountGroupCategoryVisibility(array $data)
    {
        $category = $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->find($data[self::CATEGORY_ID]);
        $scope = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class)
            ->find($data[self::SCOPE_ID]);
        if (!$category) {
            throw new InvalidArgumentException('Category object was not found.');
        }
        if (!$scope) {
            throw new InvalidArgumentException('Scope object was not found.');
        }
        $visibility = new AccountGroupCategoryVisibility();
        $visibility->setCategory($category);
        $visibility->setScope($scope);
        $visibility->setVisibility(AccountGroupCategoryVisibility::getDefault($category));

        return $visibility;
    }
}
