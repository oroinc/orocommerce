<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Entity\Request as RequestEntity;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\Type\RequestType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestTypeTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroRFPBundle/Tests/Functional/Form/Type/DataFixtures/RequestType.yml',
            '@OroRFPBundle/Tests/Functional/Form/Type/DataFixtures/RequestType.request.yml',
        ]);

        $request = Request::createFromGlobals();
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        self::getClientInstance()->getContainer()->get('request_stack')->push($request);
    }

    public function testCreateWhenNoData(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(RequestType::class, null, ['csrf_protection' => false]);

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestEntity::class,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('firstName'));
        self::assertArrayIntersectEquals(
            ['required' => true],
            $form->get('firstName')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('lastName'));
        self::assertArrayIntersectEquals(
            ['required' => true],
            $form->get('lastName')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('email'));
        self::assertArrayIntersectEquals(
            ['required' => true],
            $form->get('email')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('phone'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('phone')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('company'));
        self::assertArrayIntersectEquals(
            ['required' => true],
            $form->get('company')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('role'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('role')->getConfig()->getOptions()
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
        self::assertTrue($form->has('note'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('note')->getConfig()->getOptions()
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
        self::assertTrue($form->has('requestProducts'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'entry_options' => [
                    'compact_units' => true,
                ],
            ],
            $form->get('requestProducts')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('assignedCustomerUsers'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('assignedCustomerUsers')->getConfig()->getOptions()
        );

        $formView = $form->createView();
        self::assertContains('oro_rfp_request', $formView->vars['block_prefixes']);
    }

    public function testCreateWhenHasData(): void
    {
        /** @var RequestEntity $request */
        $requestEntity = $this->getReference('request1');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(RequestType::class, $requestEntity, ['csrf_protection' => false]);

        self::assertArrayIntersectEquals(
            [
                'data_class' => RequestEntity::class,
            ],
            $form->getConfig()->getOptions()
        );

        self::assertTrue($form->has('firstName'));
        self::assertArrayIntersectEquals(
            ['required' => true],
            $form->get('firstName')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('lastName'));
        self::assertArrayIntersectEquals(
            ['required' => true],
            $form->get('lastName')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('email'));
        self::assertArrayIntersectEquals(
            ['required' => true],
            $form->get('email')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('phone'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('phone')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('company'));
        self::assertArrayIntersectEquals(
            ['required' => true],
            $form->get('company')->getConfig()->getOptions()
        );
        self::assertTrue($form->has('role'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('role')->getConfig()->getOptions()
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
        self::assertTrue($form->has('note'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('note')->getConfig()->getOptions()
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
        self::assertTrue($form->has('requestProducts'));
        self::assertArrayIntersectEquals(
            [
                'required' => true,
                'entry_options' => [
                    'compact_units' => true,
                ],
            ],
            $form->get('requestProducts')->getConfig()->getOptions()
        );
        self::assertCount(2, $form->get('requestProducts'));
        self::assertCount(0, $form->get('requestProducts')[0]->get('kitItemLineItems'));
        self::assertCount(2, $form->get('requestProducts')[1]->get('kitItemLineItems'));

        self::assertTrue($form->has('assignedCustomerUsers'));
        self::assertArrayIntersectEquals(
            ['required' => false],
            $form->get('assignedCustomerUsers')->getConfig()->getOptions()
        );
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
        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference('customer_user');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $shipUntil = new \DateTime('today');

        $form->submit([
            'firstName' => 'Amanda',
            'lastName' => 'Cole',
            'email' => 'email@example.org',
            'phone' => '(555) 555-1234',
            'company' => 'Acme',
            'customer' => $customer->getId(),
            'customerUser' => $customerUser->getId(),
            'role' => 'Manager',
            'poNumber' => '42',
            'shipUntil' => $shipUntil->format('c'),
            'requestProducts' => [
                [
                    'product' => $productSimple1->getId(),
                    'requestProductItems' => [
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
                    'requestProductItems' => [
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
            'note' => 'Sample note',
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestEntity::class, $form->getData());

        /** @var RequestEntity $requestEntity */
        $requestEntity = $form->getData();

        // Checks request entity.
        self::assertEquals('Amanda', $requestEntity->getFirstName());
        self::assertEquals('Cole', $requestEntity->getLastName());
        self::assertEquals('email@example.org', $requestEntity->getEmail());
        self::assertEquals('(555) 555-1234', $requestEntity->getPhone());
        self::assertEquals('Acme', $requestEntity->getCompany());
        self::assertEquals($customer->getId(), $requestEntity->getCustomer()?->getId());
        self::assertEquals($customerUser->getId(), $requestEntity->getCustomerUser()?->getId());
        self::assertEquals('Manager', $requestEntity->getRole());
        self::assertEquals('42', $requestEntity->getPoNumber());
        self::assertEquals($shipUntil, $requestEntity->getShipUntil());
        self::assertEquals('Sample note', $requestEntity->getNote());

        // Checks request products.
        self::assertCount(2, $requestEntity->getRequestProducts());

        // Checks request product #1.
        $requestProduct1 = $requestEntity->getRequestProducts()[0];
        self::assertSame($productSimple1, $requestProduct1->getProduct());
        self::assertEquals('Sample comment 1', $requestProduct1->getComment());

        // Checks kit item line items of request product #1.
        self::assertCount(0, $requestProduct1->getKitItemLineItems());

        // Checks request product items of request product #1.
        self::assertCount(2, $requestProduct1->getRequestProductItems());

        // Checks request product item #1 of request product #1.
        $requestProduct1Item1 = $requestProduct1->getRequestProductItems()[0];
        self::assertSame(111.4567, $requestProduct1Item1->getQuantity());
        self::assertSame($productUnitItem, $requestProduct1Item1->getProductUnit());
        self::assertEquals(Price::create(42.5678, 'USD'), $requestProduct1Item1->getPrice());

        // Checks request product item #2 of request product #1.
        $requestProduct1Item2 = $requestProduct1->getRequestProductItems()[1];
        self::assertSame(222.5678, $requestProduct1Item2->getQuantity());
        self::assertSame($productUnitItem, $requestProduct1Item1->getProductUnit());
        self::assertEquals(Price::create(78.9, 'USD'), $requestProduct1Item2->getPrice());

        // Checks request product #2.
        $requestProduct2 = $requestEntity->getRequestProducts()[1];
        self::assertSame($productSimple3, $requestProduct2->getProduct());
        self::assertEquals('Sample comment 2', $requestProduct2->getComment());

        // Checks kit item line items of request product #2.
        self::assertCount(0, $requestProduct2->getKitItemLineItems());

        // Checks request product items of request product #2.
        self::assertCount(2, $requestProduct2->getRequestProductItems());

        // Checks request product item #1 of request product #2.
        $requestProduct2Item1 = $requestProduct2->getRequestProductItems()[0];
        self::assertSame(11.0, $requestProduct2Item1->getQuantity());
        self::assertSame($productUnitEach, $requestProduct2Item1->getProductUnit());
        self::assertEquals(Price::create(34.5678, 'USD'), $requestProduct2Item1->getPrice());

        // Checks request product item #2 of request product #2.
        $requestProduct2Item2 = $requestProduct2->getRequestProductItems()[1];
        self::assertSame(22.0, $requestProduct2Item2->getQuantity());
        self::assertSame($productUnitEach, $requestProduct2Item2->getProductUnit());
        self::assertEquals(Price::create(30.1234, 'USD'), $requestProduct2Item2->getPrice());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSubmitExistingWithProductSimple(): void
    {
        /** @var RequestEntity $requestEntity */
        $requestEntity = $this->getReference('request1');

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
        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference('customer_user');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestType::class,
            $requestEntity,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $shipUntil = new \DateTime('today +1 day');

        $form->submit([
            'firstName' => 'Amanda',
            'lastName' => 'Cole',
            'email' => 'email@example.org',
            'phone' => '(555) 555-1234',
            'company' => 'Acme',
            'customer' => $customer->getId(),
            'customerUser' => $customerUser->getId(),
            'role' => 'Manager',
            'poNumber' => '42',
            'shipUntil' => $shipUntil->format('c'),
            'requestProducts' => [
                [
                    'product' => $productSimple1->getId(),
                    'requestProductItems' => [
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
                    'requestProductItems' => [
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
            'note' => 'Sample note',
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestEntity::class, $form->getData());

        // Checks request entity.
        self::assertEquals('Amanda', $requestEntity->getFirstName());
        self::assertEquals('Cole', $requestEntity->getLastName());
        self::assertEquals('email@example.org', $requestEntity->getEmail());
        self::assertEquals('(555) 555-1234', $requestEntity->getPhone());
        self::assertEquals('Acme', $requestEntity->getCompany());
        self::assertEquals($customer->getId(), $requestEntity->getCustomer()?->getId());
        self::assertEquals($customerUser->getId(), $requestEntity->getCustomerUser()?->getId());
        self::assertEquals('Manager', $requestEntity->getRole());
        self::assertEquals('42', $requestEntity->getPoNumber());
        self::assertEquals($shipUntil, $requestEntity->getShipUntil());
        self::assertEquals('Sample note', $requestEntity->getNote());

        // Checks request products.
        self::assertCount(2, $requestEntity->getRequestProducts());

        // Checks request product #1.
        $requestProduct1 = $requestEntity->getRequestProducts()[0];
        self::assertSame($productSimple1, $requestProduct1->getProduct());
        self::assertEquals('Sample comment 1', $requestProduct1->getComment());

        // Checks kit item line items of request product #1.
        self::assertCount(0, $requestProduct1->getKitItemLineItems());

        // Checks request product items of request product #1.
        self::assertCount(2, $requestProduct1->getRequestProductItems());

        // Checks request product item #1 of request product #1.
        $requestProduct1Item1 = $requestProduct1->getRequestProductItems()[0];
        self::assertSame(111.4567, $requestProduct1Item1->getQuantity());
        self::assertSame($productUnitItem, $requestProduct1Item1->getProductUnit());
        self::assertEquals(Price::create(42.5678, 'USD'), $requestProduct1Item1->getPrice());

        // Checks request product item #2 of request product #1.
        $requestProduct1Item2 = $requestProduct1->getRequestProductItems()[1];
        self::assertSame(222.5678, $requestProduct1Item2->getQuantity());
        self::assertSame($productUnitItem, $requestProduct1Item1->getProductUnit());
        self::assertEquals(Price::create(78.9, 'USD'), $requestProduct1Item2->getPrice());

        // Checks request product #2.
        $requestProduct2 = $requestEntity->getRequestProducts()[1];
        self::assertSame($productSimple3, $requestProduct2->getProduct());
        self::assertEquals('Sample comment 2', $requestProduct2->getComment());

        // Checks kit item line items of request product #2.
        self::assertCount(0, $requestProduct2->getKitItemLineItems());

        // Checks request product items of request product #2.
        self::assertCount(2, $requestProduct2->getRequestProductItems());

        // Checks request product item #1 of request product #2.
        $requestProduct2Item1 = $requestProduct2->getRequestProductItems()[0];
        self::assertSame(11.0, $requestProduct2Item1->getQuantity());
        self::assertSame($productUnitEach, $requestProduct2Item1->getProductUnit());
        self::assertEquals(Price::create(34.5678, 'USD'), $requestProduct2Item1->getPrice());

        // Checks request product item #2 of request product #2.
        $requestProduct2Item2 = $requestProduct2->getRequestProductItems()[1];
        self::assertSame(22.0, $requestProduct2Item2->getQuantity());
        self::assertSame($productUnitEach, $requestProduct2Item2->getProductUnit());
        self::assertEquals(Price::create(30.1234, 'USD'), $requestProduct2Item2->getPrice());
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
        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference('customer_user');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestType::class,
            null,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $shipUntil = new \DateTime('today');

        $form->submit([
            'firstName' => 'Amanda',
            'lastName' => 'Cole',
            'email' => 'email@example.org',
            'phone' => '(555) 555-1234',
            'company' => 'Acme',
            'customer' => $customer->getId(),
            'customerUser' => $customerUser->getId(),
            'role' => 'Manager',
            'poNumber' => '42',
            'shipUntil' => $shipUntil->format('c'),
            'requestProducts' => [
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
                    'requestProductItems' => [
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
                    'requestProductItems' => [
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
            'note' => 'Sample note',
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertInstanceOf(RequestEntity::class, $form->getData());

        /** @var RequestEntity $requestEntity */
        $requestEntity = $form->getData();

        // Checks request entity.
        self::assertEquals('Amanda', $requestEntity->getFirstName());
        self::assertEquals('Cole', $requestEntity->getLastName());
        self::assertEquals('email@example.org', $requestEntity->getEmail());
        self::assertEquals('(555) 555-1234', $requestEntity->getPhone());
        self::assertEquals('Acme', $requestEntity->getCompany());
        self::assertEquals($customer->getId(), $requestEntity->getCustomer()?->getId());
        self::assertEquals($customerUser->getId(), $requestEntity->getCustomerUser()?->getId());
        self::assertEquals('Manager', $requestEntity->getRole());
        self::assertEquals('42', $requestEntity->getPoNumber());
        self::assertEquals($shipUntil, $requestEntity->getShipUntil());
        self::assertEquals('Sample note', $requestEntity->getNote());

        // Checks request products.
        self::assertCount(2, $requestEntity->getRequestProducts());

        // Checks request product #1.
        $requestProduct1 = $requestEntity->getRequestProducts()[0];
        self::assertSame($productKit1, $requestProduct1->getProduct());
        self::assertEquals('Sample comment 1', $requestProduct1->getComment());

        // Checks kit item line items of request product #1.
        self::assertCount(2, $requestProduct1->getKitItemLineItems());

        // Checks kit item line item #1 of request product #1.
        self::assertTrue($requestProduct1->getKitItemLineItems()->containsKey($productKit1Item1->getId()));
        /** @var RequestProductKitItemLineItem $requestProduct1KitItem1LineItem */
        $requestProduct1KitItem1LineItem = $requestProduct1->getKitItemLineItems()[$productKit1Item1->getId()];
        self::assertSame($productSimple1, $requestProduct1KitItem1LineItem->getProduct());
        self::assertSame(45.6789, $requestProduct1KitItem1LineItem->getQuantity());

        // Checks kit item line item #2 of request product #2.
        self::assertTrue($requestProduct1->getKitItemLineItems()->containsKey($productKit1Item2->getId()));
        /** @var RequestProductKitItemLineItem $requestProduct1KitItem2LineItem */
        $requestProduct1KitItem2LineItem = $requestProduct1->getKitItemLineItems()[$productKit1Item2->getId()];
        self::assertSame($productSimple3, $requestProduct1KitItem2LineItem->getProduct());
        self::assertSame(42.0, $requestProduct1KitItem2LineItem->getQuantity());

        // Checks request product items of request product #1.
        self::assertCount(2, $requestProduct1->getRequestProductItems());

        // Checks request product item #1 of request product #1.
        $requestProduct1Item1 = $requestProduct1->getRequestProductItems()[0];
        self::assertSame(111.0, $requestProduct1Item1->getQuantity());
        self::assertSame($productUnitEach, $requestProduct1Item1->getProductUnit());
        self::assertEquals(Price::create(42.5678, 'USD'), $requestProduct1Item1->getPrice());

        // Checks request product item #2 of request product #1.
        $requestProduct1Item2 = $requestProduct1->getRequestProductItems()[1];
        self::assertSame(222.0, $requestProduct1Item2->getQuantity());
        self::assertSame($productUnitEach, $requestProduct1Item1->getProductUnit());
        self::assertEquals(Price::create(78.9, 'USD'), $requestProduct1Item2->getPrice());

        // Checks request product #2.
        $requestProduct2 = $requestEntity->getRequestProducts()[1];
        self::assertSame($productKit1, $requestProduct2->getProduct());
        self::assertEquals('Sample comment 2', $requestProduct2->getComment());

        // Checks kit item line items of request product #2.
        self::assertCount(1, $requestProduct2->getKitItemLineItems());

        // Checks kit item line item #1 of request product #2.
        self::assertTrue($requestProduct1->getKitItemLineItems()->containsKey($productKit1Item1->getId()));
        /** @var RequestProductKitItemLineItem $requestProduct2KitItem1LineItem */
        $requestProduct2KitItem1LineItem = $requestProduct2->getKitItemLineItems()[$productKit1Item1->getId()];
        self::assertSame($productSimple2, $requestProduct2KitItem1LineItem->getProduct());
        self::assertSame(56.789, $requestProduct2KitItem1LineItem->getQuantity());

        // Checks request product items of request product #2.
        self::assertCount(2, $requestProduct2->getRequestProductItems());

        // Checks request product item #1 of request product #2.
        $requestProduct2Item1 = $requestProduct2->getRequestProductItems()[0];
        self::assertSame(11.0, $requestProduct2Item1->getQuantity());
        self::assertSame($productUnitEach, $requestProduct2Item1->getProductUnit());
        self::assertEquals(Price::create(34.5678, 'USD'), $requestProduct2Item1->getPrice());

        // Checks request product item #2 of request product #2.
        $requestProduct2Item2 = $requestProduct2->getRequestProductItems()[1];
        self::assertSame(22.0, $requestProduct2Item2->getQuantity());
        self::assertSame($productUnitEach, $requestProduct2Item2->getProductUnit());
        self::assertEquals(Price::create(30.1234, 'USD'), $requestProduct2Item2->getPrice());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSubmitExistingProductKit(): void
    {
        /** @var RequestEntity $requestEntity */
        $requestEntity = $this->getReference('request1');

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
        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference('customer_user');

        $formFactory = self::getContainer()->get('form.factory');
        $form = $formFactory->create(
            RequestType::class,
            $requestEntity,
            ['csrf_protection' => false, 'validation_groups' => false]
        );

        $shipUntil = new \DateTime('today +2 day');

        $form->submit([
            'firstName' => 'Amanda',
            'lastName' => 'Cole',
            'email' => 'email@example.org',
            'phone' => '(555) 555-1234',
            'company' => 'Acme',
            'customer' => $customer->getId(),
            'customerUser' => $customerUser->getId(),
            'role' => 'Manager',
            'poNumber' => '42',
            'shipUntil' => $shipUntil->format('c'),
            'requestProducts' => [
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
                    'requestProductItems' => [
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
                    'requestProductItems' => [
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
            'note' => 'Sample note',
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        // Checks request entity.
        self::assertEquals('Amanda', $requestEntity->getFirstName());
        self::assertEquals('Cole', $requestEntity->getLastName());
        self::assertEquals('email@example.org', $requestEntity->getEmail());
        self::assertEquals('(555) 555-1234', $requestEntity->getPhone());
        self::assertEquals('Acme', $requestEntity->getCompany());
        self::assertEquals($customer->getId(), $requestEntity->getCustomer()?->getId());
        self::assertEquals($customerUser->getId(), $requestEntity->getCustomerUser()?->getId());
        self::assertEquals('Manager', $requestEntity->getRole());
        self::assertEquals('42', $requestEntity->getPoNumber());
        self::assertEquals($shipUntil, $requestEntity->getShipUntil());
        self::assertEquals('Sample note', $requestEntity->getNote());

        // Checks request products.
        self::assertCount(2, $requestEntity->getRequestProducts());

        // Checks request product #1.
        $requestProduct1 = $requestEntity->getRequestProducts()[0];
        self::assertSame($productKit1, $requestProduct1->getProduct());
        self::assertEquals('Sample comment 1', $requestProduct1->getComment());

        // Checks kit item line items of request product #1.
        self::assertCount(2, $requestProduct1->getKitItemLineItems());

        // Checks kit item line item #1 of request product #1.
        self::assertTrue($requestProduct1->getKitItemLineItems()->containsKey($productKit1Item1->getId()));
        /** @var RequestProductKitItemLineItem $requestProduct1KitItem1LineItem */
        $requestProduct1KitItem1LineItem = $requestProduct1->getKitItemLineItems()[$productKit1Item1->getId()];
        self::assertSame($productSimple1, $requestProduct1KitItem1LineItem->getProduct());
        self::assertSame(45.6789, $requestProduct1KitItem1LineItem->getQuantity());

        // Checks kit item line item #2 of request product #2.
        self::assertTrue($requestProduct1->getKitItemLineItems()->containsKey($productKit1Item2->getId()));
        /** @var RequestProductKitItemLineItem $requestProduct1KitItem2LineItem */
        $requestProduct1KitItem2LineItem = $requestProduct1->getKitItemLineItems()[$productKit1Item2->getId()];
        self::assertSame($productSimple3, $requestProduct1KitItem2LineItem->getProduct());
        self::assertSame(42.0, $requestProduct1KitItem2LineItem->getQuantity());

        // Checks request product items of request product #1.
        self::assertCount(2, $requestProduct1->getRequestProductItems());

        // Checks request product item #1 of request product #1.
        $requestProduct1Item1 = $requestProduct1->getRequestProductItems()[0];
        self::assertSame(111.0, $requestProduct1Item1->getQuantity());
        self::assertSame($productUnitEach, $requestProduct1Item1->getProductUnit());
        self::assertEquals(Price::create(42.5678, 'USD'), $requestProduct1Item1->getPrice());

        // Checks request product item #2 of request product #1.
        $requestProduct1Item2 = $requestProduct1->getRequestProductItems()[1];
        self::assertSame(222.0, $requestProduct1Item2->getQuantity());
        self::assertSame($productUnitEach, $requestProduct1Item1->getProductUnit());
        self::assertEquals(Price::create(78.9, 'USD'), $requestProduct1Item2->getPrice());

        // Checks request product #2.
        $requestProduct2 = $requestEntity->getRequestProducts()[1];
        self::assertSame($productKit1, $requestProduct2->getProduct());
        self::assertEquals('Sample comment 2', $requestProduct2->getComment());

        // Checks kit item line items of request product #2.
        self::assertCount(1, $requestProduct2->getKitItemLineItems());

        // Checks kit item line item #1 of request product #2.
        self::assertTrue($requestProduct1->getKitItemLineItems()->containsKey($productKit1Item1->getId()));
        /** @var RequestProductKitItemLineItem $requestProduct2KitItem1LineItem */
        $requestProduct2KitItem1LineItem = $requestProduct2->getKitItemLineItems()[$productKit1Item1->getId()];
        self::assertSame($productSimple2, $requestProduct2KitItem1LineItem->getProduct());
        self::assertSame(56.789, $requestProduct2KitItem1LineItem->getQuantity());

        // Checks request product items of request product #2.
        self::assertCount(2, $requestProduct2->getRequestProductItems());

        // Checks request product item #1 of request product #2.
        $requestProduct2Item1 = $requestProduct2->getRequestProductItems()[0];
        self::assertSame(11.0, $requestProduct2Item1->getQuantity());
        self::assertSame($productUnitEach, $requestProduct2Item1->getProductUnit());
        self::assertEquals(Price::create(34.5678, 'USD'), $requestProduct2Item1->getPrice());

        // Checks request product item #2 of request product #2.
        $requestProduct2Item2 = $requestProduct2->getRequestProductItems()[1];
        self::assertSame(22.0, $requestProduct2Item2->getQuantity());
        self::assertSame($productUnitEach, $requestProduct2Item2->getProductUnit());
        self::assertEquals(Price::create(30.1234, 'USD'), $requestProduct2Item2->getPrice());
    }
}
