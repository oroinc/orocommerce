<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadPriceRuleLexemes extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager)
    {
        $priceRuleLexemeHandler = $this->container->get('oro_pricing.handler.price_rule_lexeme_handler');

        $priceLists = $this->container->get('doctrine')->getRepository(PriceList::class)->findAll();
        foreach ($priceLists as $priceList) {
            $priceRuleLexemeHandler->updateLexemes($priceList);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadPriceRules::class];
    }
}
