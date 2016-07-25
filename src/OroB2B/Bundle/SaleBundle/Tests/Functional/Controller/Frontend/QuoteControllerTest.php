<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
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
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteAddressData',
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

        $response = $this->client->requestGrid([
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
                        'poNumber',
                        'shipUntil',
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
                        [
                            'qid' => LoadQuoteData::QUOTE8,
                        ],
                        [
                            'qid' => LoadQuoteData::QUOTE9,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'qid',
                        'createdAt',
                        'validUntil',
                        'poNumber',
                        'shipUntil',
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
                        [
                            'qid' => LoadQuoteData::QUOTE8,
                        ],
                        [
                            'qid' => LoadQuoteData::QUOTE9,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'qid',
                        'createdAt',
                        'validUntil',
                        'accountUserName',
                        'poNumber',
                        'shipUntil',
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
                        'poNumber',
                        'shipUntil',
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

        $controls = $crawler->filter('.account-oq__order-info__control, .page-title');

        $this->assertSameSize($expectedData['columns'], $controls);

        /* @var $translator TranslatorInterface */
        $translator = $this->getContainer()->get('translator');

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($controls as $key => $control) {
            /* @var $control \DOMElement */
            $column = $expectedData['columns'][$key];

            $label = $translator->trans($column['label']);
            $property = $accessor->getValue($quote, $column['property']) ?: $translator->trans('N/A');
            if ($property instanceof \DateTime) {
                $property = $property->format('M j, Y');
            } elseif ($property instanceof Price) {
                $property = round($property->getValue());
            }

            $property = (string)$property;
            $this->assertContains($label, $control->textContent);
            $this->assertContains($property, $control->textContent);
        }

        $createOrderButton = (bool)$crawler
            ->filterXPath('//a[contains(., \'Accept and Submit to Order\')]')->count();

        $this->assertEquals($expectedData['createOrderButton'], $createOrderButton);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                        [
                            'label' => 'orob2b.sale.quote.po_number.label',
                            'property' => 'po_number',
                        ],
                        [
                            'label' => 'orob2b.sale.quote.ship_until.label',
                            'property' => 'ship_until',
                        ],
                        [
                            'label' => 'orob2b.frontend.sale.quote.ship_estimate.label',
                            'property' => 'shipping_estimate',
                        ],
                        [
                            'label' => 'orob2b.sale.quote.sections.shipping_address',
                            'property' => 'shippingAddress.street',
                        ]
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
                            'label' => 'orob2b.frontend.sale.quote.valid_until.label',
                            'property' => 'valid_until',
                        ],
                        [
                            'label' => 'orob2b.sale.quote.po_number.label',
                            'property' => 'po_number',
                        ],
                        [
                            'label' => 'orob2b.sale.quote.ship_until.label',
                            'property' => 'ship_until',
                        ],
                        [
                            'label' => 'orob2b.frontend.sale.quote.ship_estimate.label',
                            'property' => 'shipping_estimate',
                        ],
                        [
                            'label' => 'orob2b.sale.quote.sections.shipping_address',
                            'property' => 'shippingAddress.street',
                        ]
                    ],
                ],
            ],
            'account1 user3 (AccountUser:VIEW_LOCAL, Quote date)' => [
                'input' => [
                    'qid' => LoadQuoteData::QUOTE5,
                    'login' => LoadUserData::ACCOUNT1_USER3,
                    'password' => LoadUserData::ACCOUNT1_USER3,
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
                        [
                            'label' => 'orob2b.sale.quote.po_number.label',
                            'property' => 'po_number',
                        ],
                        [
                            'label' => 'orob2b.sale.quote.ship_until.label',
                            'property' => 'ship_until',
                        ],
                    ],
                ],
            ],
            'account1 user3 (AccountUser:VIEW_LOCAL, Quote expired)' => [
                'input' => [
                    'qid' => LoadQuoteData::QUOTE8,
                    'login' => LoadUserData::ACCOUNT1_USER3,
                    'password' => LoadUserData::ACCOUNT1_USER3,
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
                        [
                            'label' => 'orob2b.sale.quote.po_number.label',
                            'property' => 'po_number',
                        ],
                        [
                            'label' => 'orob2b.sale.quote.ship_until.label',
                            'property' => 'ship_until',
                        ],
                    ],
                ],
            ],
            'account1 user3 (AccountUser:VIEW_LOCAL, null Quote date)' => [
                'input' => [
                    'qid' => LoadQuoteData::QUOTE9,
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
                            'label' => 'orob2b.frontend.sale.quote.valid_until.label',
                            'property' => 'valid_until',
                        ],
                        [
                            'label' => 'orob2b.sale.quote.po_number.label',
                            'property' => 'po_number',
                        ],
                        [
                            'label' => 'orob2b.sale.quote.ship_until.label',
                            'property' => 'ship_until',
                        ],
                    ],
                ],
            ],
        ];
    }
}
