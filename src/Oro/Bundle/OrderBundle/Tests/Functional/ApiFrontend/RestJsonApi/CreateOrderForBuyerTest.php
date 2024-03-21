<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;

/**
 * @dbIsolationPerTest
 */
class CreateOrderForBuyerTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroOrderBundle/Tests/Functional/ApiFrontend/DataFixtures/orders.yml',
            LoadPaymentTermData::class
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        self::getContainer()->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($this->getReference('customer'), $this->getReference('payment_term_net_10'));
        $this->getEntityManager()->flush();
    }

    public function testCreate()
    {
        $response = $this->post(
            ['entity' => 'orders'],
            'create_order.yml'
        );

        $responseContent = $this->updateResponseContent('create_order.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithProductKit(): void
    {
        $shipUntil = (new \DateTime('now + 10 day'))->format('Y-m-d');
        $data = $this->getRequestData('create_order_with_product_kit.yml');
        $data['data']['attributes']['shipUntil'] = $shipUntil;

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->getResponseData('create_order_with_product_kit.yml');
        $responseContent['data']['attributes']['shipUntil'] = $shipUntil;

        $newProductKitLineItemItemId = self::getNewResourceIdFromIncludedSection($response, 'productKitLineItem1');
        /** @var OrderLineItem $lineItem */
        $productKitLineItem = $this->getEntityManager()->find(OrderLineItem::class, $newProductKitLineItemItemId);

        self::assertNotEmpty($productKitLineItem->getChecksum());
        $responseContent['included'][1]['attributes']['checksum'] = $productKitLineItem->getChecksum();

        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);
    }
}
