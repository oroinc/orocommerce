<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\SummaryDataProvider;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderManager;

class SummaryDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use CheckoutAwareContextTrait;

    /**
     * @var CheckoutDataProviderManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutDataProviderManager;

    /**
     * @var SummaryDataProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        $this->checkoutDataProviderManager = $this
            ->getMockBuilder('OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderManager')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->dataProvider = new SummaryDataProvider($this->checkoutDataProviderManager);
    }
    
    public function testGetData()
    {
        $checkout = new Checkout();
        $context = $this->prepareContext($checkout);

        $data = [];
        $this->checkoutDataProviderManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->will($this->returnValue($data));

        $this->assertEquals(new ArrayCollection($data), $this->dataProvider->getData($context));
    }
}
