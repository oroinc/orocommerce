<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingAddressStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingMethodsConfigsRuleRepositoryTest extends WebTestCase
{
    use EntityTrait;

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
     * @param array $entities
     * @return array
     */
    private function getEntitiesIds(array $entities)
    {
        return array_map(function ($entity) {
            return $entity->getId();
        }, $entities);
    }

    /**
     * @dataProvider getByDestinationAndCurrencyDataProvider
     * @param array $shippingAddressData
     * @param string $currency
     * @param array|ShippingMethodsConfigsRule[] $expectedRules
     */
    public function testGetByDestinationAndCurrency(array $shippingAddressData, $currency, array $expectedRules)
    {
        $expectedRulesIds = $this->getEntitiesIds($this->getEntitiesByReferences($expectedRules));
        $actualRules = $this->repository->getByDestinationAndCurrency(
            $this->createShippingAddress($shippingAddressData),
            $currency
        );

        $this->assertEquals($expectedRulesIds, $this->getEntitiesIds($actualRules));
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

        $actualRules = $this->repository->getByCurrencyWithoutDestination($currency);

        $this->assertEquals($this->getEntitiesIds($expectedRules), $this->getEntitiesIds($actualRules));
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
     * @param array $data
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

    public function testGetByCurrency()
    {
        $expectedRules = $this->getEntitiesByReferences([
            'shipping_rule.10',
            'shipping_rule.11',
            'shipping_rule.12'
        ]);

        $this->assertEquals(
            $this->getEntitiesIds($expectedRules),
            $this->getEntitiesIds($this->repository->getByCurrency('UAH'))
        );
    }

    public function testGetByCurrencyWhenCurrencyNotExists()
    {
        $this->assertEmpty($this->repository->getByCurrency('WON'));
    }
}
