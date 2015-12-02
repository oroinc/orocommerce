<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Storage;

use Doctrine\Common\Cache\CacheProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityCalculator;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class CategoryVisibilityStorage
{
    const ALL_CACHE_ID = 'all';
    const ACCOUNT_CACHE_ID_PREFIX = 'account';
    const ACCOUNT_GROUP_CACHE_ID_PREFIX = 'account_group';

    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @var CategoryVisibilityCalculator
     */
    protected $calculator;

    /**
     * @param CacheProvider $provider
     * @param CategoryVisibilityCalculator $calculator
     */
    public function __construct(CacheProvider $provider, CategoryVisibilityCalculator $calculator)
    {
        $this->cacheProvider = $provider;
        $this->calculator = $calculator;
    }

    /**
     * @return CategoryVisibilityData
     */
    public function getCategoryVisibilityData()
    {
        return $this->getData($this->getCacheId(), 'calculate');
    }

    /**
     * @param AccountGroup $accountGroup
     * @return CategoryVisibilityData
     */
    public function getCategoryVisibilityDataForAccountGroup(AccountGroup $accountGroup)
    {
        return $this->getCategoryVisibilityData()->merge(
            $this->getData($this->getCacheIdForAccountGroup($accountGroup), 'calculateForAccountGroup', $accountGroup)
        );
    }

    /**
     * @param Account|null $account
     * @return CategoryVisibilityData
     */
    public function getCategoryVisibilityDataForAccount(Account $account = null)
    {
        if (is_null($account)) {
            return $this->getCategoryVisibilityData();
        }
        if ($account->getGroup()) {
            $categoryVisibilityData = $this->getCategoryVisibilityDataForAccountGroup($account->getGroup());
        } else {
            $categoryVisibilityData = $this->getCategoryVisibilityData();
        }
        return $categoryVisibilityData->merge(
            $this->getData($this->getCacheIdForAccount($account), 'calculateForAccount', $account)
        );
    }

    public function flush()
    {
        $this->cacheProvider->deleteAll();
    }

    public function clear()
    {
        $this->cacheProvider->delete($this->getCacheId());
    }

    /**
     * @param AccountGroup $accountGroup
     */
    public function clearForAccountGroup(AccountGroup $accountGroup)
    {
        $this->cacheProvider->delete($this->getCacheIdForAccountGroup($accountGroup));
    }

    /**
     * @param Account $account
     */
    public function clearForAccount(Account $account)
    {
        $this->cacheProvider->delete($this->getCacheIdForAccount($account));
    }

    /**
     * @return string
     */
    protected function getCacheId()
    {
        return static::ALL_CACHE_ID;
    }

    /**
     * @param AccountGroup $accountGroup
     * @return string
     */
    protected function getCacheIdForAccountGroup(AccountGroup $accountGroup)
    {
        return static::ACCOUNT_GROUP_CACHE_ID_PREFIX . '.' . $accountGroup->getId();
    }

    /**
     * @param Account $account
     * @return string
     */
    protected function getCacheIdForAccount(Account $account)
    {
        return static::ACCOUNT_CACHE_ID_PREFIX . '.' . $account->getId();
    }

    /**
     * @param string $cacheId
     * @param string $calculatorVisibilityMethodName
     * @param AccountGroup|Account|null $entity
     * @return CategoryVisibilityData
     */
    protected function getData($cacheId, $calculatorVisibilityMethodName, $entity = null)
    {
        $data = $this->cacheProvider->fetch($cacheId);
        if ($data) {
            return CategoryVisibilityData::fromArray($data);
        }
        $data = call_user_func([$this->calculator, $calculatorVisibilityMethodName], $entity);
        $this->cacheProvider->save($cacheId, $data->toArray());

        return $data;
    }
}
