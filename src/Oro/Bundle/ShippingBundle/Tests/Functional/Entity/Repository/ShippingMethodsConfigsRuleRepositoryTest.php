<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ShippingMethodsConfigsRuleRepositoryTest extends WebTestCase
{
    /**
     * @var ShippingMethodsConfigsRuleRepository
     */
    protected $repository;

    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadShippingMethodsConfigsRules::class,
        ]);

        $this->em = static::getContainer()->get('doctrine')
            ->getManagerForClass('OroShippingBundle:ShippingMethodsConfigsRule');
        $this->repository = $this->em->getRepository('OroShippingBundle:ShippingMethodsConfigsRule');
    }

    /**
     * @dataProvider getByCurrencyAndCountryDataProvider
     *
     * @param string $currency
     * @param string $country
     * @param array $expectedRules
     */
    public function testGetByCurrencyAndCountry($currency, $country, array $expectedRules)
    {
        /** @var ShippingMethodsConfigsRule[]|array $expectedShippingRule */
        $expectedShippingRules = $this->getEntitiesByReferences($expectedRules);
        /** @var ShippingMethodsConfigsRule $expectedShippingRule */
        $expectedShippingRule = $expectedShippingRules[0];
        $shippingRules = $this->repository->getByDestinationAndCurrency(
            $currency,
            $country
        );

        static::assertNotFalse(strpos(serialize($shippingRules), $expectedShippingRule->getRule()->getName()));
        static::assertNotFalse(strpos(serialize($shippingRules), $expectedShippingRule->getCurrency()));
        static::assertNotFalse(strpos(serialize($shippingRules), $expectedShippingRule->getRule()->getExpression()));
    }

    /**
     * TODO: refactor in BB-6393
     */
    public function testGetRulesWithoutShippingMethods()
    {
        $rulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods();
        $enabledRulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods(true);

        static::assertCount(2, $rulesWithoutShippingMethods);
        static::assertCount(1, $enabledRulesWithoutShippingMethods);
    }

    /**
     * TODO: refactor in BB-6393
     */
    public function testDisableRulesWithoutShippingMethods()
    {
        $this->repository->disableRulesWithoutShippingMethods();

        $rulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods();
        $enabledRulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods(true);

        static::assertCount(2, $rulesWithoutShippingMethods);
        static::assertCount(0, $enabledRulesWithoutShippingMethods);
    }

    /**
     * @return array
     */
    public function getByCurrencyAndCountryDataProvider()
    {
        return [
            [
                'currency' => 'USD',
                'country' => 'US',
                'expectedRules' => [
                    'shipping_rule.8',
                    'shipping_rule.7',
                ]
            ],
            [
                'currency' => 'EUR',
                'country' => 'US',
                'expectedRules' => [
                    'shipping_rule.1',
                    'shipping_rule.2',
                    'shipping_rule.4',
                    'shipping_rule.5',
                ]
            ],
        ];
    }

    /**
     * @param array $rules
     * @return array
     */
    protected function getEntitiesByReferences(array $rules)
    {
        return array_map(function ($ruleReference) {
            return $this->getReference($ruleReference);
        }, $rules);
    }
}
