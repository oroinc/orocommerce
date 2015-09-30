<?php

namespace OroB2B\Bundle\AccountBundle\Storage;

use Doctrine\Common\Cache\CacheProvider;

use OroB2B\Bundle\AccountBundle\Calculator\CategoryVisibilityCalculator;

class CategoryVisibilityStorage
{
    const VISIBILITY = 'visibility';
    const IDS = 'ids';

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
     * @param $accountId
     * @return CategoryVisibilityData
     */
    public function getCategoryVisibilityData($accountId)
    {
        $data = $this->getData($accountId);

        return new CategoryVisibilityData($data[self::VISIBILITY], $data[self::IDS]);
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
     * @param int $accountId
     * @return array
     */
    protected function getData($accountId)
    {
        $data = $this->cacheProvider->fetch($accountId);

        if (!$data) {
            $calculatedData = $this->calculator->getVisibility($accountId);
            $data = $this->formatData($calculatedData);
            $this->cacheProvider->save($accountId, $data);
        }

        return $data;
    }

    /**
     * @param $data
     * @return array
     */
    protected function formatData($data)
    {
        $formattedData = [];
        $visible =
            count($data[CategoryVisibilityCalculator::VISIBLE]) < count($data[CategoryVisibilityCalculator::INVISIBLE]);

        $formattedData[self::VISIBILITY] = $visible;

        $formattedData[self::IDS] = $visible
            ? $data[CategoryVisibilityCalculator::VISIBLE]
            : $data[CategoryVisibilityCalculator::INVISIBLE];

        return $formattedData;
    }
}
