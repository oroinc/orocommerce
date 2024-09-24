<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderAddressData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractLoadCheckouts extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    abstract protected function getData(): array;

    abstract protected function getWorkflowName(): string;

    abstract protected function createCheckout(): Checkout;

    abstract protected function getCheckoutSourceName(): string;

    protected function getDefaultCustomerUser(ObjectManager $manager): CustomerUser
    {
        return $manager->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /* @var User $owner */
        $owner = $manager->getRepository(User::class)->findOneBy([]);
        /* @var WorkflowManager $workflowManager */
        $workflowManager = $this->container->get('oro_workflow.manager');
        $this->clearPreconditions($manager);
        $defaultCustomerUser = $this->getDefaultCustomerUser($manager);
        /* @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        foreach ($this->getData() as $name => $checkoutData) {
            /* @var CustomerUser $customerUser */
            $customerUser = isset($checkoutData['customerUser']) ?
                $this->getReference($checkoutData['customerUser']) :
                $defaultCustomerUser;

            $checkout = $this->createCheckout();
            $checkout->setCustomerUser($customerUser);
            $checkout->setOrganization($customerUser->getOrganization());
            $checkout->setWebsite($website);
            $checkout->setOwner($owner);
            $source = new CheckoutSource();
            /** @var CheckoutSourceEntityInterface $sourceEntity */
            $sourceEntity = $this->getReference($checkoutData['source']);
            $this->container->get('property_accessor')->setValue(
                $source,
                $this->getCheckoutSourceName(),
                $sourceEntity
            );
            $checkout->setPaymentMethod($checkoutData['checkout']['payment_method']);
            $checkout->setSource($source);
            $checkout->setCustomerNotes($name);
            $checkout->setCompleted(!empty($checkoutData['completed']));
            if (!empty($checkoutData['completedData'])) {
                $completedData = $checkout->getCompletedData();

                foreach ($checkoutData['completedData'] as $key => $value) {
                    $completedData->offsetSet($key, $value);
                }
            }
            if (!empty($checkoutData['checkout']['currency'])) {
                $checkout->setCurrency($checkoutData['checkout']['currency']);
            }
            if (!empty($checkoutData['checkout']['shippingCostAmount'])) {
                $checkout->setShippingCost(Price::create($checkoutData['checkout']['shippingCostAmount'], 'USD'));
            }

            if (!empty($checkoutData['lineItems'])) {
                $checkout->setLineItems($checkoutData['lineItems']);
            }

            $manager->persist($checkout);
            $this->setReference($name, $checkout);
        }
        $manager->flush();

        foreach ($this->getData() as $name => $checkoutData) {
            $checkout = $this->getReference($name);
            $workflowManager->startWorkflow($this->getWorkflowName(), $checkout);
        }
    }

    protected function clearPreconditions(ObjectManager $manager): void
    {
        $workflowDefinition = $manager->getRepository(WorkflowDefinition::class)
            ->findOneBy(['name' => $this->getWorkflowName()]);
        $config = $workflowDefinition->getConfiguration();
        $config['transition_definitions']['__start___definition']['preconditions'] = [];
        $workflowDefinition->setConfiguration($config);
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrderAddressData::class,
            LoadPaymentTermData::class,
            LoadWebsiteData::class,
        ];
    }
}
