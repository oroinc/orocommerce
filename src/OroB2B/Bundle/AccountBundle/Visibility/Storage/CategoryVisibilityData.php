<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Storage;

class CategoryVisibilityData
{
    const VISIBLE_KEY = 'visible';
    const HIDDEN_KEY = 'hidden';

    /**
     * @var array
     */
    protected $visibleCategoryIds = [];

    /**
     * @var array
     */
    protected $hiddenCategoryIds = [];

    /**
     * @param array $visibleCategoryIds
     * @param array $hiddenCategoryIds
     */
    public function __construct(array $visibleCategoryIds, array $hiddenCategoryIds)
    {
        $ids = array_intersect($visibleCategoryIds, $hiddenCategoryIds);
        if ($ids) {
            throw new \LogicException(
                sprintf('Ids [%s] are contained in visible and hidden arrays', implode(', ', $ids))
            );
        }
        $this->visibleCategoryIds = $visibleCategoryIds;
        $this->hiddenCategoryIds = $hiddenCategoryIds;
    }

    /**
     * @return array
     */
    public function getVisibleCategoryIds()
    {
        return $this->visibleCategoryIds;
    }

    /**
     * @return array
     */
    public function getHiddenCategoryIds()
    {
        return $this->hiddenCategoryIds;
    }

    /**
     * @param integer $categoryId
     * @return boolean
     */
    public function isCategoryVisible($categoryId)
    {
        return in_array($categoryId, $this->visibleCategoryIds);
    }

    /**
     * @param CategoryVisibilityData $categoryVisibilityData
     * @return $this
     */
    public function merge(CategoryVisibilityData $categoryVisibilityData)
    {
        $diffForDecision = array_diff($this->visibleCategoryIds, $categoryVisibilityData->getVisibleCategoryIds());

        $visibleFromDiffForDecision = array_diff($diffForDecision, $categoryVisibilityData->getHiddenCategoryIds());

        $this->visibleCategoryIds = array_merge(
            $visibleFromDiffForDecision,
            $categoryVisibilityData->getVisibleCategoryIds()
        );

        $diffForDecision = array_diff($this->hiddenCategoryIds, $categoryVisibilityData->getHiddenCategoryIds());

        $hiddenFromDiffForDecision = array_diff($diffForDecision, $categoryVisibilityData->getVisibleCategoryIds());

        $this->hiddenCategoryIds = array_merge(
            $hiddenFromDiffForDecision,
            $categoryVisibilityData->getHiddenCategoryIds()
        );

        return $this;
    }

    /**
     * @param array $data
     * @return CategoryVisibilityData
     */
    public static function fromArray(array $data)
    {
        return new static($data[self::VISIBLE_KEY], $data[self::HIDDEN_KEY]);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::VISIBLE_KEY => $this->visibleCategoryIds,
            self::HIDDEN_KEY => $this->hiddenCategoryIds,
        ];
    }
}
