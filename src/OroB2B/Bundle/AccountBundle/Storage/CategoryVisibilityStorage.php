<?php

namespace OroB2B\Bundle\AccountBundle\Storage;

use Doctrine\Common\Cache\CacheProvider;

use OroB2B\Bundle\AccountBundle\Calculator\CategoryVisibilityCalculator;

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
     * @param int|null $accountId
     * @return CategoryVisibilityData
     */
    public function getCategoryVisibilityData($accountId = null)
    {
        $data = $this->getData($accountId);

        return new CategoryVisibilityData($data[self::IDS], $data[self::VISIBILITY]);
    }

    /**
     * @param array $accountIds
     */
    public function clearData(array $accountIds = null)
    {
        if (empty($accountIds)) {
            $this->cacheProvider->deleteAll();
        } else {
            foreach ($accountIds as $accountId) {
                $this->cacheProvider->delete($accountId);
            }
        }
    }

    /**
     * @param int|null $accountId
     * @return array
     */
    protected function getData($accountId = null)
    {
        $data = $this->cacheProvider->fetch($accountId);

        if (!$data) {
            $calculatedData = $this->calculator->getVisibility($accountId);
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
