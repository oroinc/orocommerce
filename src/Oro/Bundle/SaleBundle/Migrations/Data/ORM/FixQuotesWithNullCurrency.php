<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration for fixing quotes that didn't have currency
 */
class FixQuotesWithNullCurrency extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->configManager = $container->get('oro_config.manager');
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $quotes = $manager->getRepository(Quote::class)
            ->findBy([
                'currency' => null
            ]);

        $currency = $this->configManager->get(CurrencyConfig::getConfigKeyByName(
            CurrencyConfig::KEY_DEFAULT_CURRENCY
        ));

        foreach ($quotes as $quote) {
            $quote->setCurrency($currency);
        }

        $manager->flush();
    }
}
