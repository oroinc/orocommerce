<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerGroupDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads payment terms for all customer groups.
 */
class LoadPaymentTermToAccountGroupDemoData extends AbstractFixture implements
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
            LoadCustomerGroupDemoData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $accessor = $this->container->get('oro_payment_term.provider.payment_term_association');
        $paymentTermsAll = $this->getLoadedPaymentTerms();
        $accountGroups = $manager->getRepository(CustomerGroup::class)->findAll();
        foreach ($accountGroups as $accountGroup) {
            $paymentTerm = $paymentTermsAll[array_rand($paymentTermsAll)];
            $accessor->setPaymentTerm($accountGroup, $paymentTerm);
        }
        $manager->flush();
    }
}
