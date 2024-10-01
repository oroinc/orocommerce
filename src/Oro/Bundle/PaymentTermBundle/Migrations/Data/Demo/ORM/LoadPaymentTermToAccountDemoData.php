<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads payment terms for all customers.
 */
class LoadPaymentTermToAccountDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use PaymentTermDemoMigrationTrait;
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadPaymentTermDemoData::class,
            LoadCustomerDemoData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $accessor = $this->container->get('oro_payment_term.provider.payment_term_association');
        $paymentTermsAll = $this->getLoadedPaymentTerms();
        $accountsIterator = $manager->getRepository(Customer::class)->getBatchIterator();
        foreach ($accountsIterator as $account) {
            $paymentTerm = $paymentTermsAll[array_rand($paymentTermsAll)];
            $accessor->setPaymentTerm($account, $paymentTerm);
        }
        $manager->flush();
    }
}
