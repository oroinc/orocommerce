<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Storage;

use Doctrine\Common\Cache\CacheProvider;

use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityCalculator;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class CategoryVisibilityStorage
{
    const VISIBILITY = 'visibility';
    const IDS = 'ids';

    const ANONYMOUS_CACHE_KEY = 'anon';

    /** @var CacheProvider */
    protected $cacheProvider;

    /** @var CategoryVisibilityCalculator */
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
     * @param Account|null $account
     * @return CategoryVisibilityData
     */
    public function getData(Account $account = null)
    {
        $data = $this->getDataFromCache($account);

        return new CategoryVisibilityData($data[self::IDS], $data[self::VISIBILITY]);
    }

    /**
     * @param array $accountIds
     */
    public function clearData(array $accountIds = null)
    {
        if ($accountIds === null) {
            $this->cacheProvider->deleteAll();
        } else {
            foreach ($accountIds as $accountId) {
                $this->cacheProvider->delete($accountId);
            }
        }
    }

    /**
     * @param Account|null $account
     * @return array
     */
    protected function getDataFromCache(Account $account = null)
    {
        $accountId = (null !== $account) ? $account->getId() : null;
        $data = $this->cacheProvider->fetch($accountId);

        if (!$data) {
            $calculatedData = $this->calculator->getVisibility($account);
            $data = $this->formatData($calculatedData);
            $this->cacheProvider->save($accountId ?: self::ANONYMOUS_CACHE_KEY, $data);
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function formatData(array $data)
    {
        $visibleIds = $data[CategoryVisibilityCalculator::VISIBLE];
        $invisibleIds = $data[CategoryVisibilityCalculator::INVISIBLE];

        $visible = count($visibleIds) < count($invisibleIds);

        return [
            self::VISIBILITY => $visible,
            self::IDS => $visible ? $visibleIds : $invisibleIds
        ];
    }
}
