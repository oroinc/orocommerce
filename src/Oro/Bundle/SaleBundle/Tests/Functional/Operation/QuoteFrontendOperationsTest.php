<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Operation;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteCompletedCheckoutsData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Response;

class QuoteFrontendOperationsTest extends FrontendActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::LEVEL_1_EMAIL, LoadCustomerUserData::LEVEL_1_PASSWORD)
        );

        $this->loadFixtures([
            LoadOrders::class,
            LoadQuoteData::class,
            LoadQuoteCompletedCheckoutsData::class,
        ]);
    }

    public function testSubmitOrdersFromSingleQuote()
    {
        // start checkout from first user
        $this->loginUser(LoadUserData::ACCOUNT1_USER2);
        $firstData = $this->startCheckout($this->getReference(LoadQuoteData::QUOTE4));
        // continue checkout from first user
        $secondData = $this->startCheckout($this->getReference(LoadQuoteData::QUOTE4));

        $this->assertEquals($firstData->attr('action'), $secondData->attr('action'));

        // start checkout from second user
        $this->loginUser(LoadUserData::ACCOUNT1_USER3);
        $startData = $this->startCheckout($this->getReference(LoadQuoteData::QUOTE4));

        $this->assertNotEquals($firstData->attr('action'), $startData->attr('action'));
    }

    public function testSubmitOrdersFromSingleQuoteNotAllowed()
    {
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);

        $this->loginUser(LoadUserData::ACCOUNT1_USER2);
        $this->executeOperation($quote, 'oro_sale_frontend_quote_submit_to_order', Response::HTTP_FORBIDDEN);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);

        $this->assertArrayHasKey('messages', $data);
        $this->assertEquals(['Quote #sale.quote.1 is no longer available'], $data['messages']);
    }

    public function testCheckoutViewOrderOperation()
    {
        $checkout = $this->getReference(LoadQuoteCompletedCheckoutsData::CHECKOUT_1);

        $this->executeViewCheckoutOperation($checkout, 'oro_checkout_frontend_view_order');
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);
        $this->assertTrue($data['success']);

        $crawler = $this->client->request('GET', $data['redirectUrl']);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        static::assertStringContainsString('Order #' . LoadOrders::ORDER_1, $crawler->html());
    }

    /**
     * @param Quote $quote
     * @return Form
     */
    protected function startCheckout(Quote $quote)
    {
        $this->executeOperation($quote, 'oro_sale_frontend_quote_submit_to_order');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);
        $this->assertTrue($data['success']);

        $crawler = $this->client->request('GET', $data['redirectUrl']);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Submit')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $title = $crawler->filter('.page-main__content .page-title__text');
        $this->assertEquals('Checkout', $title->html());

        $form = $crawler->filter('form[name=oro_workflow_transition]');
        $this->assertEquals(1, $form->count());

        return $form;
    }

    /**
     * @param Quote $quote
     * @param string $operationName
     * @param int $statusCode
     */
    protected function executeOperation(Quote $quote, $operationName, $statusCode = Response::HTTP_OK)
    {
        $this->assertExecuteOperation(
            $operationName,
            $quote->getId(),
            Quote::class,
            ['route' => 'oro_sale_quote_frontend_view'],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            $statusCode
        );
    }

    /**
     * @param Checkout $checkout
     * @param string $operationName
     */
    protected function executeViewCheckoutOperation(Checkout $checkout, $operationName)
    {
        $this->assertExecuteOperation(
            $operationName,
            $checkout->getId(),
            Checkout::class,
            ['datagrid' => 'frontend-checkouts-grid', 'group' => ['datagridRowAction']]
        );
    }
}
