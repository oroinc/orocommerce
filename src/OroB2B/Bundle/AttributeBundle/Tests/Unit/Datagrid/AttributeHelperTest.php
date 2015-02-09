<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Datagrid;

use OroB2B\Bundle\AttributeBundle\Datagrid\AttributeHelper;
use OroB2B\Bundle\AttributeBundle\Tests\Unit\Datagrid\Fixtures\StubAttributeRecord;

class AttributeHelperTest extends \PHPUnit_Framework_TestCase
{

    public function testActionConfigurationClosure()
    {
        $helper = new AttributeHelper();
        $closure = $helper->getActionConfigurationClosure();

        $attribute = new StubAttributeRecord();
        $attribute->setIsSystem(false);

        $this->assertNull($closure($attribute), 'Non-system attributes should be available for deletion');

        $attribute = new StubAttributeRecord();
        $attribute->setIsSystem(true);

        $this->assertEquals(['delete' => false], $closure($attribute), 'System attributes should not be deletable');
    }
}
