<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;

class QuoteTypeTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroSaleBundle/Tests/Functional/Form/Type/DataFixtures/QuoteType.yml',
            '@OroSaleBundle/Tests/Functional/Form/Type/DataFixtures/QuoteType.quote.yml',
        ]);

        $request = Request::createFromGlobals();
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->getClientInstance()->getContainer()->get('request_stack')->push($request);
    }

    public function testCreateWhenEmptyData(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(QuoteType::class, new Quote(), ['csrf_protection' => false]);

        $this->assertQuoteFormFields($form);

        $formView = $form->createView();
        self::assertContains('oro_sale_quote', $formView->vars['block_prefixes']);
    }

    public function testCreateWhenHasData(): void
    {
        /** @var Quote $quoteEntity */
        $quoteEntity = $this->getReference('quote1');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(QuoteType::class, $quoteEntity, ['csrf_protection' => false]);

        $this->assertQuoteFormFields($form);

        self::assertCount(2, $form->get('quoteProducts'));
        self::assertCount(0, $form->get('quoteProducts')[0]->get('kitItemLineItems'));
        self::assertCount(2, $form->get('quoteProducts')[1]->get('kitItemLineItems'));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSubmitNewWithProductSimple(): void
    {
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');
        /** @var ProductUnit $productUnitItem */
        $productUnitItem = $this->getReference('item');
        /** @var ProductUnit $productUnitSet */
        $productUnitSet = $this->getReference('set');
        /** @var ProductUnit $productUnitEach */
        $productUnitEach = $this->getReference('each');
        /** @var User $user */
        $user = $this->getReference('user');
        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference('customer_user');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteType::class,
            new Quote(),
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $shipUntil = new \DateTime('today');
        $validUntil = new \DateTime('today +1 day');

        $form->submit([
            'owner' => $user->getId(),
            'customerUser' => $customerUser->getId(),
            'customer' => $customer->getId(),
            'validUntil' => $validUntil->format('c'),
            'shippingMethodLocked' => false,
            'allowUnlistedShippingMethod' => true,
            'poNumber' => '13',
            'shipUntil' => $shipUntil->format('c'),
            'quoteProducts' => [
                [
                    'product' => $productSimple1->getId(),
                    'quoteProductOffers' => [
                        [
                            'quantity' => '111.4567',
                            'productUnit' => $productUnitItem->getCode(),
                            'price' => [
                                'value' => '42.5678',
                                'currency' => 'USD',
                            ],
                        ],
                        [
                            'quantity' => '222.5678',
                            'productUnit' => $productUnitSet->getCode(),
                            'price' => [
                                'value' => '78.9000',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'comment' => 'Sample comment 1',
                ],
                [
                    'product' => $productSimple3->getId(),
                    'quoteProductOffers' => [
                        [
                            'quantity' => '11',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '34.5678',
                                'currency' => 'USD',
                            ],
                        ],
                        [
                            'quantity' => '22',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '30.1234',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'comment' => 'Sample comment 2',
                ],
            ],
            'assignedUsers' => $user->getId(),
            'assignedCustomerUsers' => $customerUser->getId(),
            'overriddenShippingCostAmount' => [
                'value' => 111.12,
                'currency' => 'USD',
            ],
            'shippingMethod' => 'shippingMethod1',
            'shippingMethodType' => 'shippingMethodType1',
            'estimatedShippingCostAmount' => 100,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(Quote::class, $form->getData());

        /** @var Quote $quoteEntity */
        $quoteEntity = $form->getData();

        // Checks quote entity.
        self::assertEquals($user->getId(), $quoteEntity->getOwner()?->getId());
        self::assertEquals($customer->getId(), $quoteEntity->getCustomer()?->getId());
        self::assertEquals($customerUser->getId(), $quoteEntity->getCustomerUser()?->getId());
        self::assertEquals($shipUntil, $quoteEntity->getShipUntil());
        self::assertEquals($validUntil, $quoteEntity->getValidUntil());
        self::assertEquals('13', $quoteEntity->getPoNumber());
        self::assertFalse($quoteEntity->isShippingMethodLocked());
        self::assertTrue($quoteEntity->isAllowUnlistedShippingMethod());
        self::assertEquals(Price::create(100, 'USD'), $quoteEntity->getEstimatedShippingCost());
        self::assertEquals(111.12, $quoteEntity->getOverriddenShippingCostAmount());
        self::assertEquals('USD', $quoteEntity->getCurrency());
        self::assertEquals('shippingMethod1', $quoteEntity->getShippingMethod());
        self::assertEquals('shippingMethodType1', $quoteEntity->getShippingMethodType());

        // Checks quote assigned users.
        self::assertCount(1, $quoteEntity->getAssignedUsers());

        // Checks quote assigned user #1.
        $quoteAssignedUser1 = $quoteEntity->getAssignedUsers()[0];
        self::assertSame($user, $quoteAssignedUser1);

        // Checks quote assigned customer users.
        self::assertCount(1, $quoteEntity->getAssignedCustomerUsers());

        // Checks quote assigned customer user #1.
        $quoteAssignedCustomerUser1 = $quoteEntity->getAssignedCustomerUsers()[0];
        self::assertSame($customerUser, $quoteAssignedCustomerUser1);

        // Checks quote products.
        self::assertCount(2, $quoteEntity->getQuoteProducts());

        // Checks quote product #1.
        $quoteProduct1 = $quoteEntity->getQuoteProducts()[0];
        self::assertSame($productSimple1, $quoteProduct1->getProduct());
        self::assertEquals('Sample comment 1', $quoteProduct1->getComment());

        // Checks kit item line items of quote product #1.
        self::assertCount(0, $quoteProduct1->getKitItemLineItems());

        // Checks quote product offers of quote product #1.
        self::assertCount(2, $quoteProduct1->getQuoteProductOffers());

        // Checks quote product offer #1 of quote product #1.
        $quoteProduct1Offer1 = $quoteProduct1->getQuoteProductOffers()[0];
        self::assertSame(111.4567, $quoteProduct1Offer1->getQuantity());
        self::assertSame($productUnitItem, $quoteProduct1Offer1->getProductUnit());
        self::assertEquals(Price::create(42.5678, 'USD'), $quoteProduct1Offer1->getPrice());

        // Checks quote product offer #2 of quote product #1.
        $quoteProduct1Offer2 = $quoteProduct1->getQuoteProductOffers()[1];
        self::assertSame(222.5678, $quoteProduct1Offer2->getQuantity());
        self::assertSame($productUnitItem, $quoteProduct1Offer1->getProductUnit());
        self::assertEquals(Price::create(78.9, 'USD'), $quoteProduct1Offer2->getPrice());

        // Checks quote product #2.
        $quoteProduct2 = $quoteEntity->getQuoteProducts()[1];
        self::assertSame($productSimple3, $quoteProduct2->getProduct());
        self::assertEquals('Sample comment 2', $quoteProduct2->getComment());

        // Checks kit item line items of quote product #2.
        self::assertCount(0, $quoteProduct2->getKitItemLineItems());

        // Checks quote product offers of quote product #2.
        self::assertCount(2, $quoteProduct2->getQuoteProductOffers());

        // Checks quote product offer #1 of quote product #2.
        $quoteProduct2Offer1 = $quoteProduct2->getQuoteProductOffers()[0];
        self::assertSame(11.0, $quoteProduct2Offer1->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct2Offer1->getProductUnit());
        self::assertEquals(Price::create(34.5678, 'USD'), $quoteProduct2Offer1->getPrice());

        // Checks quote product offer #2 of quote product #2.
        $quoteProduct2Offer2 = $quoteProduct2->getQuoteProductOffers()[1];
        self::assertSame(22.0, $quoteProduct2Offer2->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct2Offer2->getProductUnit());
        self::assertEquals(Price::create(30.1234, 'USD'), $quoteProduct2Offer2->getPrice());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSubmitExistingWithProductSimple(): void
    {
        /** @var Quote $quoteEntity */
        $quoteEntity = $this->getReference('quote1');

        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');
        /** @var ProductUnit $productUnitItem */
        $productUnitItem = $this->getReference('item');
        /** @var ProductUnit $productUnitSet */
        $productUnitSet = $this->getReference('set');
        /** @var ProductUnit $productUnitEach */
        $productUnitEach = $this->getReference('each');
        /** @var User $user */
        $user = $this->getReference('user');
        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference('customer_user');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteType::class,
            $quoteEntity,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $shipUntil = new \DateTime('today +1 day');
        $validUntil = new \DateTime('today +1 day');

        $form->submit([
            'owner' => $user->getId(),
            'customerUser' => $customerUser->getId(),
            'customer' => $customer->getId(),
            'validUntil' => $validUntil->format('c'),
            'shippingMethodLocked' => false,
            'allowUnlistedShippingMethod' => true,
            'poNumber' => '13',
            'shipUntil' => $shipUntil->format('c'),
            'quoteProducts' => [
                [
                    'product' => $productSimple1->getId(),
                    'quoteProductOffers' => [
                        [
                            'quantity' => '111.4567',
                            'productUnit' => $productUnitItem->getCode(),
                            'price' => [
                                'value' => '42.5678',
                                'currency' => 'USD',
                            ],
                        ],
                        [
                            'quantity' => '222.5678',
                            'productUnit' => $productUnitSet->getCode(),
                            'price' => [
                                'value' => '78.9000',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'comment' => 'Sample comment 1',
                ],
                [
                    'product' => $productSimple3->getId(),
                    'quoteProductOffers' => [
                        [
                            'quantity' => '11',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '34.5678',
                                'currency' => 'USD',
                            ],
                        ],
                        [
                            'quantity' => '22',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '30.1234',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'comment' => 'Sample comment 2',
                ],
            ],
            'assignedUsers' => $user->getId(),
            'assignedCustomerUsers' => $customerUser->getId(),
            'overriddenShippingCostAmount' => [
                'value' => 111.12,
                'currency' => 'USD',
            ],
            'shippingMethod' => 'shippingMethod1',
            'shippingMethodType' => 'shippingMethodType1',
            'estimatedShippingCostAmount' => 100,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(Quote::class, $form->getData());

        /** @var Quote $quoteEntity */
        $quoteEntity = $form->getData();

        // Checks quote entity.
        self::assertEquals($user->getId(), $quoteEntity->getOwner()?->getId());
        self::assertEquals($customer->getId(), $quoteEntity->getCustomer()?->getId());
        self::assertEquals($customerUser->getId(), $quoteEntity->getCustomerUser()?->getId());
        self::assertEquals($shipUntil, $quoteEntity->getShipUntil());
        self::assertEquals($validUntil, $quoteEntity->getValidUntil());
        self::assertEquals('13', $quoteEntity->getPoNumber());
        self::assertFalse($quoteEntity->isShippingMethodLocked());
        self::assertTrue($quoteEntity->isAllowUnlistedShippingMethod());
        self::assertEquals(Price::create(100, 'USD'), $quoteEntity->getEstimatedShippingCost());
        self::assertEquals(111.12, $quoteEntity->getOverriddenShippingCostAmount());
        self::assertEquals('USD', $quoteEntity->getCurrency());
        self::assertEquals('shippingMethod1', $quoteEntity->getShippingMethod());
        self::assertEquals('shippingMethodType1', $quoteEntity->getShippingMethodType());

        // Checks quote assigned users.
        self::assertCount(1, $quoteEntity->getAssignedUsers());

        // Checks quote assigned user #1.
        $quoteAssignedUser1 = $quoteEntity->getAssignedUsers()[0];
        self::assertSame($user, $quoteAssignedUser1);

        // Checks quote assigned customer users.
        self::assertCount(1, $quoteEntity->getAssignedCustomerUsers());

        // Checks quote assigned customer user #1.
        $quoteAssignedCustomerUser1 = $quoteEntity->getAssignedCustomerUsers()[0];
        self::assertSame($customerUser, $quoteAssignedCustomerUser1);

        // Checks quote products.
        self::assertCount(2, $quoteEntity->getQuoteProducts());

        // Checks quote product #1.
        $quoteProduct1 = $quoteEntity->getQuoteProducts()[0];
        self::assertSame($productSimple1, $quoteProduct1->getProduct());
        self::assertEquals('Sample comment 1', $quoteProduct1->getComment());

        // Checks kit item line items of quote product #1.
        self::assertCount(0, $quoteProduct1->getKitItemLineItems());

        // Checks quote product offers of quote product #1.
        self::assertCount(2, $quoteProduct1->getQuoteProductOffers());

        // Checks quote product offer #1 of quote product #1.
        $quoteProduct1Offer1 = $quoteProduct1->getQuoteProductOffers()[0];
        self::assertSame(111.4567, $quoteProduct1Offer1->getQuantity());
        self::assertSame($productUnitItem, $quoteProduct1Offer1->getProductUnit());
        self::assertEquals(Price::create(42.5678, 'USD'), $quoteProduct1Offer1->getPrice());

        // Checks quote product offer #2 of quote product #1.
        $quoteProduct1Offer2 = $quoteProduct1->getQuoteProductOffers()[1];
        self::assertSame(222.5678, $quoteProduct1Offer2->getQuantity());
        self::assertSame($productUnitItem, $quoteProduct1Offer1->getProductUnit());
        self::assertEquals(Price::create(78.9, 'USD'), $quoteProduct1Offer2->getPrice());

        // Checks quote product #2.
        $quoteProduct2 = $quoteEntity->getQuoteProducts()[1];
        self::assertSame($productSimple3, $quoteProduct2->getProduct());
        self::assertEquals('Sample comment 2', $quoteProduct2->getComment());

        // Checks kit item line items of quote product #2.
        self::assertCount(0, $quoteProduct2->getKitItemLineItems());

        // Checks quote product offers of quote product #2.
        self::assertCount(2, $quoteProduct2->getQuoteProductOffers());

        // Checks quote product offer #1 of quote product #2.
        $quoteProduct2Offer1 = $quoteProduct2->getQuoteProductOffers()[0];
        self::assertSame(11.0, $quoteProduct2Offer1->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct2Offer1->getProductUnit());
        self::assertEquals(Price::create(34.5678, 'USD'), $quoteProduct2Offer1->getPrice());

        // Checks quote product offer #2 of quote product #2.
        $quoteProduct2Offer2 = $quoteProduct2->getQuoteProductOffers()[1];
        self::assertSame(22.0, $quoteProduct2Offer2->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct2Offer2->getProductUnit());
        self::assertEquals(Price::create(30.1234, 'USD'), $quoteProduct2Offer2->getPrice());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSubmitNewProductKit(): void
    {
        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        /** @var ProductUnit $productUnitEach */
        $productUnitEach = $this->getReference('each');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');
        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');
        /** @var User $user */
        $user = $this->getReference('user');
        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference('customer_user');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteType::class,
            new Quote(),
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $shipUntil = new \DateTime('today +1 day');
        $validUntil = new \DateTime('today +1 day');

        $form->submit([
            'owner' => $user->getId(),
            'customerUser' => $customerUser->getId(),
            'customer' => $customer->getId(),
            'validUntil' => $validUntil->format('c'),
            'shippingMethodLocked' => false,
            'allowUnlistedShippingMethod' => true,
            'poNumber' => '13',
            'shipUntil' => $shipUntil->format('c'),
            'quoteProducts' => [
                [
                    'product' => $productKit1->getId(),
                    'kitItemLineItems' => [
                        $productKit1Item1->getId() => [
                            'product' => $productSimple1->getId(),
                            'quantity' => '45.6789',
                        ],
                        $productKit1Item2->getId() => [
                            'product' => $productSimple3->getId(),
                            'quantity' => '42',
                        ],
                    ],
                    'quoteProductOffers' => [
                        [
                            'quantity' => '111',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '42.5678',
                                'currency' => 'USD',
                            ],
                        ],
                        [
                            'quantity' => '222',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '78.9000',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'comment' => 'Sample comment 1',
                ],
                [
                    'product' => $productKit1->getId(),
                    'kitItemLineItems' => [
                        $productKit1Item1->getId() => [
                            'product' => $productSimple2->getId(),
                            'quantity' => '56.7890',
                        ],
                    ],
                    'quoteProductOffers' => [
                        [
                            'quantity' => '11',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '34.5678',
                                'currency' => 'USD',
                            ],
                        ],
                        [
                            'quantity' => '22',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '30.1234',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'comment' => 'Sample comment 2',
                ],
            ],
            'assignedUsers' => $user->getId(),
            'assignedCustomerUsers' => $customerUser->getId(),
            'overriddenShippingCostAmount' => [
                'value' => 111.12,
                'currency' => 'USD',
            ],
            'shippingMethod' => 'shippingMethod1',
            'shippingMethodType' => 'shippingMethodType1',
            'estimatedShippingCostAmount' => 100,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(Quote::class, $form->getData());

        /** @var Quote $quoteEntity */
        $quoteEntity = $form->getData();

        // Checks quote entity.
        self::assertEquals($user->getId(), $quoteEntity->getOwner()?->getId());
        self::assertEquals($customer->getId(), $quoteEntity->getCustomer()?->getId());
        self::assertEquals($customerUser->getId(), $quoteEntity->getCustomerUser()?->getId());
        self::assertEquals($shipUntil, $quoteEntity->getShipUntil());
        self::assertEquals($validUntil, $quoteEntity->getValidUntil());
        self::assertEquals('13', $quoteEntity->getPoNumber());
        self::assertFalse($quoteEntity->isShippingMethodLocked());
        self::assertTrue($quoteEntity->isAllowUnlistedShippingMethod());
        self::assertEquals(Price::create(100, 'USD'), $quoteEntity->getEstimatedShippingCost());
        self::assertEquals(111.12, $quoteEntity->getOverriddenShippingCostAmount());
        self::assertEquals('USD', $quoteEntity->getCurrency());
        self::assertEquals('shippingMethod1', $quoteEntity->getShippingMethod());
        self::assertEquals('shippingMethodType1', $quoteEntity->getShippingMethodType());

        // Checks quote assigned users.
        self::assertCount(1, $quoteEntity->getAssignedUsers());

        // Checks quote assigned user #1.
        $quoteAssignedUser1 = $quoteEntity->getAssignedUsers()[0];
        self::assertSame($user, $quoteAssignedUser1);

        // Checks quote assigned customer users.
        self::assertCount(1, $quoteEntity->getAssignedCustomerUsers());

        // Checks quote assigned customer user #1.
        $quoteAssignedCustomerUser1 = $quoteEntity->getAssignedCustomerUsers()[0];
        self::assertSame($customerUser, $quoteAssignedCustomerUser1);

        // Checks quote products.
        self::assertCount(2, $quoteEntity->getQuoteProducts());

        // Checks quote product #1.
        $quoteProduct1 = $quoteEntity->getQuoteProducts()[0];
        self::assertSame($productKit1, $quoteProduct1->getProduct());
        self::assertEquals('Sample comment 1', $quoteProduct1->getComment());

        // Checks kit item line items of quote product #1.
        self::assertCount(2, $quoteProduct1->getKitItemLineItems());

        // Checks kit item line item #1 of quote product #1.
        self::assertTrue($quoteProduct1->getKitItemLineItems()->containsKey($productKit1Item1->getId()));
        /** @var QuoteProductKitItemLineItem $quoteProduct1KitItem1LineItem */
        $quoteProduct1KitItem1LineItem = $quoteProduct1->getKitItemLineItems()[$productKit1Item1->getId()];
        self::assertSame($productSimple1, $quoteProduct1KitItem1LineItem->getProduct());
        self::assertSame(45.6789, $quoteProduct1KitItem1LineItem->getQuantity());

        // Checks kit item line item #2 of quote product #2.
        self::assertTrue($quoteProduct1->getKitItemLineItems()->containsKey($productKit1Item2->getId()));
        /** @var QuoteProductKitItemLineItem $quoteProduct1KitItem2LineItem */
        $quoteProduct1KitItem2LineItem = $quoteProduct1->getKitItemLineItems()[$productKit1Item2->getId()];
        self::assertSame($productSimple3, $quoteProduct1KitItem2LineItem->getProduct());
        self::assertSame(42.0, $quoteProduct1KitItem2LineItem->getQuantity());

        // Checks quote product offers of quote product #1.
        self::assertCount(2, $quoteProduct1->getQuoteProductOffers());

        // Checks quote product offer #1 of quote product #1.
        $quoteProduct1Offer1 = $quoteProduct1->getQuoteProductOffers()[0];
        self::assertSame(111.0, $quoteProduct1Offer1->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct1Offer1->getProductUnit());
        self::assertEquals(Price::create(42.5678, 'USD'), $quoteProduct1Offer1->getPrice());

        // Checks quote product offer #2 of quote product #1.
        $quoteProduct1Offer2 = $quoteProduct1->getQuoteProductOffers()[1];
        self::assertSame(222.0, $quoteProduct1Offer2->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct1Offer1->getProductUnit());
        self::assertEquals(Price::create(78.9, 'USD'), $quoteProduct1Offer2->getPrice());

        // Checks quote product #2.
        $quoteProduct2 = $quoteEntity->getQuoteProducts()[1];
        self::assertSame($productKit1, $quoteProduct2->getProduct());
        self::assertEquals('Sample comment 2', $quoteProduct2->getComment());

        // Checks kit item line items of quote product #2.
        self::assertCount(1, $quoteProduct2->getKitItemLineItems());

        // Checks kit item line item #1 of quote product #2.
        self::assertTrue($quoteProduct1->getKitItemLineItems()->containsKey($productKit1Item1->getId()));
        /** @var QuoteProductKitItemLineItem $quoteProduct2KitItem1LineItem */
        $quoteProduct2KitItem1LineItem = $quoteProduct2->getKitItemLineItems()[$productKit1Item1->getId()];
        self::assertSame($productSimple2, $quoteProduct2KitItem1LineItem->getProduct());
        self::assertSame(56.789, $quoteProduct2KitItem1LineItem->getQuantity());

        // Checks quote product offers of quote product #2.
        self::assertCount(2, $quoteProduct2->getQuoteProductOffers());

        // Checks quote product offer #1 of quote product #2.
        $quoteProduct2Offer1 = $quoteProduct2->getQuoteProductOffers()[0];
        self::assertSame(11.0, $quoteProduct2Offer1->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct2Offer1->getProductUnit());
        self::assertEquals(Price::create(34.5678, 'USD'), $quoteProduct2Offer1->getPrice());

        // Checks quote product offer #2 of quote product #2.
        $quoteProduct2Offer2 = $quoteProduct2->getQuoteProductOffers()[1];
        self::assertSame(22.0, $quoteProduct2Offer2->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct2Offer2->getProductUnit());
        self::assertEquals(Price::create(30.1234, 'USD'), $quoteProduct2Offer2->getPrice());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSubmitExistingProductKit(): void
    {
        /** @var Quote $quoteEntity */
        $quoteEntity = $this->getReference('quote1');

        /** @var Product $productKit1 */
        $productKit1 = $this->getReference('product_kit1');
        $productUnitEach = $this->getReference('each');
        /** @var ProductKitItem $productKit1Item1 */
        $productKit1Item1 = $this->getReference('product_kit1_item1');
        /** @var ProductKitItem $productKit1Item2 */
        $productKit1Item2 = $this->getReference('product_kit1_item2');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference('product_simple1');
        /** @var Product $productSimple2 */
        $productSimple2 = $this->getReference('product_simple2');
        /** @var Product $productSimple3 */
        $productSimple3 = $this->getReference('product_simple3');
        /** @var User $user */
        $user = $this->getReference('user');
        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference('customer_user');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            QuoteType::class,
            $quoteEntity,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $shipUntil = new \DateTime('today +2 day');
        $validUntil = new \DateTime('today +2 day');

        $form->submit([
            'owner' => $user->getId(),
            'customerUser' => $customerUser->getId(),
            'customer' => $customer->getId(),
            'validUntil' => $validUntil->format('c'),
            'shippingMethodLocked' => false,
            'allowUnlistedShippingMethod' => true,
            'poNumber' => '13',
            'shipUntil' => $shipUntil->format('c'),
            'quoteProducts' => [
                [
                    'product' => $productKit1->getId(),
                    'kitItemLineItems' => [
                        $productKit1Item1->getId() => [
                            'product' => $productSimple1->getId(),
                            'quantity' => '45.6789',
                        ],
                        $productKit1Item2->getId() => [
                            'product' => $productSimple3->getId(),
                            'quantity' => '42',
                        ],
                    ],
                    'quoteProductOffers' => [
                        [
                            'quantity' => '111',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '42.5678',
                                'currency' => 'USD',
                            ],
                        ],
                        [
                            'quantity' => '222',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '78.9000',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'comment' => 'Sample comment 1',
                ],
                [
                    'product' => $productKit1->getId(),
                    'kitItemLineItems' => [
                        $productKit1Item1->getId() => [
                            'product' => $productSimple2->getId(),
                            'quantity' => '56.7890',
                        ],
                    ],
                    'quoteProductOffers' => [
                        [
                            'quantity' => '11',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '34.5678',
                                'currency' => 'USD',
                            ],
                        ],
                        [
                            'quantity' => '22',
                            'productUnit' => $productUnitEach->getCode(),
                            'price' => [
                                'value' => '30.1234',
                                'currency' => 'USD',
                            ],
                        ],
                    ],
                    'comment' => 'Sample comment 2',
                ],
            ],
            'assignedUsers' => $user->getId(),
            'assignedCustomerUsers' => $customerUser->getId(),
            'overriddenShippingCostAmount' => [
                'value' => 111.12,
                'currency' => 'USD',
            ],
            'shippingMethod' => 'shippingMethod1',
            'shippingMethodType' => 'shippingMethodType1',
            'estimatedShippingCostAmount' => 100,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(Quote::class, $form->getData());

        /** @var Quote $quoteEntity */
        $quoteEntity = $form->getData();

        // Checks quote entity.
        self::assertEquals($user->getId(), $quoteEntity->getOwner()?->getId());
        self::assertEquals($customer->getId(), $quoteEntity->getCustomer()?->getId());
        self::assertEquals($customerUser->getId(), $quoteEntity->getCustomerUser()?->getId());
        self::assertEquals($shipUntil, $quoteEntity->getShipUntil());
        self::assertEquals($validUntil, $quoteEntity->getValidUntil());
        self::assertEquals('13', $quoteEntity->getPoNumber());
        self::assertFalse($quoteEntity->isShippingMethodLocked());
        self::assertTrue($quoteEntity->isAllowUnlistedShippingMethod());
        self::assertEquals(Price::create(100, 'USD'), $quoteEntity->getEstimatedShippingCost());
        self::assertEquals(111.12, $quoteEntity->getOverriddenShippingCostAmount());
        self::assertEquals('USD', $quoteEntity->getCurrency());
        self::assertEquals('shippingMethod1', $quoteEntity->getShippingMethod());
        self::assertEquals('shippingMethodType1', $quoteEntity->getShippingMethodType());

        // Checks quote assigned users.
        self::assertCount(1, $quoteEntity->getAssignedUsers());

        // Checks quote assigned user #1.
        $quoteAssignedUser1 = $quoteEntity->getAssignedUsers()[0];
        self::assertSame($user, $quoteAssignedUser1);

        // Checks quote assigned customer users.
        self::assertCount(1, $quoteEntity->getAssignedCustomerUsers());

        // Checks quote assigned customer user #1.
        $quoteAssignedCustomerUser1 = $quoteEntity->getAssignedCustomerUsers()[0];
        self::assertSame($customerUser, $quoteAssignedCustomerUser1);

        // Checks quote products.
        self::assertCount(2, $quoteEntity->getQuoteProducts());

        // Checks quote product #1.
        $quoteProduct1 = $quoteEntity->getQuoteProducts()[0];
        self::assertSame($productKit1, $quoteProduct1->getProduct());
        self::assertEquals('Sample comment 1', $quoteProduct1->getComment());

        // Checks kit item line items of quote product #1.
        self::assertCount(2, $quoteProduct1->getKitItemLineItems());

        // Checks kit item line item #1 of quote product #1.
        self::assertTrue($quoteProduct1->getKitItemLineItems()->containsKey($productKit1Item1->getId()));
        /** @var QuoteProductKitItemLineItem $quoteProduct1KitItem1LineItem */
        $quoteProduct1KitItem1LineItem = $quoteProduct1->getKitItemLineItems()[$productKit1Item1->getId()];
        self::assertSame($productSimple1, $quoteProduct1KitItem1LineItem->getProduct());
        self::assertSame(45.6789, $quoteProduct1KitItem1LineItem->getQuantity());

        // Checks kit item line item #2 of quote product #2.
        self::assertTrue($quoteProduct1->getKitItemLineItems()->containsKey($productKit1Item2->getId()));
        /** @var QuoteProductKitItemLineItem $quoteProduct1KitItem2LineItem */
        $quoteProduct1KitItem2LineItem = $quoteProduct1->getKitItemLineItems()[$productKit1Item2->getId()];
        self::assertSame($productSimple3, $quoteProduct1KitItem2LineItem->getProduct());
        self::assertSame(42.0, $quoteProduct1KitItem2LineItem->getQuantity());

        // Checks quote product offers of quote product #1.
        self::assertCount(2, $quoteProduct1->getQuoteProductOffers());

        // Checks quote product offer #1 of quote product #1.
        $quoteProduct1Offer1 = $quoteProduct1->getQuoteProductOffers()[0];
        self::assertSame(111.0, $quoteProduct1Offer1->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct1Offer1->getProductUnit());
        self::assertEquals(Price::create(42.5678, 'USD'), $quoteProduct1Offer1->getPrice());

        // Checks quote product offer #2 of quote product #1.
        $quoteProduct1Offer2 = $quoteProduct1->getQuoteProductOffers()[1];
        self::assertSame(222.0, $quoteProduct1Offer2->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct1Offer1->getProductUnit());
        self::assertEquals(Price::create(78.9, 'USD'), $quoteProduct1Offer2->getPrice());

        // Checks quote product #2.
        $quoteProduct2 = $quoteEntity->getQuoteProducts()[1];
        self::assertSame($productKit1, $quoteProduct2->getProduct());
        self::assertEquals('Sample comment 2', $quoteProduct2->getComment());

        // Checks kit item line items of quote product #2.
        self::assertCount(1, $quoteProduct2->getKitItemLineItems());

        // Checks kit item line item #1 of quote product #2.
        self::assertTrue($quoteProduct1->getKitItemLineItems()->containsKey($productKit1Item1->getId()));
        /** @var QuoteProductKitItemLineItem $quoteProduct2KitItem1LineItem */
        $quoteProduct2KitItem1LineItem = $quoteProduct2->getKitItemLineItems()[$productKit1Item1->getId()];
        self::assertSame($productSimple2, $quoteProduct2KitItem1LineItem->getProduct());
        self::assertSame(56.789, $quoteProduct2KitItem1LineItem->getQuantity());

        // Checks quote product offers of quote product #2.
        self::assertCount(2, $quoteProduct2->getQuoteProductOffers());

        // Checks quote product offer #1 of quote product #2.
        $quoteProduct2Offer1 = $quoteProduct2->getQuoteProductOffers()[0];
        self::assertSame(11.0, $quoteProduct2Offer1->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct2Offer1->getProductUnit());
        self::assertEquals(Price::create(34.5678, 'USD'), $quoteProduct2Offer1->getPrice());

        // Checks quote product offer #2 of quote product #2.
        $quoteProduct2Offer2 = $quoteProduct2->getQuoteProductOffers()[1];
        self::assertSame(22.0, $quoteProduct2Offer2->getQuantity());
        self::assertSame($productUnitEach, $quoteProduct2Offer2->getProductUnit());
        self::assertEquals(Price::create(30.1234, 'USD'), $quoteProduct2Offer2->getPrice());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function assertQuoteFormFields(FormInterface $form): void
    {
        $authorizationChecker = self::getContainer()->get('security.authorization_checker');
        $isAllowPricesOverride = $authorizationChecker->isGranted('oro_quote_prices_override');
        $isAllowAddFreeFormItems = $authorizationChecker->isGranted('oro_quote_add_free_form_items');

        self::assertArrayIntersectEquals(
            [
                'data_class' => Quote::class,
                'allow_prices_override' => $isAllowPricesOverride,
                'allow_add_free_form_items' => $isAllowAddFreeFormItems,
                'validation_groups' => new GroupSequence([
                    Constraint::DEFAULT_GROUP,
                    'add_kit_item_line_item',
                    'quote_is_valid_for_sending_to_customer'
                ]),
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('qid'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('qid')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('owner'));
        self::assertArrayIntersectEquals(
            ['required' => true],
            $form->get('owner')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('customerUser'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('customerUser')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('customer'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('customer')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('validUntil'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('validUntil')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('shippingMethodLocked'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('shippingMethodLocked')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('allowUnlistedShippingMethod'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('allowUnlistedShippingMethod')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('poNumber'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('poNumber')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('shipUntil'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('shipUntil')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('quoteProducts'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'entry_options' => [
                    'compact_units' => true,
                    'allow_prices_override' => $isAllowPricesOverride,
                    'allow_add_free_form_items' => $isAllowAddFreeFormItems,
                    'currency' => $form->getData()?->getCurrency()
                ]
            ],
            $form->get('quoteProducts')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('assignedUsers'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('assignedUsers')->getConfig()->getOptions()
        );

        self::assertTrue($form->has('assignedCustomerUsers'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('assignedCustomerUsers')->getConfig()->getOptions()
        );
    }
}
