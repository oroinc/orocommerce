<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\EventListener\AbstractCustomerViewListener;
use Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\AbstractCustomerViewListenerTest;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RFPBundle\EventListener\CustomerViewListener;

class CustomerViewListenerTest extends AbstractCustomerViewListenerTest
{
    /** @var CustomerViewListener */
    protected $customerViewListener;

    protected function setUp(): void
    {
        parent::setUp();

        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->customerViewListener->setFeatureChecker($featureChecker);
        $this->customerViewListener->addFeature('rfp');
    }

    public function testOnCustomerViewDisabledFeature()
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $this->customerViewListener->setFeatureChecker($featureChecker);
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerUserViewDisabledFeature()
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $this->customerViewListener->setFeatureChecker($featureChecker);
        $this->customerViewListener->onCustomerUserView($this->event);
    }

    /**
     * {@inheritdoc}
     */
    protected function createListenerToTest(): AbstractCustomerViewListener
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
    protected function getCustomerViewTemplate(): string
    {
        return '@OroRFP/Customer/rfp_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerLabel(): string
    {
        return 'oro.rfp.datagrid.customer.label';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserViewTemplate(): string
    {
        return '@OroRFP/CustomerUser/rfp_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserLabel(): string
    {
        return 'oro.rfp.datagrid.customer_user.label';
    }
}
