<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Operation;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;

class QuoteFrontendOperationsTest extends FrontendActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            LoadQuoteData::class
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

    /**
     * @param Quote $quote
     * @return Form
     */
    protected function startCheckout(Quote $quote)
    {
        $this->executeOperation($quote, 'oro_checkout_frontend_quote_submit_to_order');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);
        $this->assertTrue($data['success']);

        $crawler = $this->client->request('GET', $data['redirectUrl']);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /* @var $form Form */
        $form = $crawler->selectButton('Submit')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $title = $crawler->filter('.page-main__content .page-title__text');
        $this->assertEquals('Open Order', $title->html());

        $form = $crawler->filter('form[name=oro_workflow_transition]');
        $this->assertEquals(1, $form->count());

        return $form;
    }

    /**
     * @param Quote $quote
     * @param string $operationName
     */
    protected function executeOperation(Quote $quote, $operationName)
    {
        $this->assertExecuteOperation(
            $operationName,
            $quote->getId(),
            Quote::class,
            ['route' => 'oro_sale_quote_frontend_view']
        );
    }
}
