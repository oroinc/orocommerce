<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityMessageFactory implements MessageFactoryInterface
{
    const ID = 'id';
    const ENTITY_CLASS_NAME = 'entity_class_name';
    const CATEGORY_ID = 'category';
    const ACCOUNT_ID = 'account';
    const ACCOUNT_GROUP_ID = 'account_group';
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
        $entityClass = ClassUtils::getClass($visibility);
        switch ($entityClass) {
            case CategoryVisibility::class:
                $message = $this->categoryVisibilityToArray($visibility);
                break;
            case AccountCategoryVisibility::class:
                $message = $this->accountCategoryVisibilityToArray($visibility);
                break;
            case AccountGroupCategoryVisibility::class:
                $message = $this->accountGroupCategoryVisibilityToArray($visibility);
                break;
            default:
                throw new InvalidArgumentException('Unsupported entity class.');
        }

        return $message;
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
        if (!$category) {
            throw new InvalidArgumentException('Category object was not found.');
        }
        $visibility = new CategoryVisibility();
        $visibility->setCategory($category);
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
        $account = $this->registry->getManagerForClass(Account::class)
            ->getRepository(Account::class)
            ->find($data[self::ACCOUNT_ID]);
        if (!$category) {
            throw new InvalidArgumentException('Category object was not found.');
        }
        if (!$account) {
            throw new InvalidArgumentException('Account object was not found.');
        }
        $visibility = new AccountCategoryVisibility();
        $visibility->setCategory($category);
        $visibility->setAccount($account);
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
        $accountGroup = $this->registry->getManagerForClass(AccountGroup::class)
            ->getRepository(AccountGroup::class)
            ->find($data[self::ACCOUNT_GROUP_ID]);
        if (!$category) {
            throw new InvalidArgumentException('Category object was not found.');
        }
        if (!$accountGroup) {
            throw new InvalidArgumentException('AccountGroup object was not found.');
        }
        $visibility = new AccountGroupCategoryVisibility();
        $visibility->setCategory($category);
        $visibility->setAccountGroup($accountGroup);
        $visibility->setVisibility(AccountGroupCategoryVisibility::getDefault($category));

        return $visibility;
    }

    /**
     * @param VisibilityInterface|CategoryVisibility $visibility
     * @return array
     */
    protected function categoryVisibilityToArray(VisibilityInterface $visibility)
    {
        return [
            self::ID => $visibility->getId(),
            self::ENTITY_CLASS_NAME => ClassUtils::getClass($visibility),
            self::CATEGORY_ID => $visibility->getCategory()->getId(),
        ];
    }

    /**
     * @param VisibilityInterface|AccountCategoryVisibility $visibility
     * @return array
     */
    protected function accountCategoryVisibilityToArray(VisibilityInterface $visibility)
    {
        $data = $this->categoryVisibilityToArray($visibility);
        $data[self::ACCOUNT_ID] = $visibility->getAccount()->getId();

        return $data;
    }

    /**
     * @param VisibilityInterface|AccountGroupCategoryVisibility $visibility
     * @return array
     */
    protected function accountGroupCategoryVisibilityToArray(VisibilityInterface $visibility)
    {
        $data = $this->categoryVisibilityToArray($visibility);
        $data[self::ACCOUNT_GROUP_ID] = $visibility->getAccountGroup()->getId();

        return $data;
    }
}
