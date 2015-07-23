<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class LoadPaymentTermToCustomerDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /** @var  ContainerInterface */
    protected $container;

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    function getDependencies()
    {
        return [
            'OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData',
        ];
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
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
        $customerRepository = $doctrine->getRepository('OroB2BCustomerBundle:Customer');
        $paymentTermRepository = $doctrine->getRepository('OroB2BPaymentBundle:PaymentTerm');

        $paymentTermsAll = $paymentTermRepository->findAll();
        $customersAll = $customerRepository->findAll();

        foreach ($customersAll as $customer) {
            /** @var PaymentTerm $paymentTerm */
            if ($customer->getGroup()->getName() == 'First') {
                continue;
            }
            $paymentTerm = $paymentTermsAll[array_rand($paymentTermsAll)];
            $paymentTerm->addCustomer($customer);
        }
        $manager->flush();
    }
}
