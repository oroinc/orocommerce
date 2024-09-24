<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Brick\Math\BigDecimal;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadOrderTaxesData;
use Oro\Bundle\TaxBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadTaxesData;
use Oro\DBAL\Types\MoneyType;

/**
 * @dbIsolationPerTest
 */
class OrderTaxesTest extends FrontendRestJsonApiTestCase
{
    /** @var string */
    private $originalTaxationUseAsBaseOption;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadOrderTaxesData::class,
            LoadTaxesData::class,
            LoadPaymentTermData::class
        ]);

        $configManager = $this->getConfigManager();
        $this->originalTaxationUseAsBaseOption = $configManager->get('oro_tax.use_as_base_by_default');
        $configManager->set('oro_tax.use_as_base_by_default', TaxationSettingsProvider::USE_AS_BASE_DESTINATION);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = $this->getConfigManager();
        $configManager->set('oro_tax.use_as_base_by_default', $this->originalTaxationUseAsBaseOption);
        $configManager->flush();

        parent::tearDown();
    }

    #[\Override]
    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        self::getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($this->getReference('customer'), $this->getReference('payment_term_net_10'));
        $this->getEntityManager()->flush();
    }

    /**
     * @param mixed $value
     *
     * @return string|null
     */
    private function getMoneyValue($value): ?string
    {
        if (null !== $value) {
            $value = (string)BigDecimal::of($value)->toScale(MoneyType::TYPE_SCALE);
        }

        return $value;
    }

    private function getTaxRelatedFields(?Result $taxValue): array
    {
        if (null === $taxValue) {
            return [
                'totalIncludingTax' => null,
                'totalExcludingTax' => null,
                'totalTaxAmount'    => null
            ];
        }

        return [
            'totalIncludingTax' => $this->getMoneyValue(
                $taxValue->getTotal()->getIncludingTax()
            ),
            'totalExcludingTax' => $this->getMoneyValue(
                $taxValue->getTotal()->getExcludingTax()
            ),
            'totalTaxAmount'    => $this->getMoneyValue(
                $taxValue->getTotal()->getTaxAmount()
            )
        ];
    }

    public function testGetListShouldReturnTaxRelatedFields(): void
    {
        /** @var Result $order1TaxValue */
        $order1TaxValue = $this->getReference('order1_tax_value')->getResult();
        /** @var Result $order2TaxValue */
        $order2TaxValue = $this->getReference('order2_tax_value')->getResult();

        $response = $this->cget(
            ['entity' => 'orders']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order1->id)>',
                        'attributes' => $this->getTaxRelatedFields($order1TaxValue)
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order2->id)>',
                        'attributes' => $this->getTaxRelatedFields($order2TaxValue)
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order3->id)>',
                        'attributes' => $this->getTaxRelatedFields(null)
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order4->id)>',
                        'attributes' => $this->getTaxRelatedFields(null)
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@order5->id)>',
                        'attributes' => $this->getTaxRelatedFields(null)
                    ],
                ]
            ],
            $response
        );
    }

    public function testGetShouldReturnTaxRelatedFields(): void
    {
        /** @var Result $orderTaxValue */
        $orderTaxValue = $this->getReference('order1_tax_value')->getResult();

        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => '<toString(@order1->id)>',
                    'attributes' => $this->getTaxRelatedFields($orderTaxValue)
                ]
            ],
            $response
        );
    }

    public function testGetShouldEmptyValuesIfOrderDoesNotHaveTaxValue(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@order3->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => '<toString(@order3->id)>',
                    'attributes' => $this->getTaxRelatedFields(null)
                ]
            ],
            $response
        );
    }

    public function testCreateShouldCalculateTaxes(): void
    {
        $response = $this->post(
            ['entity' => 'orders'],
            '@OroOrderBundle/Tests/Functional/ApiFrontend/RestJsonApi/requests/create_order.yml'
        );

        $responseContent = $this->updateResponseContent('create_order.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }
}
