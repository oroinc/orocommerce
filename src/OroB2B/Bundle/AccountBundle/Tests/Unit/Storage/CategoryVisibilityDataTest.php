<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Storage;

use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityData;

class CategoryVisibilityDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider gettersDataProvider
     * @param bool $visible
     * @param array $ids
     */
    public function testGetters($visible, $ids)
    {
        $categoryVisibilityData = new CategoryVisibilityData($visible, $ids);
        $this->assertEquals($ids, $categoryVisibilityData->getIds());
        $this->assertEquals($visible, $categoryVisibilityData->isVisible());
    }

    public function gettersDataProvider()
    {
        return [
            'visible' => [
                'visible' => true,
                'ids' => [1, 2, 3]
            ],
            'invisible' => [
                'visible' => false,
                'ids' => [42]
            ]
        ];
    }
}
