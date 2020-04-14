<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\AbstractCustomerViewListenerTest;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RFPBundle\EventListener\CustomerViewListener;

class CustomerViewListenerTest extends AbstractCustomerViewListenerTest
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    /** @var CustomerViewListener */
    protected $customerViewListener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->featureChecker = $this->createFeatureChecker(true);

        $this->customerViewListener->setFeatureChecker($this->featureChecker);
        $this->customerViewListener->addFeature('rfp');
    }

    public function testOnCustomerViewDisabledFeature()
    {
        $this->featureChecker = $this->createFeatureChecker(false);
        $this->customerViewListener->setFeatureChecker($this->featureChecker);

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerUserViewDisabledFeature()
    {
        $this->featureChecker = $this->createFeatureChecker(false);
        $this->customerViewListener->setFeatureChecker($this->featureChecker);

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');
        $this->customerViewListener->onCustomerUserView($this->event);
    }

    /**
     * @param bool $isFeatureEnabled
     * @return \PHPUnit\Framework\MockObject\MockObject|FeatureChecker
     */
    protected function createFeatureChecker($isFeatureEnabled)
    {
        $featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn($isFeatureEnabled);

        return $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    protected function createListenerToTest()
    {
        return new CustomerViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->requestStack
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerViewTemplate()
    {
        return 'OroRFPBundle:Customer:rfp_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerLabel()
    {
        return 'oro.rfp.datagrid.customer.label';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserViewTemplate()
    {
        return 'OroRFPBundle:CustomerUser:rfp_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserLabel()
    {
        return 'oro.rfp.datagrid.customer_user.label';
    }
}
