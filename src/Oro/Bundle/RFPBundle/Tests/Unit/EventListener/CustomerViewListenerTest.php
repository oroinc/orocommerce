<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Tests\Unit\EventListener\CustomerViewListenerTest as BaseCustomerViewListenerTest;

use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\RFPBundle\EventListener\CustomerViewListener;

class CustomerViewListenerTest extends BaseCustomerViewListenerTest
{
    const CUSTOMER_VIEW_TEMPLATE = CustomerViewListener::CUSTOMER_VIEW_TEMPLATE;
    const CUSTOMER_USER_VIEW_TEMPLATE = CustomerViewListener::CUSTOMER_USER_VIEW_TEMPLATE;

    /** @var ScrollData|\PHPUnit_Framework_MockObject_MockObject */
    protected $scrollData;

    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureChecker;

    /** * @var CustomerViewListener */
    protected $customerViewListener;

    protected function setUp()
    {
        parent::setUp();

        $this->customerViewListener = new CustomerViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->requestStack
        );

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerViewListener->setFeatureChecker($this->featureChecker);
        $this->customerViewListener->addFeature('rfp');
    }

    public function testOnCustomerViewGetsIgnoredIfNoRequest()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        return parent::testOnCustomerViewGetsIgnoredIfNoRequest();
    }

    public function testOnCustomerViewGetsIgnoredIfNoRequestId()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        return parent::testOnCustomerViewGetsIgnoredIfNoRequestId();
    }

    public function testOnCustomerViewGetsIgnoredIfNoEntityFound()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        return parent::testOnCustomerViewGetsIgnoredIfNoEntityFound();
    }

    public function testOnCustomerViewCreatesScrollBlock()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        return parent::testOnCustomerViewCreatesScrollBlock();
    }

    public function testOnCustomerUserViewCreatesScrollBlock()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        parent::testOnCustomerUserViewCreatesScrollBlock();
    }

    public function testOnCustomerViewDisabledFeature()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerUserViewDisabledFeature()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $this->event->expects($this->never())
            ->method('getEnvironment');
        $this->customerViewListener->onCustomerUserView($this->event);
    }
}
