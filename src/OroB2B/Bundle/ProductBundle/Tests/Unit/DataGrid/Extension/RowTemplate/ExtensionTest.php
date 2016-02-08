<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\DataGrid\Extension\RowTemplate;

use OroB2B\Bundle\ProductBundle\DataGrid\Extension\RowTemplate\Extension;
use OroB2B\Bundle\ProductBundle\DataGrid\Extension\RowTemplate\Configuration;

class ExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Extension
     */
    protected $extension;

    public function setUp()
    {
        $this->extension = new Extension();
    }

    public function testIsApplicable()
    {
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $actual = $this->extension->isApplicable($config);
        $this->assertTrue($actual);
    }

    public function testProcessConfigs()
    {
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('offsetGetByPath')
            ->with(Configuration::TEMPLATES_PATH)
            ->willReturn(['row' => 'row-template']);

        $this->extension->processConfigs($config);
    }
}
