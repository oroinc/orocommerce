<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Datagrid\Fixtures;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class StubAttributeRecord implements ResultRecordInterface
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
