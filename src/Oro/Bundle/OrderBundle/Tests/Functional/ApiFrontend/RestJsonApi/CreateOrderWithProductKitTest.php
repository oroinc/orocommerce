<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadPaymentTermData;

/**
 * @dbIsolationPerTest
 */
class CreateOrderWithProductKitTest extends FrontendRestJsonApiTestCase
{
    use OrderResponseTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
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

    public function testCreateWithoutKitItemLineItemProductRelationshipButWithProductSku(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][4]['attributes']['productSku'] = '@product-1->sku';
        unset($data['included'][4]['relationships']['product']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $responseContent = $this->updateOrderResponseContent('create_order_min.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateWithSubmittedNullPrice(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][4]['attributes']['price'] = null;

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][0]['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][1]['id'] = 'new';
        $expectedData['data']['relationships']['billingAddress']['data']['id'] = 'new';
        $expectedData['data']['relationships']['shippingAddress']['data']['id'] = 'new';
        $expectedData['included'][0]['id'] = 'new';
        $expectedData['included'][1]['id'] = 'new';
        $expectedData['included'][2]['id'] = 'new';
        $expectedData['included'][3]['id'] = 'new';
        $expectedData['included'][4]['id'] = 'new';
        $expectedData['included'][4]['attributes']['price'] = '11.5900';
        $expectedData['included'][3]['relationships']['kitItemLineItems']['data'][0]['id'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateWithSubmittedNullCurrency(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][4]['attributes']['currency'] = null;

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][0]['id'] = 'new';
        $expectedData['data']['relationships']['lineItems']['data'][1]['id'] = 'new';
        $expectedData['data']['relationships']['billingAddress']['data']['id'] = 'new';
        $expectedData['data']['relationships']['shippingAddress']['data']['id'] = 'new';
        $expectedData['included'][0]['id'] = 'new';
        $expectedData['included'][1]['id'] = 'new';
        $expectedData['included'][2]['id'] = 'new';
        $expectedData['included'][3]['id'] = 'new';
        $expectedData['included'][4]['id'] = 'new';
        $expectedData['included'][4]['attributes']['currency'] = 'USD';
        $expectedData['included'][3]['relationships']['kitItemLineItems']['data'][0]['id'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }
}
