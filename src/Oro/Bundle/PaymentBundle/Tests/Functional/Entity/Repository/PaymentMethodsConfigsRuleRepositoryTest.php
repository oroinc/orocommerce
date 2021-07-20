<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleDestinationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PaymentMethodsConfigsRuleRepositoryTest extends WebTestCase
{
    /**
     * @var PaymentMethodsConfigsRuleRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroPaymentBundle:PaymentMethodsConfigsRule');

        $this->loadFixtures(
            [
                LoadPaymentMethodsConfigsRuleData::class,
                LoadPaymentMethodsConfigsRuleDestinationData::class
            ]
        );
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

    public function testFindAll()
    {
        $allConfigsRules = $this->repository->findAll();

        $this->assertEquals(6, count($allConfigsRules));
    }

    /**
     * @dataProvider getByDestinationAndCurrencyTestProvider
     */
    public function testGetByDestinationAndCurrency(array $data)
    {
        /** @var Country $country */
        $country = $this
            ->getContainer()
            ->get('doctrine')
            ->getRepository('OroAddressBundle:Country')
            ->findOneBy(['iso2Code' => $data['iso2Code']]);

        /** @var Region $region */
        $region = $this
            ->getContainer()
            ->get('doctrine')
            ->getRepository('OroAddressBundle:Region')
            ->findOneBy(['combinedCode' => $data['combinedRegionCode']]);

        $billingAddress = (new Address())
            ->setCountry($country)
            ->setRegion($region)
            ->setPostalCode($data['postalCode']);

        $currency = $data['currency'];

        $expectedConfigsRules = $this->getConfigsRulesByReferences($data['expectedEntityReferences']);
        $configsRules = $this->repository->getByDestinationAndCurrencyAndWebsite($billingAddress, $currency);

        $this->assertEquals($this->getEntitiesIds($expectedConfigsRules), $this->getEntitiesIds($configsRules));
    }

    /**
     * @return array
     */
    public function getByDestinationAndCurrencyTestProvider()
    {
        return [
            1 => [
                [
                    'iso2Code' => 'AE',
                    'combinedRegionCode' => 'AE-AJ',
                    'postalCode' => '12345',
                    'currency' => 'EUR',
                    'expectedEntityReferences' => [
                        'payment.payment_methods_configs_rule.2',
                        'payment.payment_methods_configs_rule.4'
                    ]
                ],
            ],
            2 => [
                [
                    'iso2Code' => 'AE',
                    'combinedRegionCode' => 'AE-AJ',
                    'postalCode' => '12345',
                    'currency' => 'UAH',
                    'expectedEntityReferences' => [
                        'payment.payment_methods_configs_rule.5',
                        'payment.payment_methods_configs_rule.6',
                    ]
                ],
            ],
            3 => [
                [
                    'iso2Code' => 'AD',
                    'combinedRegionCode' => 'AD-02',
                    'postalCode' => '12345',
                    'currency' => 'UAH',
                    'expectedEntityReferences' => [
                        'payment.payment_methods_configs_rule.5',
                        'payment.payment_methods_configs_rule.6',
                    ],
                ],
            ],
            4 => [
                [
                    'iso2Code' => 'AD',
                    'combinedRegionCode' => 'AD-02',
                    'postalCode' => '12123345',
                    'currency' => 'UAH',
                    'expectedEntityReferences' => [
                        'payment.payment_methods_configs_rule.5',
                        'payment.payment_methods_configs_rule.6',
                    ]
                ],
            ],
            5 => [
                [
                    'iso2Code' => 'AD',
                    'combinedRegionCode' => 'AD-02',
                    'postalCode' => '43561',
                    'currency' => 'UAH',
                    'expectedEntityReferences' => [
                        'payment.payment_methods_configs_rule.5',
                        'payment.payment_methods_configs_rule.6',
                    ]
                ],
            ]
        ];
    }

    public function testGetByCurrencyWithoutDestination()
    {
        $expectedConfigsRules = $this->getConfigsRulesByReferences([
            'payment.payment_methods_configs_rule.5',
            'payment.payment_methods_configs_rule.6',
        ]);

        $configsRules = $this->repository->getByCurrencyAndWebsiteWithoutDestination('UAH');

        $this->assertEquals($this->getEntitiesIds($expectedConfigsRules), $this->getEntitiesIds($configsRules));
    }

    public function testGetByCurrency()
    {
        $expectedConfigsRules = $this->getConfigsRulesByReferences([
            'payment.payment_methods_configs_rule.3',
            'payment.payment_methods_configs_rule.5',
            'payment.payment_methods_configs_rule.6',
        ]);

        $configsRules = $this->repository->getByCurrencyAndWebsite('UAH');

        $this->assertEquals($this->getEntitiesIds($expectedConfigsRules), $this->getEntitiesIds($configsRules));
    }

    public function testGetByCurrencyWhenCurrencyNotExists()
    {
        $configsRules = $this->repository->getByCurrencyAndWebsite('WON');

        $this->assertEmpty($configsRules);
    }

    /**
     * @param array $configsRulesReferences
     * @return array
     */
    private function getConfigsRulesByReferences(array $configsRulesReferences)
    {
        $configsRules = [];

        foreach ($configsRulesReferences as $ruleReference) {
            $configsRules[] = $this->getReference($ruleReference);
        }

        return $configsRules;
    }
}
