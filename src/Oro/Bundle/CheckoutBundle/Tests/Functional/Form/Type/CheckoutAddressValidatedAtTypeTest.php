<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutAddressValidatedAtType;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\AbstractLoadACLData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddressesACLData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class CheckoutAddressValidatedAtTypeTest extends FrontendWebTestCase
{
    use FormAwareTestTrait;

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

    public function testCreateWithEmptyInitialData(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $form = self::createForm();
        $form->add(
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            ['checkout' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        self::assertNull($form->getData());
    }

    public function testCreateWithNotEmptyInitialData(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $dateTime = new \DateTime('today');
        $form = self::createForm(FormType::class, ['validatedAt' => $dateTime]);
        $form->add(
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            ['checkout' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        self::assertEquals(['validatedAt' => $dateTime], $form->getData());
    }

    public function testHasViewVars(): void
    {
        $billingAddress = (new OrderAddress())
            ->setValidatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $checkout->setBillingAddress($billingAddress);

        $dateTime = new \DateTime('today');
        $form = self::createForm(FormType::class, ['validatedAt' => $dateTime]);
        $form->add(
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            ['checkout' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $formView = $form->createView();

        self::assertEquals($checkout->getId(), $formView['validatedAt']->vars['checkoutId']);
        self::assertEquals(AddressType::TYPE_SHIPPING, $formView['validatedAt']->vars['addressType']);
        self::assertTrue($formView['validatedAt']->vars['isBillingAddressValid']);
    }

    public function testHasExtraBlockPrefix(): void
    {
        $billingAddress = (new OrderAddress())
            ->setValidatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);
        $checkout->setBillingAddress($billingAddress);

        $dateTime = new \DateTime('today');
        $form = self::createForm(FormType::class, ['validatedAt' => $dateTime], ['block_prefix' => 'sample_root_form']);
        $form->add(
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            ['checkout' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $formView = $form->createView();

        self::assertContains(
            'sample_root_form__oro_checkout_address_validated_at',
            $formView['validatedAt']->vars['block_prefixes']
        );
    }

    public function testSubmitWithEmptyDataWhenEmptyInitialData(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $form = self::createForm();
        $form->add(
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            ['checkout' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $form->submit([]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(['validatedAt' => null], $form->getData());
    }

    public function testSubmitWithNotEmptyDataWhenEmptyInitialData(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $dateTime = new \DateTime('2024-01-01 00:00:00');
        $form = self::createForm();
        $form->add(
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            ['checkout' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $form->submit(['validatedAt' => '2024-01-01 00:00:00']);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(['validatedAt' => $dateTime], $form->getData());
    }

    public function testSubmitWithEmptyDataWhenNotEmptyInitialData(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $initialDateTime = new \DateTime('2023-01-01 00:00:00');
        $form = self::createForm(FormType::class, ['validatedAt' => $initialDateTime]);
        $form->add(
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            ['checkout' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $form->submit(['validatedAt' => '']);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(['validatedAt' => null], $form->getData());
    }

    public function testSubmitWithNotEmptyDataWhenNotEmptyInitialData(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $initialDateTime = new \DateTime('2023-01-01 00:00:00');
        $dateTime = new \DateTime('2024-01-01 00:00:00');
        $form = self::createForm(FormType::class, ['validatedAt' => $initialDateTime]);
        $form->add(
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            ['checkout' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $form->submit(['validatedAt' => '2024-01-01 00:00:00']);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(['validatedAt' => $dateTime], $form->getData());
    }

    public function testSubmitWithInvalidDataWhenNotEmptyInitialData(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $initialDateTime = new \DateTime('2023-01-01 00:00:00');
        $form = self::createForm(FormType::class, ['validatedAt' => $initialDateTime]);
        $form->add(
            'validatedAt',
            CheckoutAddressValidatedAtType::class,
            ['checkout' => $checkout, 'address_type' => AddressType::TYPE_SHIPPING]
        );

        $form->submit(['validatedAt' => 'invalid']);

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertEquals(['validatedAt' => $initialDateTime], $form->getData());
    }
}
