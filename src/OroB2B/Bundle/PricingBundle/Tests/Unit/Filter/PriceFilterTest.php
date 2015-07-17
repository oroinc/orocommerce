<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Filter;

use OroB2B\Bundle\PricingBundle\Filter\PriceFilter;

class PriceFilterTest extends \PHPUnit_Framework_TestCase
{
    protected $priceFilter;

    public function setUp()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($form));

        $filterUtility = $this->getMockBuilder('Oro\Bundle\FilterBundle\Filter\FilterUtility')
            ->disableOriginalConstructor()
            ->getMock();
        $filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->will($this->returnValue([]));

        $this->priceFilter = new PriceFilter($formFactory, $filterUtility);
    }

    public function testApply()
    {
        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(true, $this->priceFilter->apply($ds, []));
    }

    public function testGetMetadata()
    {
        $metadata = $this->priceFilter->getMetadata();
        $this->assertEquals(true, array_key_exists('unitChoices', $metadata));
    }
}
