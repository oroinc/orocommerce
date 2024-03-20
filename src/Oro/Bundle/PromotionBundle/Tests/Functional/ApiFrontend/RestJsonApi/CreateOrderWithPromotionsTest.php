<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;

/**
 * @dbIsolationPerTest
 */
class CreateOrderWithPromotionsTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml',
            LoadPaymentTermData::class,
            LoadPromotionData::class
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        self::getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($this->getReference('customer'), $this->getReference('payment_term_net_10'));
        $this->getEntityManager()->flush();
    }

    public function testCreateOrderWithPromotions()
    {
        $response = $this->post(
            ['entity' => 'orders'],
            'create_order_with_promotions.yml'
        );

        $responseContent = $this->updateResponseContent('create_order_with_promotions.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }
}
