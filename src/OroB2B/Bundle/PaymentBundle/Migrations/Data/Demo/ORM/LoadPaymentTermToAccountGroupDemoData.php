<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class LoadPaymentTermToAccountGroupDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountGroupDemoData',
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
        $accountGroupRepository = $doctrine->getRepository('OroB2BAccountBundle:AccountGroup');
        $paymentTermRepository = $doctrine->getRepository('OroB2BPaymentBundle:PaymentTerm');

        $paymentTermsAll = $paymentTermRepository->findAll();
        $accountGroupsAll = $accountGroupRepository->findAll();

        foreach ($accountGroupsAll as $accountGroup) {
            /** @var PaymentTerm $paymentTerm */
            $paymentTerm = $paymentTermsAll[array_rand($paymentTermsAll)];
            $paymentTerm->addAccountGroup($accountGroup);
        }
        $manager->flush();
    }
}
