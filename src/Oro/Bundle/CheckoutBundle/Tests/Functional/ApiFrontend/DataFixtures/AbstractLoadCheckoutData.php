<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadAddressTypes;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountriesAndRegions;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadWebsiteData;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermSettingsData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem as ShoppingListLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem as ShoppingListProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsite;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractLoadCheckoutData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected const string WORKFLOW_NAME = 'b2b_flow_checkout';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUser::class,
            LoadWebsiteData::class,
            LoadCountriesAndRegions::class,
            LoadAddressTypes::class,
            LoadProductData::class,
            LoadProductKitData::class,
            LoadCombinedPriceLists::class,
            LoadShippingMethodsConfigsRulesWithConfigs::class,
            LoadPaymentTermData::class,
            LoadPaymentTermSettingsData::class,
            LoadPaymentMethodsConfigsRuleData::class
        ];
    }

    protected function loadCheckouts(ObjectManager $manager, array $data): void
    {
        foreach ($data as $name => $checkoutData) {
            $this->loadCheckout($manager, $name, $checkoutData);
        }
        $manager->flush();

        /** @var CheckoutSubtotalUpdater $checkoutSubtotalUpdater */
        $checkoutSubtotalUpdater = $this->container->get('oro_checkout.model.checkout_subtotal_updater');
        /* @var WorkflowManager $workflowManager */
        $workflowManager = $this->container->get('oro_workflow.manager');
        foreach ($data as $name => $checkoutData) {
            if (!isset($checkoutData['customerUser'])) {
                continue;
            }

            /* @var CustomerUser $customerUser */
            $customerUser = $this->getReference($checkoutData['customerUser']);
            $this->initializeSecurityContext($customerUser);
            try {
                /** @var Checkout $checkout */
                $checkout = $this->getReference($name);
                $checkoutSubtotalUpdater->recalculateCheckoutSubtotals($checkout);
                $this->startWorkflow($workflowManager, $checkout);
            } finally {
                $this->restoreSecurityContext();
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function loadCheckout(ObjectManager $manager, string $name, array $checkoutData): void
    {
        /* @var Organization|null $organization */
        $organization = isset($checkoutData['organization'])
            ? $this->getReference($checkoutData['organization'])
            : null;
        /* @var User|null $user */
        $user = null;
        if (isset($checkoutData['user'])) {
            $user = $this->getReference($checkoutData['user']);
            if (null === $organization) {
                $organization = $user->getOrganization();
            }
        }
        /* @var CustomerUser|null $customerUser */
        $customerUser = null;
        if (isset($checkoutData['customerUser'])) {
            $customerUser = $this->getReference($checkoutData['customerUser']);
            if (null === $user) {
                $user = $customerUser->getOwner();
            }
            if (null === $organization) {
                $organization = $customerUser->getOrganization();
            }
        }

        $shoppingList = null;
        if (isset($checkoutData['shoppingListLineItems'])) {
            $shoppingListLineItems = [];
            foreach ($checkoutData['shoppingListLineItems'] as $shoppingListLineItemData) {
                $shoppingListLineItems[] = $this->createShoppingListLineItem($shoppingListLineItemData);
            }
            $shoppingList = $this->createShoppingList(
                $organization,
                $user,
                $customerUser,
                $name,
                $shoppingListLineItems
            );
            $manager->persist($shoppingList);
            $this->setReference($name . '.shopping_list', $shoppingList);
        }

        $checkout = new Checkout();
        if (null !== $customerUser) {
            $checkout->setCustomerUser($customerUser);
        }
        $checkout->setOrganization($organization);
        $checkout->setWebsite($this->getReference(LoadWebsite::WEBSITE));
        $checkout->setOwner($this->getReference(LoadUser::USER));
        $checkout->setSource($this->createCheckoutSource($shoppingList));
        if ('checkout.empty' !== $name) {
            $checkout->setCustomerNotes($name);
        }
        if (!empty($checkoutData['checkout']['currency'])) {
            $checkout->setCurrency($checkoutData['checkout']['currency']);
        }
        if (!empty($checkoutData['checkout']['shippingCostAmount'])) {
            $checkout->setShippingCost(Price::create($checkoutData['checkout']['shippingCostAmount'], 'USD'));
        }
        if ($checkoutData['checkout']['deleted'] ?? false) {
            $checkout->setDeleted(true);
        }
        if (null !== $shoppingList) {
            $checksumGenerator = $this->container->get('oro_product.line_item_checksum_generator');

            foreach ($shoppingList->getLineItems() as $i => $shoppingListLineItem) {
                $lineItem = new CheckoutLineItem();
                $lineItem->setProduct($shoppingListLineItem->getProduct());
                $lineItem->setProductUnit($shoppingListLineItem->getProductUnit());
                $lineItem->setQuantity($shoppingListLineItem->getQuantity());
                $lineItem->setChecksum($checksumGenerator->getChecksum($lineItem));
                $checkout->addLineItem($lineItem);
                $this->setReference($name . '.line_item.' . ($i + 1), $lineItem);
                foreach ($shoppingListLineItem->getKitItemLineItems() as $j => $kitItem) {
                    $lineItemKitItem = new CheckoutProductKitItemLineItem();
                    $lineItemKitItem->setLineItem($lineItem);
                    $lineItemKitItem->setKitItem($kitItem->getKitItem());
                    $lineItemKitItem->setProduct($kitItem->getProduct());
                    $lineItemKitItem->setProductUnit($kitItem->getUnit());
                    $lineItemKitItem->setQuantity($kitItem->getQuantity());
                    $lineItemKitItem->setSortOrder($kitItem->getSortOrder());
                    $lineItemKitItem->setCurrency('USD');
                    $lineItem->addKitItemLineItem($lineItemKitItem);
                    $this->setReference(
                        $name . '.line_item.' . ($i + 1) . '.kit_item.' . ($j + 1),
                        $lineItemKitItem
                    );
                }
            }
        }
        if (isset($checkoutData['shipToBillingAddress'])) {
            $checkout->setShipToBillingAddress($checkoutData['shipToBillingAddress']);
        }
        if (isset($checkoutData['billingAddress'])) {
            $checkout->setBillingAddress($checkoutData['billingAddress']);
            $this->setReference($name . '.billing_address', $checkoutData['billingAddress']);
        }
        if (isset($checkoutData['shippingAddress'])) {
            $checkout->setShippingAddress($checkoutData['shippingAddress']);
            $this->setReference($name . '.shipping_address', $checkoutData['shippingAddress']);
        }
        $manager->persist($checkout);
        $this->setReference($name, $checkout);
    }

    protected function createCheckoutAddress(array $data): OrderAddress
    {
        $address = new OrderAddress();
        $address->setCountry($this->getReference($data['country']));
        $address->setCity($data['city']);
        $address->setRegion($this->getReference($data['region']));
        $address->setStreet($data['street']);
        $address->setPostalCode($data['postalCode']);
        $address->setFirstName($data['firstName']);
        $address->setLastName($data['lastName']);

        return $address;
    }

    protected function initializeSecurityContext(CustomerUser $customerUser): void
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->container->get('security.token_storage');
        $tokenStorage->setToken(new UsernamePasswordOrganizationToken(
            $customerUser,
            'main',
            $customerUser->getOrganization(),
            $customerUser->getUserRoles()
        ));
        /** @var RequestStack $requestStack */
        $requestStack = $this->container->get('request_stack');
        $requestStack->push($this->createAjaxCheckoutRequest());
    }

    protected function restoreSecurityContext(): void
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->container->get('security.token_storage');
        $tokenStorage->setToken(null);
        /** @var RequestStack $requestStack */
        $requestStack = $this->container->get('request_stack');
        $requestStack->pop();
    }

    protected function startWorkflow(WorkflowManager $workflowManager, Checkout $checkout): void
    {
        $errors = new ArrayCollection();
        $errorMessage = '';
        try {
            $workflowManager->startWorkflow(self::WORKFLOW_NAME, $checkout, null, [], true, $errors);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }
        if ($errorMessage || !$errors->isEmpty()) {
            throw new \RuntimeException($this->getWorkflowTransitionFailureMessage(
                'The start workflow failed.',
                $errorMessage,
                $errors
            ));
        }
    }

    protected function transitWorkflow(WorkflowManager $workflowManager, Checkout $checkout, string $transition): void
    {
        $workflowItem = $workflowManager->getWorkflowItem($checkout, self::WORKFLOW_NAME);
        $errors = new ArrayCollection();
        $errorMessage = '';
        try {
            $workflowManager->transit($workflowItem, $transition, $errors);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }
        if ($errorMessage || !$errors->isEmpty()) {
            throw new \RuntimeException($this->getWorkflowTransitionFailureMessage(
                \sprintf(
                    'The workflow transition "%s" failed (current step: "%s").',
                    $transition,
                    $workflowItem->getCurrentStep()->getName()
                ),
                $errorMessage,
                $errors
            ));
        }
    }

    protected function getWorkflowTransitionFailureMessage(
        string $message,
        ?string $errorMessage,
        ArrayCollection $errors
    ): string {
        $result = $message;
        if ($errorMessage) {
            $result .= "\n - " . $errorMessage;
        }
        foreach ($errors as $error) {
            $result .= "\n - " . $this->container->get('translator')->trans($error['message']);
        }

        return $result;
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->container->get('property_accessor');
    }

    protected function createShoppingList(
        Organization $organization,
        User $user,
        ?CustomerUser $customerUser,
        string $name,
        array $lineItems
    ): ShoppingList {
        $shoppingList = new ShoppingList();
        $shoppingList->setWebsite($this->getReference(LoadWebsite::WEBSITE));
        $shoppingList->setOwner($user);
        $shoppingList->setOrganization($organization);
        if (null !== $customerUser) {
            $shoppingList->setCustomerUser($customerUser);
            $shoppingList->setCustomer($customerUser->getCustomer());
        }
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');
        $shoppingList->setCurrency('USD');
        foreach ($lineItems as $lineItem) {
            $shoppingList->addLineItem($lineItem);
        }

        return $shoppingList;
    }

    protected function createShoppingListLineItem(array $data): ShoppingListLineItem
    {
        /** @var Product $product */
        $product = $this->getReference($data['product']);

        $lineItem = new ShoppingListLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $lineItem->setQuantity($data['quantity'] ?? 1);
        $kitItems = $data['kitItems'] ?? [];
        foreach ($kitItems as $i => $kitItemReference) {
            /** @var ProductKitItem $kitItem */
            $kitItem = $this->getReference($kitItemReference);
            $lineItemKitItem = new ShoppingListProductKitItemLineItem();
            $lineItemKitItem->setLineItem($lineItem);
            $lineItemKitItem->setKitItem($kitItem);
            $lineItemKitItem->setProduct($kitItem->getProducts()->first());
            $lineItemKitItem->setUnit($kitItem->getProductUnit());
            $lineItemKitItem->setQuantity(1);
            $lineItemKitItem->setSortOrder($i + 1);
            $lineItem->addKitItemLineItem($lineItemKitItem);
        }

        return $lineItem;
    }

    private function createCheckoutSource(?ShoppingList $shoppingList): CheckoutSource
    {
        $checkoutSource = new CheckoutSource();
        if (null !== $shoppingList) {
            $this->getPropertyAccessor()->setValue($checkoutSource, 'shoppingList', $shoppingList);
        }

        return $checkoutSource;
    }

    private function createAjaxCheckoutRequest(): Request
    {
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->query->set('_wid', 'ajax_checkout');

        return $request;
    }
}
