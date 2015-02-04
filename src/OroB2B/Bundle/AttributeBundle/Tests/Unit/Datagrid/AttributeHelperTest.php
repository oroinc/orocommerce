<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use OroB2B\Bundle\AttributeBundle\Datagrid\AttributeHelper;

class AttributeRecord implements ResultRecordInterface
{

    /**
     * @var bool
     */
    private $isSystem = false;

    /**
     * @param bool $value [optional], by default it is true
     */
    public function setIsSystem($value = true)
    {
        $this->isSystem = $value;
    }
    /**
     * {@inheritdoc}
     */
    public function getValue($name)
    {
        if ('system' === $name) {
            return $this->isSystem;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootEntity()
    {
        return null;
    }
}

class AttributeHelperTest extends \PHPUnit_Framework_TestCase
{

    public function testActionConfigurationClosure()
    {
        $helper = new AttributeHelper();
        $closure = $helper->getActionConfigurationClosure();

        $attribute = new AttributeRecord();
        $attribute->setIsSystem(false);

        $this->assertNull($closure($attribute), 'Non-system attributes should be available for deletion');

        $attribute = new AttributeRecord();
        $attribute->setIsSystem(true);

        $this->assertEquals(['delete' => false], $closure($attribute), 'System attributes should not be deletable');
    }
}
