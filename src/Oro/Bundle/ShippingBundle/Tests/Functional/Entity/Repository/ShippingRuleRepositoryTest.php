<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules;

/**
 * @dbIsolation
 */
class ShippingRuleRepositoryTest extends WebTestCase
{
    /**
     * @var ShippingRuleRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadShippingRules::class,
        ]);

        $this->repository = static::getContainer()->get('doctrine')->getRepository('OroShippingBundle:ShippingRule');
    }

    /**
     * @dataProvider getOrderedRulesByCurrencyDataProvider
     *
     * @param string $currency
     * @param string $country
     * @param array $expectedRules
     */
    public function testGetOrderedRulesByCurrency($currency, $country, array $expectedRules)
    {
        /** @var ShippingRule[]|array $expectedShippingRule */
        $expectedShippingRules = $this->getEntitiesByReferences($expectedRules);
        /** @var ShippingRule $expectedShippingRule */
        $expectedShippingRule = $expectedShippingRules[0];
        $shippingRules = $this->repository->getEnabledOrderedRulesByCurrencyAndCountry(
            $currency,
            $this->findCountry($country)
        );

        static::assertNotFalse(strpos(serialize($shippingRules), $expectedShippingRule->getName()));
        static::assertNotFalse(strpos(serialize($shippingRules), $expectedShippingRule->getCurrency()));
        static::assertNotFalse(strpos(serialize($shippingRules), $expectedShippingRule->getConditions()));
    }

    public function testGetRulesWithoutShippingMethods()
    {
        $rulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods();
        $enabledRulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods(true);

        static::assertCount(2, $rulesWithoutShippingMethods);
        static::assertCount(1, $enabledRulesWithoutShippingMethods);
    }

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
    public function getOrderedRulesByCurrencyDataProvider()
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

    /**
     * @param string $isoCode
     * @return Country
     */
    protected function findCountry($isoCode)
    {
        return static::getContainer()->get('doctrine')
            ->getManagerForClass('OroAddressBundle:Country')
            ->find('OroAddressBundle:Country', $isoCode);
    }
}
