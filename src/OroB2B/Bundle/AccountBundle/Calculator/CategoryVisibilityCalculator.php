<?php

namespace OroB2B\Bundle\AccountBundle\Calculator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

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
        $repo = $this->getRepository('OroB2BAccountBundle:Visibility\CategoryVisibility');
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
    public function calculateVisible($visibilities)
    {
        $result = [];
        $visibleIds = [];

        foreach ($visibilities as $category) {
            $id = $category['id'];
            $result[$id] = [];
            // calculation order is import ant because of result dependency
            $result[$id][self::TO_ALL] = $this->calculateVisibleToAll($category, $result);
            $result[$id][self::TO_GROUP] = $this->calculateVisibleToGroup($category, $result);
            $result[$id][self::TO_ACCOUNT] = $this->calculateVisibleToAccount($category, $result);

            // todo refactor: move Visibility constants to model
            if ('visible' === $result[$id][self::TO_ACCOUNT]) {
                $visibleIds[] = $id;
            }
        }

        return $visibleIds;
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
            // CategoryVisibility::PARENT_CATEGORY
            case null:
                if (null !== $category['parent_id']) {
                    return $result[$category['parent_id']][self::TO_ALL];
                } else {
                    return $this->getConfigValue(self::CATEGORY_VISIBILITY_CONFIG_VALUE_KEY);
                }
                break;
            case CategoryVisibility::CONFIG:
                return $this->getConfigValue(self::CATEGORY_VISIBILITY_CONFIG_VALUE_KEY);
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
            // AccountGroupCategoryVisibility::CATEGORY
            case null:
                return $result[$id][self::TO_ALL];
            case AccountGroupCategoryVisibility::PARENT_CATEGORY:
                if (null !== $category['parent_id']) {
                    return $result[$category['parent_id']][self::TO_GROUP];
                } else {
                    return $this->getConfigValue(self::CATEGORY_VISIBILITY_CONFIG_VALUE_KEY);
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
            // AccountCategoryVisibility::ACCOUNT_GROUP
            case null:
                return $result[$id][self::TO_GROUP];
            case AccountCategoryVisibility::CATEGORY:
                return $result[$id][self::TO_ALL];
            case AccountCategoryVisibility::PARENT_CATEGORY:
                if (null !== $category['parent_id']) {
                    return $result[$category['parent_id']][self::TO_ACCOUNT];
                } else {
                    return $this->getConfigValue(self::CATEGORY_VISIBILITY_CONFIG_VALUE_KEY);
                }
                break;
            case AccountCategoryVisibility::VISIBLE:
            case AccountCategoryVisibility::HIDDEN:
                return $category[self::TO_ACCOUNT];
        }

        throw new InvalidVisibilityValueException;
    }

    /**
     * @return string
     */
    protected function getDefaultConfigValue()
    {
        return CategoryVisibility::VISIBLE;
    }

    /***
     * @param string $persistentObject The name of the persistent object.
     * @param string $persistentManagerName The object manager name (null for the default one).
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository($persistentObject, $persistentManagerName = null)
    {
        return $this->managerRegistry->getRepository($persistentObject, $persistentManagerName);
    }

    /**
     * @param string $name
     * @return array|string
     */
    protected function getConfigValue($name)
    {
        if (!$this->configManager) {
            $this->configManager = $this->container->get('oro_config.global');
        }

        return $this->configManager->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
