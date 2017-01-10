<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerGroupRepository;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

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
            'Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerGroupDemoData',
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

        $accountGroupRepository = $doctrine->getRepository('OroCustomerBundle:CustomerGroup');

        $paymentTermsAll = $this->getLoadedPaymentTerms();
        $accountGroupsIterator = $accountGroupRepository->getBatchIterator();

        foreach ($accountGroupsIterator as $accountGroup) {
            /** @var PaymentTerm $paymentTerm */
            $paymentTerm = $paymentTermsAll[array_rand($paymentTermsAll)];
            $accessor->setPaymentTerm($accountGroup, $paymentTerm);
        }
        $manager->flush();
    }
}
