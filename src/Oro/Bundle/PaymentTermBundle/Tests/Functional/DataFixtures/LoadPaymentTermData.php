<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadPaymentTermData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const TERM_LABEL_NET_10 = 'net 10';
    const TERM_LABEL_NET_20 = 'net 20';
    const TERM_LABEL_NET_30 = 'net 30';
    const TERM_LABEL_NET_40 = 'net 40';
    const PAYMENT_TERM_REFERENCE_PREFIX = 'payment_term_test_data_';

    /**
     * @var array
     */
    protected $data = [
        [
            'label' => self::TERM_LABEL_NET_10,
            'customers' => ['customer.level_1'],
            'groups' => ['customer_group.group1']
        ],
        [
            'label' => self::TERM_LABEL_NET_20,
            'customers' => ['customer.level_1.2'],
            'groups' => [],
        ],
        [
            'label' => self::TERM_LABEL_NET_30,
            'customers' => ['customer.level_1.1'],
            'groups' => ['customer_group.group2'],
        ],
        [
            'label' => self::TERM_LABEL_NET_40,
            'customers' => [],
            'groups' => [],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadCustomers::class, LoadGroups::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $accessor = $this->container->get('oro_payment_term.provider.payment_term_association');

        foreach ($this->data as $paymentTermData) {
            $paymentTerm = new PaymentTerm();
            $paymentTerm->setLabel($paymentTermData['label']);

            foreach ($paymentTermData['groups'] as $groupName) {
                $accessor->setPaymentTerm($this->getReference($groupName), $paymentTerm);
            }

            foreach ($paymentTermData['customers'] as $customerName) {
                $accessor->setPaymentTerm($this->getReference($customerName), $paymentTerm);
            }
            $manager->persist($paymentTerm);
            $this->addReference(static::PAYMENT_TERM_REFERENCE_PREFIX . $paymentTermData['label'], $paymentTerm);
        }

        $manager->flush();
    }
}
