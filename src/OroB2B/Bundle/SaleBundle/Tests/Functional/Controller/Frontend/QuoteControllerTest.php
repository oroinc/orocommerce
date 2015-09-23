<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class QuoteControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
        ]);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider indexProvider
     */
    public function testIndex(array $inputData, array $expectedData)
    {
        $this->initClient([], $this->generateBasicAuthHeader($inputData['login'], $inputData['password']));

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_sale_quote_frontend_index'));

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('frontend-quotes-grid', $crawler->html());

        $response = $this->requestFrontendGrid([
            'gridName' => 'frontend-quotes-grid',
            'frontend-quotes-grid[_sort_by][qid]' => 'ASC',
        ]);

        $result = static::getJsonResponseContent($response, 200);

        $data = $result['data'];

        $this->assertEquals(count($expectedData['data']), count($data));

        if (isset($expectedData['columns'])) {
            $testedColumns = array_keys($data[0]);
            $expectedColumns = $expectedData['columns'];

            sort($testedColumns);
            sort($expectedColumns);

            $this->assertEquals($expectedColumns, $testedColumns);
        }

        for ($i = 0; $i < count($expectedData['data']); $i++) {
            foreach ($expectedData['data'][$i] as $key => $value) {
                $this->assertArrayHasKey($key, $data[$i]);
                $this->assertEquals($value, $data[$i][$key]);
            }
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function indexProvider()
    {
        return [
            'account1 user1 (only account user quotes)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER1,
                    'password' => LoadUserData::ACCOUNT1_USER1,
                ],
                'expected' => [
                    'data' => [
                        [
                            'qid' => LoadQuoteData::QUOTE3,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'qid',
                        'createdAt',
                        'validUntil',
                        'view_link',
                        'action_configuration',
                    ],
                ],
            ],
            'account1 user2 (all account qouotes)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER2,
                    'password' => LoadUserData::ACCOUNT1_USER2,
                ],
                'expected' => [
                    'data' => [
                        [
                            'qid' => LoadQuoteData::QUOTE2,
                        ],
                        [
                            'qid' => LoadQuoteData::QUOTE3,
                        ],
                        [
                            'qid' => LoadQuoteData::QUOTE4,
                        ],
                        [
                            'qid' => LoadQuoteData::QUOTE5,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'qid',
                        'createdAt',
                        'validUntil',
                        'view_link',
                        'action_configuration',
                    ],
                ],
            ],
            'account1 user3 (all account quotes and assignedTo)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER3,
                    'password' => LoadUserData::ACCOUNT1_USER3,
                ],
                'expected' => [
                    'data' => [
                        [
                            'qid' => LoadQuoteData::QUOTE2,
                        ],
                        [
                            'qid' => LoadQuoteData::QUOTE3,
                        ],
                        [
                            'qid' => LoadQuoteData::QUOTE4,
                        ],
                        [
                            'qid' => LoadQuoteData::QUOTE5,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'qid',
                        'createdAt',
                        'validUntil',
                        'accountUserName',
                        'view_link',
                        'action_configuration',
                    ],
                ],
            ],
            'account2 user1 (only account user quotes)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT2_USER1,
                    'password' => LoadUserData::ACCOUNT2_USER1,
                ],
                'expected' => [
                    'data' => [
                        [
                            'qid' => LoadQuoteData::QUOTE7,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'qid',
                        'createdAt',
                        'validUntil',
                        'view_link',
                        'action_configuration',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider viewProvider
     */
    public function testView(array $inputData, array $expectedData)
    {
        $this->initClient([], $this->generateBasicAuthHeader($inputData['login'], $inputData['password']));

        /* @var $quote Quote */
        $quote = $this->getReference($inputData['qid']);

        $crawler = $this->client->request('GET', $this->getUrl(
            'orob2b_sale_quote_frontend_view',
            ['id' => $quote->getId()]
        ));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $controls = $crawler->filter('.control-group');

        $this->assertSameSize($expectedData['columns'], $controls);

        /* @var $translator TranslatorInterface */
        $translator = $this->getContainer()->get('translator');

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($controls as $key => $control) {
            /* @var $control \DOMElement */
            $column = $expectedData['columns'][$key];

            $label = $translator->trans($column['label']);
            $property = (string)$accessor->getValue($quote, $column['property']) ?: $translator->trans('N/A');

            $this->assertContains($label, $control->textContent);
            $this->assertContains($property, $control->textContent);
        }

        $createOrderButton = (bool)$crawler->filterXPath('//a[contains(., \'Accept and Submit to Order\')]')->count();

        $this->assertEquals($expectedData['createOrderButton'], $createOrderButton);
    }

    /**
     * @return array
     */
    public function viewProvider()
    {
        return [
            'account1 user1 (AccountUser:VIEW_BASIC)' => [
                'input' => [
                    'qid' => LoadQuoteData::QUOTE3,
                    'login' => LoadUserData::ACCOUNT1_USER1,
                    'password' => LoadUserData::ACCOUNT1_USER1,
                ],
                'expected' => [
                    'createOrderButton' => false,
                    'columns' => [
                        [
                            'label' => 'orob2b.frontend.sale.quote.qid.label',
                            'property' => 'qid',
                        ],
                        [
                            'label' => 'orob2b.frontend.sale.quote.valid_until.label',
                            'property' => 'valid_until',
                        ],
                    ],
                ],
            ],
            'account1 user3 (AccountUser:VIEW_LOCAL)' => [
                'input' => [
                    'qid' => LoadQuoteData::QUOTE3,
                    'login' => LoadUserData::ACCOUNT1_USER3,
                    'password' => LoadUserData::ACCOUNT1_USER3,
                ],
                'expected' => [
                    'createOrderButton' => true,
                    'columns' => [
                        [
                            'label' => 'orob2b.frontend.sale.quote.qid.label',
                            'property' => 'qid',
                        ],
                        [
                            'label' => 'orob2b.frontend.sale.quote.account_user.label',
                            'property' => 'account_user',
                        ],
                        [
                            'label' => 'orob2b.frontend.sale.quote.valid_until.label',
                            'property' => 'valid_until',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider createOrderProvider
     */
    public function testCreateOrder(array $inputData, array $expectedData)
    {
        $this->initClient([], $this->generateBasicAuthHeader($inputData['login'], $inputData['password']));

        /* @var $quote Quote */
        $quote = $this->getReference($inputData['qid']);

        $this->client->followRedirects(true);
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_sale_frontend_quote_create_order', ['id' => $quote->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, $expectedData['statusCode']);
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
                'expected' => [
                    'statusCode' => 403,
                ],
            ],
            'account1 user3 (Order:VIEW_BASIC)' => [
                'input' => [
                    'qid' => LoadQuoteData::QUOTE3,
                    'login' => LoadUserData::ACCOUNT1_USER3,
                    'password' => LoadUserData::ACCOUNT1_USER3,
                ],
                'expected' => [
                    'statusCode' => 200,
                ],
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
                'orob2b_sale_frontend_quote_create_order_from_widget',
                ['id' => $quote->getId(), '_widgetContainer' => 'dialog']
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

            foreach ($form->get('orob2b_sale_quote_to_order') as $key => $row) {
                if (!is_array($row)) {
                    continue;
                }

                /** @var ChoiceFormField $offer */
                $offer = $row['offer'];

                if ($offer->getValue() != $quoteProductOffer->getId()) {
                    continue;
                }

                if ($quoteProductOffer->isAllowIncrements()) {
                    $form['orob2b_sale_quote_to_order['.$key.'][quantity]'] = $customQuantity;
                } else {
                    $form['orob2b_sale_quote_to_order['.$key.'][quantity]'] = $quoteProductOffer->getQuantity();
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
