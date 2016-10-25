<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Migrations\PaymentTermDemoMigrationTrait;

class LoadPaymentTermToAccountDemoData extends AbstractFixture implements
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
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
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

        /** @var \Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository $accountRepository */
        $accountRepository = $doctrine->getRepository('OroCustomerBundle:Account');

        $paymentTermsAll  = $this->getLoadedPaymentTerms();
        $accountsIterator = $accountRepository->getBatchIterator();

        foreach ($accountsIterator as $account) {
            /** @var PaymentTerm $paymentTerm */
            $paymentTerm = $paymentTermsAll[array_rand($paymentTermsAll)];
            $paymentTerm->addAccount($account);
        }
        $manager->flush();
    }
}
