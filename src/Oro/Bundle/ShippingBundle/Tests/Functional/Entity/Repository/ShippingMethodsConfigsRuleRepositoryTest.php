<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\FlatRateShippingBundle\Tests\Functional\DataFixtures\LoadFlatRateIntegration;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ShippingMethodsConfigsRuleRepositoryTest extends WebTestCase
{
    private ShippingMethodsConfigsRuleRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadShippingMethodsConfigsRulesWithConfigs::class]);

        $this->repository = self::getContainer()->get('doctrine')
            ->getRepository(ShippingMethodsConfigsRule::class);
    }

    private function getEntitiesIds(array $entities): array
    {
        return array_map(function ($entity) {
            return $entity->getId();
        }, $entities);
    }

    private function getFlatRateIdentifier(): string
    {
        return sprintf('flat_rate_%s', $this->getReference(LoadFlatRateIntegration::REFERENCE_FLAT_RATE)->getId());
    }

    /**
     * @dataProvider getByDestinationAndCurrencyDataProvider
     */
    public function testGetByDestinationAndCurrency(array $shippingAddressData, string $currency, array $expectedRules)
    {
        $expectedRulesIds = $this->getEntitiesIds($this->getEntitiesByReferences($expectedRules));
        $actualRules = $this->repository->getByDestinationAndCurrencyAndWebsite(
            $this->createShippingAddress($shippingAddressData),
            $currency
        );

        $this->assertEquals($expectedRulesIds, $this->getEntitiesIds($actualRules));
    }

    public function getByDestinationAndCurrencyDataProvider(): array
    {
        return [
            [
                'shippingAddress' => [
                    'country' => 'US',
                    'region' => [
                        'combinedCode' => 'US-NY',
                        'code' => 'NY',
                    ],
                    'postalCode' => '12345',
                ],
                'currency' => 'EUR',
                'expectedRulesIds' => [
                    'shipping_rule.1',
                    'shipping_rule.2',
                    'shipping_rule.3',
                    'shipping_rule.4',
                    'shipping_rule.5',
                ]
            ],
        ];
    }

    public function testGetByCurrencyWithoutDestination()
    {
        $currency = 'UAH';
        $expectedRules = $this->getEntitiesByReferences([
            'shipping_rule.10',
            'shipping_rule.11'
        ]);

        $actualRules = $this->repository->getByCurrencyAndWebsiteWithoutDestination($currency);

        $this->assertEquals($this->getEntitiesIds($expectedRules), $this->getEntitiesIds($actualRules));
    }

    public function testDisableRulesWithoutShippingMethods()
    {
        $enabledRulesWithoutShippingMethodsQuery = $this->repository->createQueryBuilder('methodsConfigsRule')
            ->select('rule.id')
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodsConfigsRule.rule', 'rule')
            ->andWhere('rule.enabled = true')
            ->having('COUNT(methodConfigs.id) = 0')
            ->groupBy('rule.id')
            ->getQuery();

        // guard
        self::assertCount(3, $enabledRulesWithoutShippingMethodsQuery->getArrayResult());

        $this->repository->disableRulesWithoutShippingMethods();
        self::assertCount(0, $enabledRulesWithoutShippingMethodsQuery->getArrayResult());
    }

    public function testGetRulesByMethod()
    {
        $rulesByExistingMethod = $this->repository->getRulesByMethod($this->getFlatRateIdentifier());

        $expectedRuleReferences = [
            'shipping_rule.1',
            'shipping_rule.2',
            'shipping_rule.3',
            'shipping_rule.4',
            'shipping_rule.5',
            'shipping_rule.6',
            'shipping_rule.7',
            'shipping_rule.9',
            'shipping_rule_without_type_configs',
            'shipping_rule_with_disabled_type_configs',
        ];
        foreach ($expectedRuleReferences as $expectedRuleReference) {
            self::assertContains($this->getReference($expectedRuleReference), $rulesByExistingMethod);
        }

        $rulesByNotExistingMethod = $this->repository->getRulesByMethod('not_existing_method');
        self::assertCount(0, $rulesByNotExistingMethod);
    }

    /**
     * @dataProvider getEnabledRulesByMethodDataProvider
     */
    public function testGetEnabledRulesByMethod(array $expectedRuleReferences)
    {
        $actualRules = $this->repository->getEnabledRulesByMethod($this->getFlatRateIdentifier());

        foreach ($expectedRuleReferences as $expectedRuleReference) {
            self::assertContains($this->getReference($expectedRuleReference), $actualRules);
        }
    }

    public function getEnabledRulesByMethodDataProvider(): array
    {
        return [
            [
                'expectedRuleReferences' => [
                    'shipping_rule.1',
                    'shipping_rule.2',
                    'shipping_rule.4',
                    'shipping_rule.5',
                    'shipping_rule.6',
                    'shipping_rule.7',
                    'shipping_rule.9',
                    'shipping_rule_without_type_configs',
                    'shipping_rule_with_disabled_type_configs',
                ]
            ]
        ];
    }

    private function getEntitiesByReferences(array $rules): array
    {
        return array_map(function ($ruleReference) {
            return $this->getReference($ruleReference);
        }, $rules);
    }

    private function createShippingAddress(array $data): AddressInterface
    {
        $address = new ShippingOrigin();
        $address->setCountry(new Country($data['country']));
        $address->setRegion((new Region($data['region']['combinedCode']))->setCode($data['region']['code']));
        $address->setPostalCode($data['postalCode']);

        return $address;
    }

    public function testGetByCurrency()
    {
        $expectedRules = $this->getEntitiesByReferences([
            'shipping_rule.10',
            'shipping_rule.11',
            'shipping_rule.12'
        ]);

        $this->assertEquals(
            $this->getEntitiesIds($expectedRules),
            $this->getEntitiesIds($this->repository->getByCurrencyAndWebsite('UAH'))
        );
    }

    public function testGetByCurrencyWhenCurrencyNotExists()
    {
        $this->assertEmpty($this->repository->getByCurrencyAndWebsite('WON'));
    }
}
