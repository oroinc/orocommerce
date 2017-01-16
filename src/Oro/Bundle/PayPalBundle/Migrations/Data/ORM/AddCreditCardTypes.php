<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\PayPalBundle\Entity\CreditCardType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class AddCreditCardTypes extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManagerForClass(CreditCardType::class);

        $types = [
            CreditCardType::CARD_VISA,
            CreditCardType::CARD_MASTERCARD,
            CreditCardType::CARD_DISCOVER,
            CreditCardType::CARD_AMERICAN_EXPRESS
        ];

        foreach ($types as $type) {
            $action = (new CreditCardType())->setLabel($type);
            $em->persist($action);
        }

        $em->flush();
    }
}
