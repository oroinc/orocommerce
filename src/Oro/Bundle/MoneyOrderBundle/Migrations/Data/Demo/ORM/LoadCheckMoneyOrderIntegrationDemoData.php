<?php

declare(strict_types=1);

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MoneyOrderBundle\Migration\DataHelper\MoneyOrderHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates Check/Money Order payment method and payment rules.
 */
class LoadCheckMoneyOrderIntegrationDemoData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected MoneyOrderHelper $moneyOrderHelper;

    #[\Override]
    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container;
        $this->moneyOrderHelper = $this->container->get(MoneyOrderHelper::class);
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $owner = $manager->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);
        $this->moneyOrderHelper->createMoneyOrderPaymentMethodAndPaymentRules(
            label: 'Check / Money Order',
            payTo: $owner->getFullName(),
            sendTo: "1901 Avenue of the Stars, Suite 200,\nLos Angeles, CA 90067",
            owner: $owner,
            currencies: [null], // Create a payment rule for the default currency
            enablePaymentRules: false,
            paymentRulesSortOrder: 20,
        );
    }
}
