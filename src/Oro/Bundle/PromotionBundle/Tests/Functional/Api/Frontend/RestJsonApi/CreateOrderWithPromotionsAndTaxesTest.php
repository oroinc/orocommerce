<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadOrderTaxesData;
use Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadTaxesData;

/**
 * @dbIsolationPerTest
 */
class CreateOrderWithPromotionsAndTaxesTest extends FrontendRestJsonApiTestCase
{
    private string $originalTaxationUseAsBaseOption;
    private bool $originalTaxesAfterPromotionsOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadOrderTaxesData::class,
            LoadTaxesData::class,
            LoadPaymentTermData::class,
            LoadPromotionData::class
        ]);

        $configManager = $this->getConfigManager();
        $this->originalTaxationUseAsBaseOption = $configManager->get('oro_tax.use_as_base_by_default');
        $configManager->set('oro_tax.use_as_base_by_default', TaxationSettingsProvider::USE_AS_BASE_DESTINATION);
        $this->originalTaxesAfterPromotionsOption = $configManager->get('oro_tax.calculate_taxes_after_promotions');
        $configManager->set('oro_tax.calculate_taxes_after_promotions', true);
        $configManager->flush();
    }

    protected function tearDown(): void
    {
        $configManager = $this->getConfigManager();
        $configManager->set('oro_tax.use_as_base_by_default', $this->originalTaxationUseAsBaseOption);
        $configManager->set('oro_tax.calculate_taxes_after_promotions', $this->originalTaxesAfterPromotionsOption);
        $configManager->flush();

        parent::tearDown();
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        self::getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($this->getReference('customer'), $this->getReference('payment_term_net_10'));
        $this->getEntityManager()->flush();
    }

    public function testCreateShouldCalculateTaxes(): void
    {
        $response = $this->post(
            ['entity' => 'orders'],
            '@OroOrderBundle/Tests/Functional/Api/Frontend/RestJsonApi/requests/create_order.yml'
        );

        $responseContent = $this->updateResponseContent('create_order_with_promotions_and_taxes.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }
}
