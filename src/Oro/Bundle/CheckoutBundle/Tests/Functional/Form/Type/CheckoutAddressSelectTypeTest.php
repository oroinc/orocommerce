<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressSelectType;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\AbstractLoadACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddressesACLData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\FormType;

final class CheckoutAddressSelectTypeTest extends FrontendWebTestCase
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
    }

    public function testHasDefaultAddressFromAddressCollection(): void
    {
        /** @var OrderAddressManager $addressManager */
        $addressManager = self::getContainer()->get('oro_order.manager.order_address');

        $form = self::createForm();
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $form->add(
            'shippingAddress',
            CheckoutAddressSelectType::class,
            ['object' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $addressCollection = $addressManager->getGroupedAddresses(
            $checkout,
            AddressType::TYPE_SHIPPING,
            'oro.checkout.'
        );

        self::assertEquals($addressCollection->getDefaultAddressKey(), $form->get('shippingAddress')->getData());
    }

    public function testHasDefaultAddressFromCheckout(): void
    {
        /** @var OrderAddressManager $addressManager */
        $addressManager = self::getContainer()->get('oro_order.manager.order_address');

        $customerUserAddress = $this->getReference(LoadCustomerUserAddressesACLData::ADDRESS_ACC_1_USER_LOCAL);
        $shippingAddress = $addressManager->updateFromAbstract($customerUserAddress);

        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $checkout->setShippingAddress($shippingAddress);

        $form = self::createForm();
        $form->add(
            'shippingAddress',
            CheckoutAddressSelectType::class,
            ['object' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        self::assertEquals($shippingAddress, $form->get('shippingAddress')->getData());
    }

    public function testHasOptions(): void
    {
        /** @var OrderAddressManager $addressManager */
        $addressManager = self::getContainer()->get('oro_order.manager.order_address');

        $form = self::createForm();
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $form->add(
            'shippingAddress',
            CheckoutAddressSelectType::class,
            ['object' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $addressCollection = $addressManager->getGroupedAddresses(
            $checkout,
            AddressType::TYPE_SHIPPING,
            'oro.checkout.'
        );

        self::assertFormHasField($form, 'shippingAddress', CheckoutAddressSelectType::class, [
            'data_class' => null,
            'label' => false,
            'configs' => [
                'placeholder' => 'oro.checkout.form.address.choose',
            ],
            'address_collection' => $addressCollection,
            'address_type' => AddressType::TYPE_SHIPPING,
            'object' => $checkout,
        ]);
    }

    public function testHasViewVars(): void
    {
        $form = self::createForm();
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $form->add(
            'shippingAddress',
            CheckoutAddressSelectType::class,
            ['object' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $formView = $form->createView();

        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        $addressCollection = $orderAddressManager->getGroupedAddresses(
            $checkout,
            AddressType::TYPE_SHIPPING,
            'oro.checkout.'
        );

        $plainAddresses = [];
        array_walk_recursive($addressCollection, static function ($item, $key) use (&$plainAddresses, $serializer) {
            if ($item instanceof AbstractAddress) {
                $plainAddresses[$key] = $serializer->normalize($item);
            }
        });

        self::assertEquals(json_encode($plainAddresses), $formView['shippingAddress']->vars['attr']['data-addresses']);
        self::assertEquals(
            json_encode($orderAddressManager->getAddressTypes($addressCollection->toArray(), 'oro.checkout.')),
            $formView['shippingAddress']->vars['attr']['data-addresses-types']
        );
        self::assertEquals(
            'oro.checkout.form.address.select.shipping.label.short',
            $formView['shippingAddress']->vars['label']
        );
        self::assertSame($checkout, $formView['shippingAddress']->vars['checkout']);
        self::assertEquals($checkout->getId(), $formView['shippingAddress']->vars['checkoutId']);
    }

    public function testHasManualAddressInAddressesViewVar(): void
    {
        $form = self::createForm();
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $checkout->setShippingAddress(new OrderAddress());
        $form->add(
            'shippingAddress',
            CheckoutAddressSelectType::class,
            ['object' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $formView = $form->createView();

        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        $addressCollection = $orderAddressManager->getGroupedAddresses(
            $checkout,
            AddressType::TYPE_SHIPPING,
            'oro.checkout.'
        );

        $plainAddresses = [];
        array_walk_recursive($addressCollection, static function ($item, $key) use (&$plainAddresses, $serializer) {
            if ($item instanceof AbstractAddress) {
                $plainAddresses[$key] = $serializer->normalize($item);
            }
        });

        $plainAddresses[CheckoutAddressSelectType::ENTER_MANUALLY] = $serializer
            ->normalize($checkout->getShippingAddress());

        self::assertEquals(json_encode($plainAddresses), $formView['shippingAddress']->vars['attr']['data-addresses']);
    }

    public function testSubmitWithCustomerUserAddress(): void
    {
        $customerUserAddress = $this->getReference(LoadCustomerUserAddressesACLData::ADDRESS_ACC_1_USER_LOCAL);

        $form = self::createForm();
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $form->add(
            'shippingAddress',
            CheckoutAddressSelectType::class,
            ['object' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $form->submit(['shippingAddress' => 'au_' . $customerUserAddress->getId()]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        $shippingAddress = $orderAddressManager->updateFromAbstract($customerUserAddress);

        self::assertEquals($shippingAddress, $form->get('shippingAddress')->getData());
    }

    public function testSubmitWithCustomerUserAddressDoesNotCreateNewOrderAddress(): void
    {
        $customerUserAddress = $this->getReference(LoadCustomerUserAddressesACLData::ADDRESS_ACC_1_USER_LOCAL);

        $form = self::createForm();
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $oldShippingAddress = new OrderAddress();
        ReflectionUtil::setId($oldShippingAddress, 42);
        $checkout->setShippingAddress($oldShippingAddress);
        $form->add(
            'shippingAddress',
            CheckoutAddressSelectType::class,
            ['object' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $form->submit(['shippingAddress' => 'au_' . $customerUserAddress->getId()]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        /** @var OrderAddressManager $orderAddressManager */
        $orderAddressManager = self::getContainer()->get('oro_order.manager.order_address');
        $shippingAddress = $orderAddressManager->updateFromAbstract($customerUserAddress, $oldShippingAddress);

        self::assertEquals($shippingAddress, $form->get('shippingAddress')->getData());
        self::assertEquals($shippingAddress->getId(), $oldShippingAddress->getId());
    }

    public function testSubmitWithEnterManually(): void
    {
        $form = self::createForm(FormType::class, ['validation_groups' => false]);
        /** @var Checkout $checkout */
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $shippingAddress = (new OrderAddress())
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setStreet('St. Green 42')
            ->setCity('Forks')
            ->setPostalCode('424242')
            ->setCountry((new Country('USA'))->setName('USA'));
        ReflectionUtil::setId($shippingAddress, 42);
        $checkout->setShippingAddress($shippingAddress);
        $form->add(
            'shippingAddress',
            CheckoutAddressSelectType::class,
            ['object' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $form->submit(['shippingAddress' => CheckoutAddressSelectType::ENTER_MANUALLY]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertSame($shippingAddress, $form->get('shippingAddress')->getData());
    }

    public function testHasChoices(): void
    {
        $form = self::createForm();
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $form->add(
            'shippingAddress',
            CheckoutAddressSelectType::class,
            ['object' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $formView = $form->createView();

        $choices = $formView['shippingAddress']->vars['choices'];
        self::assertContainsEquals(
            new ChoiceView(0, '0', 'oro.checkout.form.address.manual', [], []),
            $choices
        );

        self::assertArrayHasKey(
            'oro.checkout.form.address.group_label.customer_user',
            $choices
        );

        self::assertEquals(
            'oro.checkout.form.address.group_label.customer_user',
            $choices['oro.checkout.form.address.group_label.customer_user']->label
        );

        $addressManager = self::getContainer()->get('oro_order.manager.order_address');
        /** @var AddressFormatter $addressFormatter */
        $addressFormatter = self::getContainer()->get('oro_locale.formatter.address');

        $addressCollection = $addressManager->getGroupedAddresses(
            $checkout,
            AddressType::TYPE_SHIPPING,
            'oro.checkout.'
        );
        $addressCollectionArray = $addressCollection->toArray();
        $customerUserAddresses = $addressCollectionArray['oro.checkout.form.address.group_label.customer_user'];

        self::assertCount(
            count($customerUserAddresses),
            $choices['oro.checkout.form.address.group_label.customer_user']->choices
        );

        foreach ($choices['oro.checkout.form.address.group_label.customer_user']->choices as $choiceView) {
            self::assertArrayHasKey($choiceView->value, $customerUserAddresses);
            self::assertEquals($choiceView->data, $customerUserAddresses[$choiceView->value]);
            self::assertEquals(
                $choiceView->label,
                $addressFormatter->format($customerUserAddresses[$choiceView->value], null, ', ')
            );
        }
    }
}
