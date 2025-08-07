<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadOrderTaxesData;
use Oro\Bundle\TaxBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadTaxesData;

/**
 * @dbIsolationPerTest
 */
class CreateOrderWithPromotionsAndTaxesTest extends FrontendRestJsonApiTestCase
{
    private ?string $initialTaxationUseAsBaseOption;
    private ?bool $initialTaxesAfterPromotionsOption;

    #[\Override]
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

        $configManager = self::getConfigManager();
        $this->initialTaxationUseAsBaseOption = $configManager->get('oro_tax.use_as_base_by_default');
        $this->initialTaxesAfterPromotionsOption = $configManager->get('oro_tax.calculate_taxes_after_promotions');
        $configManager->set('oro_tax.use_as_base_by_default', TaxationSettingsProvider::USE_AS_BASE_DESTINATION);
        $configManager->set('oro_tax.calculate_taxes_after_promotions', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_tax.use_as_base_by_default', $this->initialTaxationUseAsBaseOption);
        $configManager->set('oro_tax.calculate_taxes_after_promotions', $this->initialTaxesAfterPromotionsOption);
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

    public function testCreateShouldCalculateTaxes(): void
    {
        $response = $this->post(
            ['entity' => 'orders'],
            '@OroOrderBundle/Tests/Functional/ApiFrontend/RestJsonApi/requests/create_order.yml'
        );

        $responseContent = $this->updateResponseContent('create_order_with_promotions_and_taxes.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }
}
