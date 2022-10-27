<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\InventoryBundle\Validator\Constraints\CheckoutShipUntil;
use Oro\Bundle\InventoryBundle\Validator\Constraints\CheckoutShipUntilValidator;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class CheckoutShipUntilValidatorTest extends ConstraintValidatorTestCase
{
    /** @var UpcomingProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $upcomingProvider;

    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    protected function setUp(): void
    {
        $this->upcomingProvider = $this->createMock(UpcomingProductProvider::class);
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new CheckoutShipUntilValidator($this->upcomingProvider, $this->checkoutLineItemsManager);
    }

    /**
     * @dataProvider getValidationData
     */
    public function testValidate(?\DateTime $shipUntil, ?\DateTime $latestDate, bool $isErrorExpected)
    {
        $checkout = new Checkout();
        $checkout->setShipUntil($shipUntil);
        $this->checkoutLineItemsManager->expects($this->any())
            ->method('getData')
            ->with($checkout)
            ->willReturn([
                (new OrderLineItem())->setProduct($product1 = new Product()),
                (new OrderLineItem())->setProduct($product2 = new Product()),
            ]);

        $this->upcomingProvider->expects($this->any())
            ->method('getLatestAvailabilityDate')
            ->with([$product1, $product2])
            ->willReturn($latestDate);

        $constraint = new CheckoutShipUntil();
        $this->validator->validate($checkout, $constraint);

        if ($isErrorExpected) {
            $this->buildViolation($constraint->message)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function getValidationData(): array
    {
        return [
            [
                'shipUntil' => new \DateTime('01-01-2030'),
                'latestDate' => new \DateTime('01-01-2020'),
                'isErrorExpected' => false
            ],
            [
                'shipUntil' => new \DateTime('01-01-2020'),
                'latestDate' => new \DateTime('01-01-2020 12:00'),
                'isErrorExpected' => false
            ],
            [
                'shipUntil' => new \DateTime('01-01-2010'),
                'latestDate' => new \DateTime('01-01-2020'),
                'isErrorExpected' => true
            ],
            [
                'shipUntil' => null,
                'latestDate' => new \DateTime('01-01-2020'),
                'isErrorExpected' => false
            ],
            [
                'shipUntil' => null,
                'latestDate' => null,
                'isErrorExpected' => false
            ],
            [
                'shipUntil' => new \DateTime('01-01-2010'),
                'latestDate' => null,
                'isErrorExpected' => false
            ],
        ];
    }
}
