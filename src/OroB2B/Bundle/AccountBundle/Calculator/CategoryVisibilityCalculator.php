<?php

namespace OroB2B\Bundle\AccountBundle\Calculator;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Exception\InvalidVisibilityValueException;

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
                return $visibility['id'];
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

        foreach ($visibilities as $category) {
            $this->setDefaultValues($category);
            $id = $category['id'];
            $result[$id] = [];

            $result[$id][self::TO_ALL] = $this->calculateVisibleToAll($category, $result);
            $result[$id][self::TO_GROUP] = $this->calculateVisibleToGroup($category, $result);
            $result[$id][self::TO_ACCOUNT] = $this->calculateVisibleToAccount($category, $result);

            // todo refactor: move visibility constants to model class to prevent string constant usage below
            if ('visible' === $result[$id][self::TO_ACCOUNT]) {
                $visibleIds[] = $id;
            }
        }

        return $visibleIds;
    }

    /**
     * @param $category
     */
    protected function setDefaultValues(&$category)
    {
        if (null === $category[self::TO_ALL]) {
            $category[self::TO_ALL] = CategoryVisibility::getDefault();
        }
        if (null === $category[self::TO_GROUP]) {
            $category[self::TO_GROUP] = AccountGroupCategoryVisibility::getDefault();
        }
        if (null === $category[self::TO_ACCOUNT]) {
            $category[self::TO_ACCOUNT] = AccountCategoryVisibility::getDefault();
        }
    }

    /**
     * @param $category
     * @param $result
     * @return null|string
     * @throws InvalidVisibilityValueException
     */
    protected function calculateVisibleToAll($category, $result)
    {
        switch ($category[self::TO_ALL]) {
            case CategoryVisibility::PARENT_CATEGORY:
                if (null !== $category['parent_category']) {
                    return $result[$category['parent_category']][self::TO_ALL];
                } else {
                    return $this->getCategoryVisibilityConfigValue();
                }
                break;
            case CategoryVisibility::CONFIG:
                return $this->getCategoryVisibilityConfigValue();
            case CategoryVisibility::VISIBLE:
            case CategoryVisibility::HIDDEN:
                return $category[self::TO_ALL];
        }

        throw new InvalidVisibilityValueException;
    }

    /**
     * @param $category
     * @param $result
     * @return null|string
     * @throws InvalidVisibilityValueException
     */
    protected function calculateVisibleToGroup($category, $result)
    {
        $id = $category['id'];
        switch ($category[self::TO_GROUP]) {
            case AccountGroupCategoryVisibility::CATEGORY:
                return $result[$id][self::TO_ALL];
            case AccountGroupCategoryVisibility::PARENT_CATEGORY:
                if (null !== $category['parent_category']) {
                    return $result[$category['parent_category']][self::TO_GROUP];
                } else {
                    return $this->getCategoryVisibilityConfigValue();
                }
                break;
            case AccountGroupCategoryVisibility::VISIBLE:
            case AccountGroupCategoryVisibility::HIDDEN:
                return $category[self::TO_GROUP];
        }

        throw new InvalidVisibilityValueException;
    }

    /**
     * @param $category
     * @param $result
     * @return null|string
     * @throws InvalidVisibilityValueException
     */
    protected function calculateVisibleToAccount($category, $result)
    {
        $id = $category['id'];
        switch ($category[self::TO_ACCOUNT]) {
            case AccountCategoryVisibility::ACCOUNT_GROUP:
                return $result[$id][self::TO_GROUP];
            case AccountCategoryVisibility::CATEGORY:
                return $result[$id][self::TO_ALL];
            case AccountCategoryVisibility::PARENT_CATEGORY:
                if (null !== $category['parent_category']) {
                    return $result[$category['parent_category']][self::TO_ACCOUNT];
                } else {
                    return $this->getCategoryVisibilityConfigValue();
                }
                break;
            case AccountCategoryVisibility::VISIBLE:
            case AccountCategoryVisibility::HIDDEN:
                return $category[self::TO_ACCOUNT];
        }

        throw new InvalidVisibilityValueException;
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
