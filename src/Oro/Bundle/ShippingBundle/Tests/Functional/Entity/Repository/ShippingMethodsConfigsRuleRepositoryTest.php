<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules;
use Oro\Bundle\ShippingBundle\Tests\Functional\Helper\FlatRateIntegrationTrait;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingAddressStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolationPerTest
 */
class ShippingMethodsConfigsRuleRepositoryTest extends WebTestCase
{
    use EntityTrait;
    use FlatRateIntegrationTrait;

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
     * @dataProvider getByDestinationAndCurrencyDataProvider
     *
     * @param array                        $shippingAddressData
     * @param string                       $currency
     * @param ShippingMethodsConfigsRule[] $expectedRules
     */
    public function testGetByDestinationAndCurrency(array $shippingAddressData, $currency, array $expectedRules)
    {
        $expectedRules = $this->getEntitiesByReferences($expectedRules);
        $actualRules = $this->repository->getByDestinationAndCurrency(
            $this->createShippingAddress($shippingAddressData),
            $currency
        );

        static::assertEquals(count($expectedRules), count($actualRules));
        static::assertTrue(in_array($expectedRules[0], $actualRules, true));
        static::assertTrue(in_array($expectedRules[1], $actualRules, true));
        static::assertTrue(in_array($expectedRules[2], $actualRules, true));
        static::assertTrue(in_array($expectedRules[3], $actualRules, true));
        static::assertTrue(in_array($expectedRules[4], $actualRules, true));
    }

    /**
     * @return array
     */
    public function getByDestinationAndCurrencyDataProvider()
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
                'expectedRules' => [
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
            'shipping_rule.11',
        ]);

        $actualRules = $this->repository->getByCurrencyWithoutDestination($currency);

        static::assertEquals(count($expectedRules), count($actualRules));
        static::assertTrue(in_array($expectedRules[0], $actualRules, true));
        static::assertTrue(in_array($expectedRules[1], $actualRules, true));
    }

    public function testGetRulesWithoutShippingMethods()
    {
        $rulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods();
        $enabledRulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods(true);

        static::assertCount(4, $rulesWithoutShippingMethods);
        static::assertCount(3, $enabledRulesWithoutShippingMethods);
    }

    public function testDisableRulesWithoutShippingMethods()
    {
        $this->repository->disableRulesWithoutShippingMethods();

        $rulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods();
        $enabledRulesWithoutShippingMethods = $this->repository->getRulesWithoutShippingMethods(true);

        static::assertCount(4, $rulesWithoutShippingMethods);
        static::assertCount(0, $enabledRulesWithoutShippingMethods);
    }

    public function testGetRulesByMethod()
    {
        $rulesByExistingMethod = $this->repository->getRulesByMethod($this->getFlatRateIdentifier());
        $rulesByNotExistingMethod = $this->repository->getRulesByMethod('not_existing_method');

        static::assertCount(9, $rulesByExistingMethod);
        static::assertCount(0, $rulesByNotExistingMethod);
    }

    /**
     * @param array $rules
     *
     * @return array
     */
    protected function getEntitiesByReferences(array $rules)
    {
        return array_map(function ($ruleReference) {
            return $this->getReference($ruleReference);
        }, $rules);
    }

    /**
     * @param array $data
     *
     * @return AddressInterface|object
     */
    protected function createShippingAddress(array $data)
    {
        return $this->getEntity(ShippingAddressStub::class, [
            'country' => new Country($data['country']),
            'region' => $this->getEntity(Region::class, [
                'combinedCode' => $data['region']['combinedCode'],
                'code' => $data['region']['code'],
            ]),
            'postalCode' => $data['postalCode'],
        ]);
    }
}
