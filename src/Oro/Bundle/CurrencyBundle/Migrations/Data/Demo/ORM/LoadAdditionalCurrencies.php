<?php

namespace Oro\Bundle\CurrencyBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;

class LoadAdditionalCurrencies extends AbstractFixture implements ContainerAwareInterface
{
    const ORO_CURRENCY_ALLOWED_CURRENCIES = 'oro_currency.allowed_currencies';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_config.manager');
        $currencies = $this->container->get('oro_config.manager')->get(self::ORO_CURRENCY_ALLOWED_CURRENCIES);
        $currencies = array_merge($currencies, ['EUR']);
        $configManager->set(self::ORO_CURRENCY_ALLOWED_CURRENCIES, $currencies);
        $configManager->flush();
    }
}
