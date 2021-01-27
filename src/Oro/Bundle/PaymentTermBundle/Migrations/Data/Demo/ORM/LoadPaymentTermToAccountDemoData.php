<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
            'Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData',
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

        $accessor = $this->container->get('oro_payment_term.provider.payment_term_association');

        /** @var \Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository $accountRepository */
        $accountRepository = $doctrine->getRepository('OroCustomerBundle:Customer');

        $paymentTermsAll  = $this->getLoadedPaymentTerms();
        $accountsIterator = $accountRepository->getBatchIterator();

        foreach ($accountsIterator as $account) {
            /** @var PaymentTerm $paymentTerm */
            $paymentTerm = $paymentTermsAll[array_rand($paymentTermsAll)];
            $accessor->setPaymentTerm($account, $paymentTerm);
        }
        $manager->flush();
    }
}
