<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Form\Type\AddressValidatedAtType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressSelectType;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressType;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderAddressData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @dbIsolationPerTest
 */
final class OrderAddressTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader()
        );
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([
            LoadOrderAddressData::class,
            LoadCustomerUserAddresses::class,
            LoadCustomerAddresses::class,
        ]);
    }

    public function testCanBeCreatedWithEmptyInitialData(): void
    {
        $form = self::createForm();
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertNull($form->getData());
    }

    public function testDefaultAddressIsSetWhenInitialDataIsNull(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var CustomerUserAddress $defaultCustomerUserAddress */
        $defaultCustomerUserAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $form = self::createForm(FormType::class, ['orderAddress' => null]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        /** @var OrderAddress $orderAddress */
        $orderAddress = $form->get('orderAddress')->getData();

        // When initial data is null, the form should set the default address from the address collection
        self::assertNotNull($orderAddress);
        self::assertSame(
            $defaultCustomerUserAddress,
            $form->get('orderAddress')->get('customerAddress')->getData()
        );
        // The order address should be populated with data from the default customer user address
        self::assertEquals($orderAddressManager->updateFromAbstract($defaultCustomerUserAddress), $orderAddress);
    }

    public function testDefaultCustomerUserAddressIsSelectedForShippingType(): void
    {
        /** @var CustomerUserAddress $defaultShippingAddress */
        $defaultShippingAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $form = self::createForm(FormType::class, ['orderAddress' => null]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        // The default shipping address should be selected in the customerAddress dropdown
        self::assertSame(
            $defaultShippingAddress,
            $form->get('orderAddress')->get('customerAddress')->getData()
        );
    }

    public function testDefaultCustomerUserAddressIsSelectedForBillingType(): void
    {
        /** @var CustomerUserAddress $defaultBillingAddress */
        $defaultBillingAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_2');

        $form = self::createForm(FormType::class, ['orderAddress' => null]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_BILLING]
        );

        // The default billing address should be selected in the customerAddress dropdown
        self::assertSame(
            $defaultBillingAddress,
            $form->get('orderAddress')->get('customerAddress')->getData()
        );
    }

    public function testCanBeCreatedWithOrderAddressInitialData(): void
    {
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress]);
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertSame(['orderAddress' => $orderAddress], $form->getData());
        self::assertSame(
            OrderAddressSelectType::ENTER_MANUALLY,
            $form->get('orderAddress')->get('customerAddress')->getData()
        );
    }

    public function testCanBeCreatedWithCustomerUserAddressInitialData(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        $customerUserAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_1');

        /** @var OrderAddress $orderAddress */
        $orderAddress = $orderAddressManager->updateFromAbstract($customerUserAddress);

        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress]);
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertSame(['orderAddress' => $orderAddress], $form->getData());
        self::assertSame(
            $customerUserAddress,
            $form->get('orderAddress')->get('customerAddress')->getData()
        );
    }

    public function testCanBeCreatedWithCustomerAddressInitialData(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var CustomerAddress $customerAddress */
        $customerAddress = $this->getReference('customer.level_1.address_1');

        /** @var OrderAddress $orderAddress */
        $orderAddress = $orderAddressManager->updateFromAbstract($customerAddress);

        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertSame(['orderAddress' => $orderAddress], $form->getData());
        self::assertSame(
            $customerAddress,
            $form->get('orderAddress')->get('customerAddress')->getData()
        );
    }

    public function testHasFields(): void
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertFormHasField($form->get('orderAddress'), 'customerAddress', OrderAddressSelectType::class, [
            'order' => $order,
            'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING,
            'required' => false,
            'mapped' => false,
        ]);

        self::assertFormHasField(
            $form->get('orderAddress'),
            'phone',
            TextType::class,
            [
                'required' => false,
                StripTagsExtension::OPTION_NAME => true,
            ]
        );

        self::assertFormHasField($form->get('orderAddress'), 'validatedAt', AddressValidatedAtType::class);
    }

    public function testHasFieldsWithBillingAddressType(): void
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_BILLING]
        );

        self::assertFormHasField($form->get('orderAddress'), 'customerAddress', OrderAddressSelectType::class, [
            'order' => $order,
            'address_type' => OrderAddressProvider::ADDRESS_TYPE_BILLING,
            'required' => false,
            'mapped' => false,
        ]);

        self::assertFormHasField(
            $form->get('orderAddress'),
            'phone',
            TextType::class,
            [
                'required' => false,
                StripTagsExtension::OPTION_NAME => true,
            ]
        );

        self::assertFormHasField($form->get('orderAddress'), 'validatedAt', AddressValidatedAtType::class);
    }

    public function testFieldsAreEnabledForNewAddress(): void
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        /** @var FormInterface $child */
        foreach ($form->get('orderAddress') as $child) {
            self::assertFalse(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be enabled for manually entered address'
            );
        }
    }

    public function testIsDisabledWhenCustomerAddressSelected(): void
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        /** @var CustomerAddress $customerAddress */
        $customerAddress = $this->getReference('customer.level_1.address_1');

        $orderAddress->setCustomerAddress($customerAddress);

        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        /** @var FormInterface $child */
        foreach ($form->get('orderAddress') as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'], true)) {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }

        $orderAddress->setCustomerAddress(null);
    }

    public function testIsDisabledWhenNotNewAddress(): void
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $orderAddress->setCustomerUserAddress($customerUserAddress);

        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        /** @var FormInterface $child */
        foreach ($form->get('orderAddress') as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'])) {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }

        $orderAddress->setCustomerUserAddress(null);
    }

    public function testSubmitWithEmptyDataWhenEmptyInitialData(): void
    {
        $form = self::createForm(FormType::class, null, ['validation_groups' => false]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        // Sets customer and customer user to null to prevent setting default address as initial data.
        $order->setCustomer(null);
        $order->setCustomerUser(null);

        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit([]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(['orderAddress' => new OrderAddress()], $form->getData());
    }

    public function testSubmitWithCustomerUserAddressDataWhenEmptyInitialData(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $form = self::createForm(FormType::class, null, ['validation_groups' => false]);
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit(['orderAddress' => ['customerAddress' => 'au_' . $customerUserAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(
            ['orderAddress' => $orderAddressManager->updateFromAbstract($customerUserAddress)],
            $form->getData()
        );

        /** @var FormInterface $child */
        foreach ($form->get('orderAddress') as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'], true)) {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }
    }

    public function testSubmitWithCustomerAddressDataWhenEmptyInitialData(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var CustomerAddress $customerAddress */
        $customerAddress = $this->getReference('customer.level_1.address_1');

        $form = self::createForm(FormType::class, null, ['validation_groups' => false]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit(['orderAddress' => ['customerAddress' => 'a_' . $customerAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(
            ['orderAddress' => $orderAddressManager->updateFromAbstract($customerAddress)],
            $form->getData()
        );

        /** @var FormInterface $child */
        foreach ($form->get('orderAddress') as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'], true)) {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }
    }

    public function testSubmitWithManuallyEnteredAddressDataWhenEmptyInitialData(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $form = self::createForm(FormType::class, null, ['validation_groups' => false]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        $form->submit([
            'orderAddress' => [
                    'customerAddress' => OrderAddressSelectType::ENTER_MANUALLY,
                    'country' => $customerUserAddress->getCountryIso2(),
                    'region' => $customerUserAddress->getRegion()->getCombinedCode(),
                ] + $serializer->normalize($customerUserAddress),
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = $orderAddressManager->updateFromAbstract($customerUserAddress);
        $expectedAddress->setCustomerUserAddress(null);
        $this->normalizeAddressEntity($expectedAddress);

        $actualAddress = $form->getData()['orderAddress'];
        $this->normalizeAddressEntity($actualAddress);

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );

        /** @var FormInterface $child */
        foreach ($form->get('orderAddress') as $child) {
            self::assertFalse(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected not to be disabled'
            );
        }
    }

    public function testSubmitWithCustomerUserAddressDataWhenManuallyEnteredInitialData(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_1');
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        $form = self::createForm(
            FormType::class,
            ['orderAddress' => clone $orderAddress],
            ['validation_groups' => false]
        );
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit(['orderAddress' => ['customerAddress' => 'au_' . $customerUserAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = $orderAddressManager->updateFromAbstract($customerUserAddress, clone $orderAddress);
        $this->normalizeAddressEntity($expectedAddress);

        $actualAddress = $form->getData()['orderAddress'];
        $this->normalizeAddressEntity($actualAddress);

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );

        /** @var FormInterface $child */
        foreach ($form->get('orderAddress') as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'], true)) {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }
    }

    public function testSubmitWithCustomerAddressDataWhenManuallyEnteredInitialData(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var CustomerAddress $customerAddress */
        $customerAddress = $this->getReference('customer.level_1.address_1');
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        $form = self::createForm(
            FormType::class,
            ['orderAddress' => clone $orderAddress],
            ['validation_groups' => false]
        );
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit(['orderAddress' => ['customerAddress' => 'a_' . $customerAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = $orderAddressManager->updateFromAbstract($customerAddress, clone $orderAddress);
        $this->normalizeAddressEntity($expectedAddress);

        $actualAddress = $form->getData()['orderAddress'];
        $this->normalizeAddressEntity($actualAddress);

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );

        /** @var FormInterface $child */
        foreach ($form->get('orderAddress') as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'], true)) {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }
    }

    public function testSubmitWithCustomerUserAddressDataWhenCustomerUserAddressDataInitialData(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        // address_1 has billing => false, address_2 has billing => true
        // We use billing type to test switching from address_1 (default) to address_2
        /** @var CustomerUserAddress $initialAddress */
        $initialAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_1');
        /** @var CustomerUserAddress $newAddress */
        $newAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_2');

        $orderAddress = $orderAddressManager->updateFromAbstract($initialAddress);

        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress], ['validation_groups' => false]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_BILLING]
        );

        $form->submit(['orderAddress' => ['customerAddress' => 'au_' . $newAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        /** @var OrderAddress $actualAddress */
        $actualAddress = $form->getData()['orderAddress'];

        // Verify the address was updated from the new customer user address
        self::assertNotNull($actualAddress);
        self::assertSame($newAddress->getStreet(), $actualAddress->getStreet());
        self::assertSame($newAddress->getCity(), $actualAddress->getCity());
        self::assertSame($newAddress, $actualAddress->getCustomerUserAddress());
    }

    public function testSubmitAddressIsNotChangedIfCustomerUserAddressIdIsSame(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.address_1');
        $orderAddress = $orderAddressManager->updateFromAbstract($customerUserAddress);
        $orderAddress->setStreet('Overridden street that should not be changed');

        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress], ['validation_groups' => false]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        $form->submit(
            [
                'orderAddress' => ['customerAddress' => 'au_' . $customerUserAddress->getId()],
            ],
            false
        );

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = clone $orderAddress;
        $this->normalizeAddressEntity($orderAddress);

        $actualAddress = $form->getData()['orderAddress'];
        $this->normalizeAddressEntity($actualAddress);

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );

        self::assertEquals('Overridden street that should not be changed', $actualAddress->getStreet());
    }

    public function testSubmitWithCustomerAddressDataWhenCustomerAddressDataInitialData(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        // address_1 has billing => false, address_2 has billing => true
        // We use billing type to test switching from address_1 (default) to address_2
        /** @var CustomerAddress $initialAddress */
        $initialAddress = $this->getReference('customer.level_1.address_1');
        /** @var CustomerAddress $newAddress */
        $newAddress = $this->getReference('customer.level_1.address_2');

        $orderAddress = $orderAddressManager->updateFromAbstract($initialAddress);

        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress], ['validation_groups' => false]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_BILLING]
        );

        $form->submit(['orderAddress' => ['customerAddress' => 'a_' . $newAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        /** @var OrderAddress $actualAddress */
        $actualAddress = $form->getData()['orderAddress'];

        // Verify the address was updated from the new customer address
        self::assertNotNull($actualAddress);
        self::assertSame($newAddress->getStreet(), $actualAddress->getStreet());
        self::assertSame($newAddress->getCity(), $actualAddress->getCity());
        self::assertSame($newAddress, $actualAddress->getCustomerAddress());
    }

    public function testSubmitAddressIsNotChangedIfCustomerAddressIdIsSame(): void
    {
        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var CustomerAddress $customerAddress */
        $customerAddress = $this->getReference('customer.level_1.address_1');
        $orderAddress = $orderAddressManager->updateFromAbstract($customerAddress);
        $orderAddress->setStreet('Overridden street that should not be changed');

        $form = self::createForm(FormType::class, ['orderAddress' => $orderAddress], ['validation_groups' => false]);
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $form->add(
            'orderAddress',
            OrderAddressType::class,
            ['order' => $order, 'address_type' => OrderAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        $form->submit(
            [
                'orderAddress' => ['customerAddress' => 'a_' . $customerAddress->getId()],
            ],
            false
        );

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = clone $orderAddress;
        $this->normalizeAddressEntity($orderAddress);

        $actualAddress = $form->getData()['orderAddress'];
        $this->normalizeAddressEntity($actualAddress);

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );

        self::assertEquals('Overridden street that should not be changed', $actualAddress->getStreet());
    }

    private function normalizeAddressEntity(AbstractAddress $address): void
    {
        ReflectionUtil::setPropertyValue($address, 'id', null);
        ReflectionUtil::setPropertyValue($address, 'extendEntityStorage', null);
        $address->setCreated(null);
        $address->setUpdated(null);
    }
}
