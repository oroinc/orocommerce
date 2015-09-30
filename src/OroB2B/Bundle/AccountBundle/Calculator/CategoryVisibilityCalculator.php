<?php

namespace OroB2B\Bundle\AccountBundle\Calculator;

class CategoryVisibilityCalculator
{
    /**
     * @param int|null $accountId
     * @return array
     */
    public function getVisibility($accountId = null)
    {
        $visibility = [
            'visible' => [],
            'invisible' => [],
        ];

        return $visibility;
    }
}
