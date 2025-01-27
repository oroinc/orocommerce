<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Model\CheckoutBySourceCriteriaManipulator;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ActualizeCheckout;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActualizeCheckoutTest extends TestCase
{
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private CheckoutBySourceCriteriaManipulator|MockObject $checkoutBySourceCriteriaManipulator;
    private ActualizeCheckout $actualizeCheckout;

    #[\Override]
    protected function setUp(): void
    {
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->checkoutBySourceCriteriaManipulator = $this->createMock(CheckoutBySourceCriteriaManipulator::class);

        $this->actualizeCheckout = new ActualizeCheckout(
            $this->userCurrencyManager,
            $this->checkoutBySourceCriteriaManipulator
        );
    }

    public function testExecute(): void
    {
        $checkout = new Checkout();
        $sourceCriteria = ['source_entity' => $this->createMock(CheckoutSourceEntityInterface::class)];
        $checkoutData = [];
        $updateData = true;
        $website = $this->createMock(Website::class);

        $this->userCurrencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn('USD');
        $this->checkoutBySourceCriteriaManipulator->expects(self::once())
            ->method('actualizeCheckout')
            ->with(
                $checkout,
                $website,
                $sourceCriteria,
                'USD',
                $checkoutData,
                $updateData
            )
            ->willReturn($checkout);

        $result = $this->actualizeCheckout->execute(
            $checkout,
            $sourceCriteria,
            $website,
            $updateData,
            $checkoutData
        );

        self::assertSame($checkout, $result);
    }
}
