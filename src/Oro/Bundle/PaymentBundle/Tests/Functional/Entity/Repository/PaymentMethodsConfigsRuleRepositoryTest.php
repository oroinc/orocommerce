<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class PaymentMethodsConfigsRuleRepositoryTest extends WebTestCase
{
    /**
     * @var PaymentMethodsConfigsRuleRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroPaymentBundle:PaymentMethodsConfigsRule');

        $currentBundleDataFixturesNameSpace = 'Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures';
        $this->loadFixtures(
            [
                $currentBundleDataFixturesNameSpace.'\LoadPaymentMethodsConfigsRuleData',
                $currentBundleDataFixturesNameSpace.'\LoadPaymentMethodsConfigsRuleDestinationData',
            ]
        );
    }

    public function testFindAll()
    {
        $allConfigsRules = $this->repository->findAll();

        $this->assertEquals(4, count($allConfigsRules));
    }

    /**
     * @param array $data
     *
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
        $configsRules = $this->repository->getByDestinationAndCurrency($billingAddress, $currency);

        $this->assertEquals(sort($expectedConfigsRules), sort($configsRules));
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
                        'payment.payment_methods_configs_rule.4',
                        'payment.payment_methods_configs_rule.2',
                    ]
                ],
            ],
            2 => [
                [
                    'iso2Code' => 'AE',
                    'combinedRegionCode' => 'AE-AJ',
                    'postalCode' => '12345',
                    'currency' => 'USD',
                    'expectedEntityReferences' => []
                ],
            ],
            3 => [
                [
                    'iso2Code' => 'AD',
                    'combinedRegionCode' => 'AD-02',
                    'postalCode' => '12345',
                    'currency' => 'USD',
                    'expectedEntityReferences' => [
                        'payment.payment_methods_configs_rule.1'
                    ],
                ],
            ],
            4 => [
                [
                    'iso2Code' => 'AD',
                    'combinedRegionCode' => 'AD-02',
                    'postalCode' => '12123345',
                    'currency' => 'USD',
                    'expectedEntityReferences' => []
                ],
            ],
            5 => [
                [
                    'iso2Code' => 'AD',
                    'combinedRegionCode' => 'AD-02',
                    'postalCode' => '43561',
                    'currency' => 'USD',
                    'expectedEntityReferences' => [
                        'payment.payment_methods_configs_rule.4'
                    ]
                ],
            ]
        ];
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
