<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Filter;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

use OroB2B\Bundle\PricingBundle\Filter\ProductPriceFilter;

class ProductPriceFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceFilter
     */
    protected $productPriceFilter;

    public function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface $formFactory */
        $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($form));

        /** @var \PHPUnit_Framework_MockObject_MockObject|FilterUtility $filterUtility */
        $filterUtility = $this->getMockBuilder('Oro\Bundle\FilterBundle\Filter\FilterUtility')
            ->disableOriginalConstructor()
            ->getMock();
        $filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->will($this->returnValue([]));

        $this->productPriceFilter = new ProductPriceFilter($formFactory, $filterUtility);
    }

    public function testApply()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OrmFilterDatasourceAdapter $ds */
        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(false, $this->productPriceFilter->apply($ds, []));
    }

    public function testGetMetadata()
    {
        $metadata = $this->productPriceFilter->getMetadata();
        $this->assertEquals(true, array_key_exists('unitChoices', $metadata));
    }
}
