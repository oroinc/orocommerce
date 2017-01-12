<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\PayPalBundle\Entity\ExpressCheckoutPaymentAction;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class AddExpressCheckoutPaymentActions extends AbstractFixture implements ContainerAwareInterface
{
    const AUTHORIZE = 'authorize';
    const CHARGE = 'charge';

    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManagerForClass(ExpressCheckoutPaymentAction::class);

        $action_auth = (new ExpressCheckoutPaymentAction())->setLabel(self::AUTHORIZE);
        $action_charge = (new ExpressCheckoutPaymentAction())->setLabel(self::CHARGE);

        $em->persist($action_auth);
        $em->persist($action_charge);

        $em->flush();
    }
}
