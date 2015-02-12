<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use OroB2B\Bundle\AttributeBundle\Datagrid\AttributeHelper;

class AttributeHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetActionConfiguration()
    {
        $attribute = new ResultRecord(['system' => true]);
        $this->assertEquals(
            ['delete' => false],
            AttributeHelper::getActionConfiguration($attribute),
            'Non-system attributes should be available for deletion'
        );

        $attribute = new ResultRecord(['system' => false]);
        $this->assertEquals(
            ['delete' => true],
            AttributeHelper::getActionConfiguration($attribute),
            'System attributes should not be deletable'
        );
    }
}
