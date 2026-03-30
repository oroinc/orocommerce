<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * @dbIsolationPerTest
 */
final class RemoveLineItemsFromOrderTypeExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrders::class,
            LoadProductData::class,
            LoadProductKitData::class,
            LoadProductUnits::class,
        ]);

        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Order::class);
    }

    public function testLineItemsFieldIsRemovedWhenDraftEditModeIsEnabled(): void
    {
        $draftSessionUuid = UUIDGenerator::v4();
        $this->setDraftSessionUuid($draftSessionUuid);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $form = self::createForm(OrderType::class, $order);

        self::assertFalse($form->has('lineItems'));
    }

    public function testLineItemsFieldIsNotRemovedWhenDraftEditModeIsDisabled(): void
    {
        $this->setDraftSessionUuid(null);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $form = self::createForm(OrderType::class, $order);

        self::assertTrue($form->has('lineItems'));
    }

    public function testValidationErrorsOnLineItemsAreReplacedWithGeneralError(): void
    {
        $draftSessionUuid = UUIDGenerator::v4();
        $this->setDraftSessionUuid($draftSessionUuid);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference(LoadProductUnits::LITER);

        // Create a line item with invalid data that will trigger validation errors
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity(-5); // Invalid quantity - should trigger validation error
        $lineItem->setPrice(Price::create(100, 'USD'));
        $lineItem->setDraftSessionUuid($draftSessionUuid);

        $order->addLineItem($lineItem);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $form = self::createForm(OrderType::class, $order);
        $submitData = [
            'website' => $order->getWebsite()->getId(),
            'customer' => $order->getCustomer()->getId(),
            'currency' => 'USD',
        ];

        if ($form->has('website')) {
            $submitData['website'] = $order->getWebsite()->getId();
        }

        $form->submit($submitData);

        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());

        // Check that line item validation errors are replaced with general error
        $errors = $form->getErrors();
        $hasGeneralError = false;
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
            if (str_contains($error->getMessage(), 'One or more line items have errors')) {
                $hasGeneralError = true;
                break;
            }
        }

        self::assertTrue(
            $hasGeneralError,
            'Expected general line items error to be present. Actual errors: ' . implode(', ', $errorMessages)
        );
    }

    private function setDraftSessionUuid(?string $draftSessionUuid): void
    {
        /** @var RequestContextAwareInterface $router */
        $router = self::getContainer()->get('router');
        $context = $router->getContext();
        $context->setParameter('orderDraftSessionUuid', $draftSessionUuid);
    }
}
