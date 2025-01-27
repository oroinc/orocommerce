<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressSelectType;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressType;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressValidatedAtType;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\AbstractLoadACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddressesACLData;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\FrontendBundle\Form\Type\CountryType;
use Oro\Bundle\FrontendBundle\Form\Type\RegionType;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

final class CheckoutAddressTypeTest extends FrontendWebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader()
        );

        $this->loadFixtures([LoadCheckoutACLData::class, LoadCustomerUserAddressesACLData::class]);
        $this->updateCustomerUserSecurityToken(AbstractLoadACLData::USER_ACCOUNT_1_ROLE_LOCAL);
        self::getContainer()->get('oro_order.order.provider.order_address')->reset();

        $requestStack = self::getContainer()->get('request_stack');

        $request = new Request();
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);
    }

    public function testCanBeCreatedWithEmptyInitialData(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $form = self::createForm();
        $form->add(
            'shippingAddress',
            CheckoutAddressType::class,
            ['object' => $checkout, 'addressType' => AddressType::TYPE_SHIPPING]
        );

        self::assertNull($form->getData());
    }

    public function testHasDefaultAddressFromAddressBookFromCheckout(): void
    {
        /** @var OrderAddressManager $addressManager */
        $addressManager = self::getContainer()->get('oro_order.manager.order_address');

        $customerUserAddress = $this->getReference(LoadCustomerUserAddressesACLData::ADDRESS_ACC_1_USER_LOCAL);
        $shippingAddress = $addressManager->updateFromAbstract($customerUserAddress);

        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $checkout->setShippingAddress($shippingAddress);

        $form = self::createForm(FormType::class, ['shippingAddress' => $shippingAddress]);
        $form->add(
            'shippingAddress',
            CheckoutAddressType::class,
            ['object' => $checkout, 'addressType' => AddressType::TYPE_SHIPPING]
        );

        self::assertSame(['shippingAddress' => $shippingAddress], $form->getData());
        self::assertSame(
            $shippingAddress,
            $form->get('shippingAddress')->get('customerAddress')->getData()
        );
        self::assertSame(
            'au_' . $customerUserAddress->getId(),
            $form->get('shippingAddress')->get('customerAddress')->getViewData()
        );
    }

    public function testHasDefaultAddressFromCheckout(): void
    {
        $shippingAddress = new OrderAddress();

        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $checkout->setShippingAddress($shippingAddress);

        $form = self::createForm(FormType::class, ['shippingAddress' => $shippingAddress]);
        $form->add(
            'shippingAddress',
            CheckoutAddressType::class,
            ['object' => $checkout, 'addressType' => AddressType::TYPE_SHIPPING]
        );

        self::assertSame(['shippingAddress' => $shippingAddress], $form->getData());
        self::assertSame(
            $shippingAddress,
            $form->get('shippingAddress')->get('customerAddress')->getData()
        );
        self::assertSame('', $form->get('shippingAddress')->get('customerAddress')->getViewData());
    }

    public function testHasFields(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $form = self::createForm();
        $form->add(
            'shippingAddress',
            CheckoutAddressType::class,
            ['object' => $checkout, 'addressType' => AddressType::TYPE_SHIPPING]
        );

        self::assertFormHasField(
            $form->get('shippingAddress'),
            'customerAddress',
            CheckoutAddressSelectType::class,
            [
                'object' => $checkout,
                'address_type' => AddressType::TYPE_SHIPPING,
                'required' => true,
                'mapped' => false,
            ]
        );

        self::assertFormHasField(
            $form->get('shippingAddress'),
            'phone',
            TextType::class,
            [
                'required' => false,
                StripTagsExtension::OPTION_NAME => true,
            ]
        );

        self::assertFormHasField(
            $form->get('shippingAddress'),
            'country',
            CountryType::class,
            [
                'required' => true,
                'label' => 'oro.address.country.label',
            ]
        );

        self::assertFormHasField(
            $form->get('shippingAddress'),
            'region',
            RegionType::class,
            [
                'required' => true,
                'label' => 'oro.address.region.label',
            ]
        );

        self::assertFormHasField(
            $form->get('shippingAddress'),
            'city',
            TextType::class,
            [
                'required' => false,
                'label' => 'oro.address.city.label',
                StripTagsExtension::OPTION_NAME => true,
            ]
        );

        self::assertFormHasField(
            $form->get('shippingAddress'),
            'postalCode',
            TextType::class,
            [
                'required' => false,
                'label' => 'oro.address.postal_code.label',
                StripTagsExtension::OPTION_NAME => true,
            ]
        );

        self::assertFormHasField(
            $form->get('shippingAddress'),
            'street',
            TextType::class,
            [
                'required' => false,
                'label' => 'oro.address.street.label',
                StripTagsExtension::OPTION_NAME => true,
            ]
        );

        self::assertFormHasField(
            $form->get('shippingAddress'),
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            [
                'checkout' => $checkout,
                'address_type' => AddressType::TYPE_SHIPPING,
            ]
        );
    }

    public function testIsDisabledWhenNotManuallyEnteredAddress(): void
    {
        /** @var OrderAddressManager $addressManager */
        $addressManager = self::getContainer()->get('oro_order.manager.order_address');

        $customerUserAddress = $this->getReference(LoadCustomerUserAddressesACLData::ADDRESS_ACC_1_USER_LOCAL);
        $shippingAddress = $addressManager->updateFromAbstract($customerUserAddress);

        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $checkout->setShippingAddress($shippingAddress);

        $form = self::createForm(FormType::class, ['shippingAddress' => $shippingAddress]);
        $form->add(
            'shippingAddress',
            CheckoutAddressType::class,
            ['object' => $checkout, 'addressType' => AddressType::TYPE_SHIPPING]
        );

        $form->submit(['shippingAddress' => ['customerAddress' => 'au_' . $customerUserAddress->getId()]]);

        /** @var FormInterface $child */
        foreach ($form->get('shippingAddress') as $child) {
            if ($child->getName() === 'customerAddress') {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }
    }

    public function testSubmitWithCustomerUserAddress(): void
    {
        /** @var OrderAddressManager $addressManager */
        $addressManager = self::getContainer()->get('oro_order.manager.order_address');
        $customerUserAddress = $this->getReference(LoadCustomerUserAddressesACLData::ADDRESS_ACC_1_USER_LOCAL);
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $form = self::createForm(FormType::class, null, ['validation_groups' => false]);
        $form->add(
            'shippingAddress',
            CheckoutAddressType::class,
            ['object' => $checkout, 'addressType' => AddressType::TYPE_SHIPPING]
        );

        $form->submit(['shippingAddress' => ['customerAddress' => 'au_' . $customerUserAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(
            ['shippingAddress' => $addressManager->updateFromAbstract($customerUserAddress)],
            $form->getData()
        );
    }

    public function testSubmitWithManuallyEnteredAddress(): void
    {
        /** @var OrderAddressManager $addressManager */
        $addressManager = self::getContainer()->get('oro_order.manager.order_address');
        $customerUserAddress = $this->getReference(LoadCustomerUserAddressesACLData::ADDRESS_ACC_1_USER_LOCAL);
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $form = self::createForm(FormType::class, null, ['validation_groups' => false]);
        $form->add(
            'shippingAddress',
            CheckoutAddressType::class,
            ['object' => $checkout, 'addressType' => AddressType::TYPE_SHIPPING]
        );

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        $form->submit([
            'shippingAddress' => [
                    'customerAddress' => CheckoutAddressSelectType::ENTER_MANUALLY,
                    'country' => $customerUserAddress->getCountryIso2(),
                    'region' => $customerUserAddress->getRegion()->getCombinedCode(),
                ] + $serializer->normalize($customerUserAddress),
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = $addressManager->updateFromAbstract($customerUserAddress);
        $expectedAddress->setCustomerUserAddress(null);
        $this->normalizeAddressEntity($expectedAddress);

        $actualAddress = $form->getData()['shippingAddress'];
        $this->normalizeAddressEntity($actualAddress);

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );

        /** @var FormInterface $child */
        foreach ($form->get('shippingAddress') as $child) {
            self::assertFalse(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected not to be disabled'
            );
        }
    }

    private function normalizeAddressEntity(AbstractAddress $address): void
    {
        ReflectionUtil::setPropertyValue($address, 'id', null);
        ReflectionUtil::setPropertyValue($address, 'extendEntityStorage', null);
        $address->setCreated(null);
        $address->setUpdated(null);
    }
}
