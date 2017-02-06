<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\PayPalBundle\Entity\CreditCardPaymentAction;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class AddCreditCardPaymentActions extends AbstractFixture implements ContainerAwareInterface
{
    const AUTHORIZE = 'authorize';
    const CHARGE = 'charge';

    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManagerForClass(CreditCardPaymentAction::class);

        $action_auth = (new CreditCardPaymentAction())->setLabel(self::AUTHORIZE);
        $action_charge = (new CreditCardPaymentAction())->setLabel(self::CHARGE);

        $em->persist($action_auth);
        $em->persist($action_charge);

        $em->flush();
    }
}
