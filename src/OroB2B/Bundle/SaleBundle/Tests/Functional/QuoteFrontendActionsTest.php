<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class QuoteFrontendActionsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData'
            ]
        );
    }

    /**
     * @param array $input
     * @param bool $expected
     *
     * @dataProvider createOrderProvider
     */
    public function testCreateOrder(array $input, $expected)
    {
        $this->initClient([], $this->generateBasicAuthHeader($input['login'], $input['password']));

        /* @var $quote Quote */
        $quote = $this->getReference($input['qid']);

        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_api_frontend_action_execute_operations',
                [
                    'operationName' => 'orob2b_sale_frontend_quote_accept_and_submit_to_order',
                    'route' => 'orob2b_sale_quote_frontend_view',
                    'entityId' => $quote->getId(),
                    'entityClass' => 'OroB2B\Bundle\SaleBundle\Entity\Quote'
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, $expected ? 200 : 404);

        if ($expected) {
            $data = json_decode($result->getContent(), true);

            $this->assertArrayHasKey('redirectUrl', $data);
        }
    }

    /**
     * @return array
     */
    public function createOrderProvider()
    {
        return [
            'account1 user1 (Order:NONE)' => [
                'input' => [
                    'qid' => LoadQuoteData::QUOTE3,
                    'login' => LoadUserData::ACCOUNT1_USER1,
                    'password' => LoadUserData::ACCOUNT1_USER1,
                ],
                'expected' => false
            ],
            'account1 user3 (Order:VIEW_BASIC)' => [
                'input' => [
                    'qid' => LoadQuoteData::QUOTE3,
                    'login' => LoadUserData::ACCOUNT1_USER3,
                    'password' => LoadUserData::ACCOUNT1_USER3,
                ],
                'expected' => true
            ],
        ];
    }

    public function testCreateOrderFromWidgetAction()
    {
        $quantity = 12345;
        $orderCount = count($this->getOrderRepository()->findBy([]));

        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER3, LoadUserData::ACCOUNT1_USER3)
        );

        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);
        $this->assertInstanceOf('OroB2B\Bundle\SaleBundle\Entity\Quote', $quote);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_frontend_action_widget_form',
                [
                    'operationName' => 'orob2b_sale_frontend_quote_accept_and_submit_to_order_from_widget',
                    'route' => 'orob2b_sale_quote_frontend_view',
                    'entityId' => $quote->getId(),
                    'entityClass' => 'OroB2B\Bundle\SaleBundle\Entity\Quote'
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /* @var Form $form */
        $form = $crawler->selectButton('Submit')->form();
        $selectedOffers = $this->setFormData($form, $quote, $quantity);

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $orders = $this->getOrderRepository()->findBy([], ['createdAt' => 'DESC']);

        $this->assertCount($orderCount + 1, $orders);

        /** @var \OroB2B\Bundle\OrderBundle\Entity\Order $order */
        $order = reset($orders);

        $this->assertOrderLineItems($order, $selectedOffers, $quantity);
    }

    /**
     * @return ObjectRepository
     */
    protected function getOrderRepository()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BOrderBundle:Order')
            ->getRepository('OroB2BOrderBundle:Order');
    }

    /**
     * @param Form $form
     * @param Quote $quote
     * @param int $customQuantity
     * @return array
     */
    protected function setFormData(Form $form, Quote $quote, $customQuantity)
    {
        $selectedOffers = [];

        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            /** @var \OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer $quoteProductOffer */
            $quoteProductOffer = $quoteProduct->getQuoteProductOffers()->first();

            foreach ($form->get('oro_action_operation[quote_to_order]') as $key => $row) {
                if (!is_array($row)) {
                    continue;
                }

                /** @var ChoiceFormField $offer */
                $offer = $form->get('offer_choice_' . $key);

                if ((int)$offer->getValue() !== (int)$quoteProductOffer->getId()) {
                    continue;
                }

                $quantityKey = 'oro_action_operation[quote_to_order]['.$key.'][quantity]';
                if ($quoteProductOffer->isAllowIncrements()) {
                    $form[$quantityKey] = $customQuantity;
                } else {
                    $form[$quantityKey] = $quoteProductOffer->getQuantity();
                }

                $selectedOffers[] = $quoteProductOffer;
            }
        }

        return $selectedOffers;
    }

    /**
     * @param Order $order
     * @param array $offers
     * @param int $customQuantity
     */
    protected function assertOrderLineItems(Order $order, array $offers, $customQuantity)
    {
        $this->assertCount(count($offers), $order->getLineItems());

        foreach ($order->getLineItems() as $orderLineItem) {
            /** @var QuoteProductOffer $selectedOffer */
            foreach ($offers as $selectedOffer) {
                $quoteProduct = $selectedOffer->getQuoteProduct();

                if ($quoteProduct->getProduct()->getId() === $orderLineItem->getProduct()->getId()) {
                    if ($selectedOffer->isAllowIncrements()) {
                        $this->assertEquals($customQuantity, $orderLineItem->getQuantity());
                    } else {
                        $this->assertEquals($selectedOffer->getQuantity(), $orderLineItem->getQuantity());
                    }
                }
            }
        }
    }
}
