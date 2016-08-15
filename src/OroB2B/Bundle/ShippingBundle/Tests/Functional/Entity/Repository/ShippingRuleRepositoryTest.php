<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules;

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
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            LoadShippingRules::class,
        ]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroB2BShippingBundle:ShippingRule');
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
        $expectedShippingRule = $this->getEntitiesByReferences($expectedRules);
        /** @var ShippingRule $expectedShippingRule */
        $expectedShippingRule = $expectedShippingRule[0];
        $shippingRules = $this->repository->getEnabledOrderedRulesByCurrencyAndCountry(
            $currency,
            $this->findCountry($country)
        );

        $this->assertTrue(false !== strpos(serialize($shippingRules), $expectedShippingRule->getName()));
        $this->assertTrue(false !== strpos(serialize($shippingRules), $expectedShippingRule->getCurrency()));
        $this->assertTrue(false !== strpos(serialize($shippingRules), $expectedShippingRule->getConditions()));
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
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAddressBundle:Country')
            ->find('OroAddressBundle:Country', $isoCode);
    }
}
