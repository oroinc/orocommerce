<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Calculator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Visibility\Exception\InvalidVisibilityValueException;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityData;

class CategoryVisibilityCalculator
{
    /**
     * @var array
     */
    protected $resolvedVisibilities = [];

    /**
     * @var array
     */
    protected $resolvedAccountGroupVisibilities = [];

    /**
     * @var array
     */
    protected $resolvedAccountVisibilities = [];

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     */
    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    /**
     * @param string $configPath
     */
    public function setVisibilityConfigurationPath($configPath)
    {
        $this->configPath = $configPath;
    }

    /**
     * @return CategoryVisibilityData
     * @throws InvalidVisibilityValueException
     */
    public function calculate()
    {
        $queryBuilder = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\CategoryVisibility')
            ->getCategoriesVisibilitiesQueryBuilder();

        return $this->calculateProcess($queryBuilder, function (array $data) {
            return $this->calculateVisibility($data);
        });
    }

    /**
     * @param AccountGroup $accountGroup
     * @return CategoryVisibilityData
     */
    public function calculateForAccountGroup(AccountGroup $accountGroup)
    {
        $queryBuilder = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility')
            ->getCategoryWithVisibilitiesForAccountGroup($accountGroup);

        return $this->calculateProcess($queryBuilder, function (array $data) {
            return $this->calculateAccountGroupVisibility($data);
        }, 'account_group_visibility');
    }

    /**
     * @param Account $account
     * @return CategoryVisibilityData
     */
    public function calculateForAccount(Account $account)
    {
        $queryBuilder = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
            ->getCategoryVisibilitiesForAccount($account);

        return $this->calculateProcess($queryBuilder, function (array $data) {
            return $this->calculateAccountVisibility($data);
        }, 'account_visibility');
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param \Closure $closure
     * @param string $field
     * @return CategoryVisibilityData
     * @throws InvalidVisibilityValueException
     */
    protected function calculateProcess(QueryBuilder $queryBuilder, \Closure $closure, $field = null)
    {
        $iterator = new BufferedQueryResultIterator($queryBuilder);

        $result = [
            CategoryVisibilityData::HIDDEN_KEY => [],
            CategoryVisibilityData::VISIBLE_KEY => [],
        ];
        foreach ($iterator as $data) {
            $visibility = call_user_func($closure, $data);
            // get only non default values to decrease storage size
            if ($field && empty($data[$field])) {
                continue;
            }
            $visibilityKey = $visibility ? CategoryVisibilityData::VISIBLE_KEY : CategoryVisibilityData::HIDDEN_KEY;
            $result[$visibilityKey][] = $data['category_id'];
        }

        return CategoryVisibilityData::fromArray($result);
    }

    /**
     * @param array|null $data
     * @return bool
     * @throws InvalidVisibilityValueException
     */
    protected function calculateVisibility($data)
    {
        if (is_null($data['visibility'])) {
            $data['visibility'] = CategoryVisibility::PARENT_CATEGORY;
            if (is_null($data['category_parent_id'])) {
                $data['visibility'] = CategoryVisibility::CONFIG;
            }
        }

        $visible = false;
        switch ($data['visibility']) {
            case CategoryVisibility::HIDDEN:
                break;
            case CategoryVisibility::VISIBLE:
                $visible = true;
                break;
            case CategoryVisibility::CONFIG:
                $visible = $this->isVisibleByConfig();
                break;
            case CategoryVisibility::PARENT_CATEGORY:
                $visible = $this->resolvedVisibilities[$data['category_parent_id']];
                break;
            default:
                throw new InvalidVisibilityValueException();
        }

        return $this->resolvedVisibilities[$data['category_id']] = $visible;
    }

    /**
     * @param array $data
     * @return bool
     * @throws InvalidVisibilityValueException
     */
    protected function calculateAccountGroupVisibility(array $data)
    {
        $visibility = $this->calculateVisibility($data);

        if (empty($data['account_group_visibility'])) {
            return $this->resolvedAccountGroupVisibilities[$data['category_id']] = $visibility;
        }

        $visible = false;
        switch ($data['account_group_visibility']) {
            case AccountGroupCategoryVisibility::HIDDEN:
                break;
            case AccountGroupCategoryVisibility::VISIBLE:
                $visible = true;
                break;
            case AccountGroupCategoryVisibility::PARENT_CATEGORY:
                $visible = $this->resolvedAccountGroupVisibilities[$data['category_parent_id']];
                break;
            default:
                throw new InvalidVisibilityValueException();
        }

        return $this->resolvedAccountGroupVisibilities[$data['category_id']] = $visible;
    }

    /**
     * @param array $data
     * @return bool
     * @throws InvalidVisibilityValueException
     */
    protected function calculateAccountVisibility(array $data)
    {
        $groupVisibility = $this->calculateAccountGroupVisibility($data);

        if (is_null($data['account_visibility'])) {
            return $this->resolvedAccountVisibilities[$data['category_id']] = $groupVisibility;
        }

        $visible = false;
        switch ($data['account_visibility']) {
            case AccountCategoryVisibility::HIDDEN:
                break;
            case AccountCategoryVisibility::VISIBLE:
                $visible = true;
                break;
            case AccountCategoryVisibility::CATEGORY:
                $visible = $this->calculateVisibility($data);
                break;
            case AccountCategoryVisibility::PARENT_CATEGORY:
                $visible = $this->resolvedAccountVisibilities[$data['category_parent_id']];
                break;
            default:
                throw new InvalidVisibilityValueException();
        }

        return $this->resolvedAccountVisibilities[$data['category_id']] = $visible;
    }

    /**
     * @return bool
     */
    protected function isVisibleByConfig()
    {
        if (empty($this->configPath)) {
            throw new \LogicException(
                'Visibility configuration path not configured for AbstractCategoryVisibilityCalculator'
            );
        }
        return $this->configManager->get($this->configPath) === CategoryVisibility::VISIBLE;
    }
}
