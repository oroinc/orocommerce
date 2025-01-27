<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Form\Factory\AddressValidation;

use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\SecurityBundle\Test\Functional\AclAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\HttpFoundation\Request;

final class CheckoutAddressFormFactoryTest extends WebTestCase
{
    use AclAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadCheckoutACLData::class,
            LoadUserData::class,
        ]);

        $user = $this->getReference(LoadUserData::SIMPLE_USER);
        $this->updateUserSecurityToken($user->getEmail());
    }

    public function testThatFormCreatedForMultiStepBillingAddress(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.address_form.billing_address'
        );
        $requestStack = self::getContainer()->get('request_stack');
        $frontendHelper = self::getContainer()->get('oro_frontend.request.frontend_helper');

        $frontendHelper->emulateFrontendRequest();

        $request = new Request();
        $request->attributes->set('checkout', $checkout);
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);

        $billingAddress = new OrderAddress();
        $checkout->setBillingAddress($billingAddress);

        $addressForm = $factory->createAddressForm($request);

        self::assertEquals('billing_address', $addressForm->getName());
        self::assertEquals($billingAddress, $addressForm->getData());
    }

    public function testThatFormCreatedForMultiStepBillingAddressWhenHasExplicitAddress(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.address_form.billing_address'
        );

        $requestStack = self::getContainer()->get('request_stack');
        $frontendHelper = self::getContainer()->get('oro_frontend.request.frontend_helper');

        $frontendHelper->emulateFrontendRequest();

        $request = new Request();
        $request->attributes->set('checkout', $checkout);
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);

        $billingAddress = new OrderAddress();
        $checkout->setBillingAddress($billingAddress);

        $newBillingAddress = new OrderAddress();
        $addressForm = $factory->createAddressForm($request, $newBillingAddress);

        self::assertEquals('billing_address', $addressForm->getName());
        self::assertSame($newBillingAddress, $addressForm->getData());
    }

    public function testThatFormCreatedForMultiStepShippingAddress(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        /* @var WorkflowManager $workflowManager */
        $workflowManager = self::getContainer()->get('oro_workflow.manager');

        /**
         * @var CheckoutWorkflowHelper $workflowHelper
         */
        $workflowHelper = self::getContainer()->get('oro_checkout.helper.checkout_workflow_helper');

        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.address_form.shipping_address'
        );

        $requestStack = self::getContainer()->get('request_stack');
        $frontendHelper = self::getContainer()->get('oro_frontend.request.frontend_helper');

        $frontendHelper->emulateFrontendRequest();

        $request = new Request();
        $request->attributes->set('checkout', $checkout);
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);

        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();
        $checkout->setBillingAddress($billingAddress);
        $checkout->setShippingAddress($shippingAddress);

        $workflowItem = $workflowHelper->getWorkflowItem($checkout);
        $workflowManager->transitWithoutChecks($workflowItem, 'continue_to_shipping_address');

        $addressForm = $factory->createAddressForm($request);

        self::assertEquals('shipping_address', $addressForm->getName());
        self::assertEquals($shippingAddress, $addressForm->getData());
    }

    public function testThatFormCreatedForMultiStepShippingAddressWhenHasExplicitAddress(): void
    {
        $checkout = $this->getReference(LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL);

        /* @var WorkflowManager $workflowManager */
        $workflowManager = self::getContainer()->get('oro_workflow.manager');

        /**
         * @var CheckoutWorkflowHelper $workflowHelper
         */
        $workflowHelper = self::getContainer()->get('oro_checkout.helper.checkout_workflow_helper');

        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.address_form.shipping_address'
        );

        $requestStack = self::getContainer()->get('request_stack');
        $frontendHelper = self::getContainer()->get('oro_frontend.request.frontend_helper');

        $frontendHelper->emulateFrontendRequest();

        $request = new Request();
        $request->attributes->set('checkout', $checkout);
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);

        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();
        $checkout->setBillingAddress($billingAddress);
        $checkout->setShippingAddress($shippingAddress);

        $workflowItem = $workflowHelper->getWorkflowItem($checkout);
        $workflowManager->transitWithoutChecks($workflowItem, 'continue_to_shipping_address');

        $newShippingAddress = new OrderAddress();
        $addressForm = $factory->createAddressForm($request, $newShippingAddress);

        self::assertEquals('shipping_address', $addressForm->getName());
        self::assertSame($newShippingAddress, $addressForm->getData());
    }

    public function testThatFormCreatedForSingleStepBillingAddress(): void
    {
        $checkout = $this->getReference(
            LoadCheckoutACLData::SINGLE_STEP_CHECKOUT_ACC_1_USER_LOCAL
        );

        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.address_form.billing_address'
        );

        $requestStack = self::getContainer()->get('request_stack');
        $frontendHelper = self::getContainer()->get('oro_frontend.request.frontend_helper');

        $frontendHelper->emulateFrontendRequest();

        $request = new Request();
        $request->attributes->set('checkout', $checkout);
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);

        $billingAddress = new OrderAddress();
        $checkout->setBillingAddress($billingAddress);

        $addressForm = $factory->createAddressForm($request);

        self::assertEquals('billing_address', $addressForm->getName());
        self::assertEquals($billingAddress, $addressForm->getData());
    }

    public function testThatFormCreatedForSingleStepBillingAddressWhenHasExplicitAddress(): void
    {
        $checkout = $this->getReference(
            LoadCheckoutACLData::SINGLE_STEP_CHECKOUT_ACC_1_USER_LOCAL
        );

        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.address_form.billing_address'
        );

        $requestStack = self::getContainer()->get('request_stack');
        $frontendHelper = self::getContainer()->get('oro_frontend.request.frontend_helper');

        $frontendHelper->emulateFrontendRequest();

        $request = new Request();
        $request->attributes->set('checkout', $checkout);
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);

        $billingAddress = new OrderAddress();
        $checkout->setBillingAddress($billingAddress);

        $newBillingAddress = new OrderAddress();
        $addressForm = $factory->createAddressForm($request, $newBillingAddress);

        self::assertEquals('billing_address', $addressForm->getName());
        self::assertSame($newBillingAddress, $addressForm->getData());
    }

    public function testThatFormCreatedForSingleStepShippingAddress(): void
    {
        $checkout = $this->getReference(
            LoadCheckoutACLData::SINGLE_STEP_CHECKOUT_ACC_1_USER_LOCAL
        );

        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.address_form.shipping_address'
        );

        $requestStack = self::getContainer()->get('request_stack');
        $frontendHelper = self::getContainer()->get('oro_frontend.request.frontend_helper');

        $frontendHelper->emulateFrontendRequest();

        $request = new Request();
        $request->attributes->set('checkout', $checkout);
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);

        $shippingAddress = new OrderAddress();
        $checkout->setShippingAddress($shippingAddress);

        $addressForm = $factory->createAddressForm($request);

        self::assertEquals('shipping_address', $addressForm->getName());
        self::assertEquals($shippingAddress, $addressForm->getData());
    }

    public function testThatFormCreatedForSingleStepShippingAddressWhenHasExplicitAddress(): void
    {
        $checkout = $this->getReference(
            LoadCheckoutACLData::SINGLE_STEP_CHECKOUT_ACC_1_USER_LOCAL
        );

        $factory = self::getContainer()->get(
            'oro_checkout.form.factory.address_validation.address_form.shipping_address'
        );

        $requestStack = self::getContainer()->get('request_stack');
        $frontendHelper = self::getContainer()->get('oro_frontend.request.frontend_helper');

        $frontendHelper->emulateFrontendRequest();

        $request = new Request();
        $request->attributes->set('checkout', $checkout);
        $request->attributes->set('_theme', 'default');

        $requestStack->push($request);

        $shippingAddress = new OrderAddress();
        $checkout->setShippingAddress($shippingAddress);

        $newShippingAddress = new OrderAddress();
        $addressForm = $factory->createAddressForm($request, $newShippingAddress);

        self::assertEquals('shipping_address', $addressForm->getName());
        self::assertSame($newShippingAddress, $addressForm->getData());
    }
}
