<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility\Storage;

use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityData;

class CategoryVisibilityDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider gettersDataProvider
     *
     * @param bool $visible
     * @param array $ids
     */
    public function testGetters($visible, $ids)
    {
        $categoryVisibilityData = new CategoryVisibilityData($ids, $visible);
        $this->assertEquals($visible, $categoryVisibilityData->isVisible());
        $this->assertEquals($ids, $categoryVisibilityData->getIds());
    }

    /**
     * @return array
     */
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
