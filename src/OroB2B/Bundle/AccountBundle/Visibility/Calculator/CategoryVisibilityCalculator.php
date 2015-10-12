<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Calculator;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Exception\InvalidVisibilityValueException;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityCalculator implements ContainerAwareInterface
{
    const CATEGORY_VISIBILITY_CONFIG_VALUE_KEY = 'oro_b2b_account.category_visibility';

    const VISIBLE = 'visible';
    const INVISIBLE = 'invisible';

    const TO_ALL = 'to_all';
    const TO_GROUP = 'to_group';
    const TO_ACCOUNT = 'to_account';

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var string
     */
    protected $configValue;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param Account|null $account
     * @return array
     */
    public function getVisibility(Account $account = null)
    {
        /** @var \OroB2B\Bundle\AccountBundle\Entity\Repository\CategoryVisibilityRepository $repo */
        $repo = $this->managerRegistry->getManagerForClass(
            'OroB2BAccountBundle:Visibility\CategoryVisibility'
        )->getRepository('OroB2BAccountBundle:Visibility\CategoryVisibility');
        $visibilities = $repo->getVisibilityToAll($account);
        $visibleIds = $this->calculateVisible($visibilities);
        $ids = array_map(
            function ($visibility) {
                return $visibility['categoryEntity']->getId();
            },
            $visibilities
        );
        $invisibleIds = array_values(array_diff($ids, $visibleIds));

        return [
            self::VISIBLE => $visibleIds,
            self::INVISIBLE => $invisibleIds
        ];
    }

    /**
     * @param array $visibilities
     * @return array
     * @throws \Exception
     */
    protected function calculateVisible($visibilities)
    {
        $result = [];
        $visibleIds = [];

        foreach ($visibilities as $visibility) {
            $this->setDefaultValues($visibility);
            /** @var Category $category */
            $category = $visibility['categoryEntity'];
            $id = $category->getId();
            $result[$id] = [];

            $result[$id][self::TO_ALL] = $this->calculateVisibleToAll($visibility, $result);
            $result[$id][self::TO_GROUP] = $this->calculateVisibleToGroup($visibility, $result);
            $result[$id][self::TO_ACCOUNT] = $this->calculateVisibleToAccount($visibility, $result);

            // todo refactor: move visibility constants to model class to prevent string constant usage below
            if ('visible' === $result[$id][self::TO_ACCOUNT]) {
                $visibleIds[] = $id;
            }
        }

        return $visibleIds;
    }

    /**
     * @param $visibility
     */
    protected function setDefaultValues(&$visibility)
    {
        /** @var Category $category */
        $category = $visibility['categoryEntity'];

        if (null === $visibility[self::TO_ALL]) {
            $visibility[self::TO_ALL] = CategoryVisibility::getDefault($category);
        }
        if (null === $visibility[self::TO_GROUP]) {
            $visibility[self::TO_GROUP] = AccountGroupCategoryVisibility::getDefault($category);
        }
        if (null === $visibility[self::TO_ACCOUNT]) {
            $visibility[self::TO_ACCOUNT] = AccountCategoryVisibility::getDefault($category);
        }
    }

    /**
     * @param $visibility
     * @param $result
     * @return null|string
     * @throws InvalidVisibilityValueException
     */
    protected function calculateVisibleToAll($visibility, $result)
    {
        /** @var Category $category */
        $category = $visibility['categoryEntity'];
        switch ($visibility[self::TO_ALL]) {
            case CategoryVisibility::PARENT_CATEGORY:
                if (null !== $category->getParentCategory()) {
                    return $result[$category->getParentCategory()->getId()][self::TO_ALL];
                } else {
                    return $this->getCategoryVisibilityConfigValue();
                }
                break;
            case CategoryVisibility::CONFIG:
                return $this->getCategoryVisibilityConfigValue();
            case CategoryVisibility::VISIBLE:
            case CategoryVisibility::HIDDEN:
                return $visibility[self::TO_ALL];
            default:
                throw new InvalidVisibilityValueException;
        }
    }

    /**
     * @param $visibility
     * @param $result
     * @return null|string
     * @throws InvalidVisibilityValueException
     */
    protected function calculateVisibleToGroup($visibility, $result)
    {
        /** @var Category $category */
        $category = $visibility['categoryEntity'];

        switch ($visibility[self::TO_GROUP]) {
            case AccountGroupCategoryVisibility::CATEGORY:
                return $result[$category->getId()][self::TO_ALL];
            case AccountGroupCategoryVisibility::PARENT_CATEGORY:
                if (null !== $category->getParentCategory()) {
                    return $result[$category->getParentCategory()->getId()][self::TO_GROUP];
                } else {
                    return $this->getCategoryVisibilityConfigValue();
                }
                break;
            case AccountGroupCategoryVisibility::VISIBLE:
            case AccountGroupCategoryVisibility::HIDDEN:
                return $visibility[self::TO_GROUP];
            default:
                throw new InvalidVisibilityValueException;
        }
    }

    /**
     * @param $visibility
     * @param $result
     * @return null|string
     * @throws InvalidVisibilityValueException
     */
    protected function calculateVisibleToAccount($visibility, $result)
    {
        /** @var Category $category */
        $category = $visibility['categoryEntity'];

        switch ($visibility[self::TO_ACCOUNT]) {
            case AccountCategoryVisibility::ACCOUNT_GROUP:
                return $result[$category->getId()][self::TO_GROUP];
            case AccountCategoryVisibility::CATEGORY:
                return $result[$category->getId()][self::TO_ALL];
            case AccountCategoryVisibility::PARENT_CATEGORY:
                if (null !== $category->getParentCategory()) {
                    return $result[$category->getParentCategory()->getId()][self::TO_ACCOUNT];
                } else {
                    return $this->getCategoryVisibilityConfigValue();
                }
                break;
            case AccountCategoryVisibility::VISIBLE:
            case AccountCategoryVisibility::HIDDEN:
                return $visibility[self::TO_ACCOUNT];
            default:
                throw new InvalidVisibilityValueException;
        }
    }

    /**
     * @return array|string
     */
    protected function getCategoryVisibilityConfigValue()
    {
        if (!$this->configManager) {
            $this->configManager = $this->container->get('oro_config.manager');
        }

        return $this->configManager->get(self::CATEGORY_VISIBILITY_CONFIG_VALUE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
