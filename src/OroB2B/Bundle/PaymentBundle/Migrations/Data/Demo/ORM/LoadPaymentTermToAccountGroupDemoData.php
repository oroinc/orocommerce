<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Migrations\PaymentTermDemoMigrationTrait;

class LoadPaymentTermToAccountGroupDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use PaymentTermDemoMigrationTrait;

    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'Oro\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountGroupDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $doctrine = $this->container->get('doctrine');
        $accountGroupRepository = $doctrine->getRepository('OroAccountBundle:AccountGroup');

        $paymentTermsAll       = $this->getLoadedPaymentTerms();
        $accountGroupsIterator = $accountGroupRepository->getBatchIterator();

        foreach ($accountGroupsIterator as $accountGroup) {
            /** @var PaymentTerm $paymentTerm */
            $paymentTerm = $paymentTermsAll[array_rand($paymentTermsAll)];
            $paymentTerm->addAccountGroup($accountGroup);
        }
        $manager->flush();
    }
}
