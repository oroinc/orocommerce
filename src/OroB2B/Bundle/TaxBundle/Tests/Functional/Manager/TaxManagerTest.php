<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderItems;

/**
 * @dbIsolation
 */
class TaxManagerTest extends WebTestCase
{
    /** @var ConfigManager */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules',
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderItems',
            ]
        );

        $this->configManager = $this->getContainer()->get('oro_config.global');
    }

    protected function tearDown()
    {
        $this->configManager->reset('orob2b_tax.product_prices_include_tax');
        $this->configManager->flush();

        parent::tearDown();
    }

    /**
     * @dataProvider objectDataProvider
     * @param string|null $orderName
     * @param string|null $includingTax
     * @param string|null $excludingTax
     * @param string|null $taxAmount
     * @param string|null $adjustment
     * @param bool        $includingTaxCalculator
     */
    public function testObject(
        $orderName,
        $includingTax,
        $excludingTax,
        $taxAmount,
        $adjustment,
        $includingTaxCalculator
    ) {
        $this->configManager->set('orob2b_tax.product_prices_include_tax', $includingTaxCalculator);
        $manager = $this->getContainer()->get('orob2b_tax.manager.tax_manager');
        $order = ($orderName) ? $this->getReference($orderName) : new Order();

        $result = $manager->getTax($order);

        $this->assertResult($result->getTotal(), $includingTax, $excludingTax, $taxAmount, $adjustment);
    }

    /**
     * @return array
     */
    public function objectDataProvider()
    {
        return [
            'with saved order and including tax calculator' => [
                'orderName' => LoadOrders::ORDER_1,
                'includingTax' => '789',
                'excludingTax' => '717.27',
                'taxAmount' => '71.73',
                'adjustment' => '-0.0027',
                'includingTaxCalculator' => true
            ],
            'with saved order and tax calculator' => [
                'orderName' => LoadOrders::ORDER_1,
                'includingTax' => '867.9',
                'excludingTax' => '789',
                'taxAmount' => '78.9',
                'adjustment' => '0',
                'includingTaxCalculator' => false
            ],
            'with empty order and including tax calculator' => [
                'orderName' => null,
                'includingTax' => null,
                'excludingTax' => null,
                'taxAmount' => null,
                'adjustment' => null,
                'includingTaxCalculator' => true
            ],
            'with empty order and tax calculator' => [
                'orderName' => null,
                'includingTax' => null,
                'excludingTax' => null,
                'taxAmount' => null,
                'adjustment' => null,
                'includingTaxCalculator' => false
            ]
        ];
    }

    /**
     * @dataProvider orderItemDataProvider
     * @param string $orderItemName
     * @param bool   $includingTaxCalculator
     * @param string $unit
     * @param string $row
     */
    public function testGetTaxFirstItem($orderItemName, $includingTaxCalculator, $unit, $row)
    {
        $this->configManager->set('orob2b_tax.product_prices_include_tax', $includingTaxCalculator);

        $manager = $this->getContainer()->get('orob2b_tax.manager.tax_manager');

        $order = $this->getReference(LoadOrders::ORDER_1);

        $orderLineItem =
            ($orderItemName) ? $this->getReference($orderItemName) : (new OrderLineItem())->setOrder($order);

        $result = $manager->getTax($orderLineItem);

        $this->assertResult(
            $result->getUnit(),
            $unit['includingTax'],
            $unit['excludingTax'],
            $unit['amount'],
            $unit['adjustment']
        );

        $this->assertResult(
            $result->getRow(),
            $row['includingTax'],
            $row['excludingTax'],
            $row['amount'],
            $row['adjustment']
        );
    }

    /**
     * @return array
     */
    public function orderItemDataProvider()
    {
        return [
            'with saved OrderItem and including tax calculator' => [
                'orderItemName' => LoadOrderItems::ORDER_ITEM_1,
                'includingTaxCalculator' => true,
                'unit' => [
                    'includingTax' => '15.99',
                    'excludingTax' => '14.54',
                    'amount' => '1.45',
                    'adjustment' => '0.0036',
                ],
                'row' => [
                    'includingTax' => '79.95',
                    'excludingTax' => '72.68',
                    'amount' => '7.27',
                    'adjustment' => '-0.0018',
                ]
            ],
            'with saved OrderItem and tax calculator' => [
                'orderItemName' => LoadOrderItems::ORDER_ITEM_1,
                'includingTaxCalculator' => false,
                'unit' => [
                    'includingTax' => '17.59',
                    'excludingTax' => '15.99',
                    'amount' => '1.6',
                    'adjustment' => '-0.001',
                ],
                'row' => [
                    'includingTax' => '87.95',
                    'excludingTax' => '79.95',
                    'amount' => '8',
                    'adjustment' => '-0.005',
                ]
            ],
            'with empty OrderItem and including tax calculator' => [
                'orderItemName' => null,
                'includingTaxCalculator' => true,
                'unit' => [
                    'includingTax' => null,
                    'excludingTax' => null,
                    'amount' => null,
                    'adjustment' => null,
                ],
                'row' => [
                    'includingTax' => null,
                    'excludingTax' => null,
                    'amount' => null,
                    'adjustment' => null,
                ]
            ],
            'with empty OrderItem and tax calculator' => [
                'orderItemName' => null,
                'includingTaxCalculator' => false,
                'unit' => [
                    'includingTax' => null,
                    'excludingTax' => null,
                    'amount' => null,
                    'adjustment' => null,
                ],
                'row' => [
                    'includingTax' => null,
                    'excludingTax' => null,
                    'amount' => null,
                    'adjustment' => null,
                ]
            ]
        ];
    }

    /**
     * @dataProvider testSaveObjectDataProvider
     * @param string|null $orderName
     * @param bool        $includingTaxCalculator
     * @param bool        $hasResult
     * @param array       $expected
     */
    public function testSaveTaxObject(
        $orderName,
        $includingTaxCalculator,
        $hasResult,
        $expected
    ) {
        $this->configManager->set('orob2b_tax.product_prices_include_tax', $includingTaxCalculator);
        $manager = $this->getContainer()->get('orob2b_tax.manager.tax_manager');

        $order = ($orderName) ? $this->getReference($orderName) : new Order();
        $result = $manager->saveTax($order);

        if (!$hasResult) {
            $this->assertFalse($result);
        } else {
            $this->assertResult(
                $result->getTotal(),
                $expected['includingTax'],
                $expected['excludingTax'],
                $expected['taxAmount'],
                $expected['adjustment']
            );

            $savedResult = $manager->loadTax($order);
            $this->assertResult(
                $savedResult->getTotal(),
                $expected['includingTax'],
                $expected['excludingTax'],
                $expected['taxAmount'],
                $expected['adjustment']
            );
        }
    }

    /**
     * @return array
     */
    public function testSaveObjectDataProvider()
    {
        return [
            'with saved order and including tax calculator' => [
                'orderName' => LoadOrders::ORDER_1,
                'includingTaxCalculator' => true,
                'hasResult' => true,
                'expected' => [
                    'includingTax' => '789',
                    'excludingTax' => '717.27',
                    'taxAmount' => '71.73',
                    'adjustment' => '-0.0027',
                ],
            ],
            'with saved order and tax calculator' => [
                'orderName' => LoadOrders::ORDER_1,
                'includingTaxCalculator' => false,
                'hasResult' => true,
                'expected' => [
                    'includingTax' => '867.9',
                    'excludingTax' => '789',
                    'taxAmount' => '78.9',
                    'adjustment' => '0',
                ],
            ],
            'with empty order and including tax calculator' => [
                'orderName' => null,
                'includingTaxCalculator' => true,
                'hasResult' => false,
                'expected' => [],
            ],
            'with empty order and tax calculator' => [
                'orderName' => null,
                'includingTaxCalculator' => false,
                'hasResult' => false,
                'expected' => [],
            ]
        ];
    }

    /**
     * @dataProvider testSaveItemDataProvider
     * @param string|null $orderItemName
     * @param bool        $includingTaxCalculator
     * @param bool        $hasResult
     * @param array       $expected
     */
    public function testSaveItemTaxObject(
        $orderItemName,
        $includingTaxCalculator,
        $hasResult,
        $expected
    ) {
        $this->configManager->set('orob2b_tax.product_prices_include_tax', $includingTaxCalculator);
        $manager = $this->getContainer()->get('orob2b_tax.manager.tax_manager');

        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderItem = ($orderItemName) ? $this->getReference($orderItemName) : (new OrderLineItem())->setOrder($order);

        $result = $manager->saveTax($orderItem);

        if (!$hasResult) {
            $this->assertFalse($result);
        } else {
            $this->assertResult(
                $result->getUnit(),
                $expected['unit']['includingTax'],
                $expected['unit']['excludingTax'],
                $expected['unit']['taxAmount'],
                $expected['unit']['adjustment']
            );

            $this->assertResult(
                $result->getRow(),
                $expected['row']['includingTax'],
                $expected['row']['excludingTax'],
                $expected['row']['taxAmount'],
                $expected['row']['adjustment']
            );

            $savedResult = $manager->loadTax($orderItem);

            $this->assertResult(
                $savedResult->getUnit(),
                $expected['unit']['includingTax'],
                $expected['unit']['excludingTax'],
                $expected['unit']['taxAmount'],
                $expected['unit']['adjustment']
            );

            $this->assertResult(
                $savedResult->getRow(),
                $expected['row']['includingTax'],
                $expected['row']['excludingTax'],
                $expected['row']['taxAmount'],
                $expected['row']['adjustment']
            );
        }
    }

    /**
     * @return array
     */
    public function testSaveItemDataProvider()
    {
        return [
            'with saved order and including tax calculator' => [
                'orderItemName' => LoadOrderItems::ORDER_ITEM_1,
                'includingTaxCalculator' => true,
                'hasResult' => true,
                'expected' => [
                    'unit' => [
                        'includingTax' => '15.99',
                        'excludingTax' => '14.54',
                        'taxAmount' => '1.45',
                        'adjustment' => '0.0036',
                    ],
                    'row' => [
                        'includingTax' => '79.95',
                        'excludingTax' => '72.68',
                        'taxAmount' => '7.27',
                        'adjustment' => '-0.0018',
                    ]
                ],
            ],
            'with saved order and tax calculator' => [
                'orderItemName' => LoadOrderItems::ORDER_ITEM_1,
                'includingTaxCalculator' => false,
                'hasResult' => true,
                'expected' => [
                    'unit' => [
                        'includingTax' => '17.59',
                        'excludingTax' => '15.99',
                        'taxAmount' => '1.6',
                        'adjustment' => '-0.001',
                    ],
                    'row' => [
                        'includingTax' => '87.95',
                        'excludingTax' => '79.95',
                        'taxAmount' => '8',
                        'adjustment' => '-0.005',
                    ]
                ],
            ],
            'with empty order and including tax calculator' => [
                'orderItemName' => null,
                'includingTaxCalculator' => true,
                'hasResult' => false,
                'expected' => [],
            ],
            'with empty order and tax calculator' => [
                'orderItemName' => null,
                'includingTaxCalculator' => false,
                'hasResult' => false,
                'expected' => []
            ]
        ];
    }

    /**
     * @param ResultElement $resultElement
     * @param string|null   $includingTax
     * @param string|null   $excludingTax
     * @param string|null   $amount
     * @param string|null   $adjustment
     */
    protected function assertResult(ResultElement $resultElement, $includingTax, $excludingTax, $amount, $adjustment)
    {
        $this->assertEquals($includingTax, $resultElement->getIncludingTax());
        $this->assertEquals($excludingTax, $resultElement->getExcludingTax());
        $this->assertEquals($amount, $resultElement->getTaxAmount());
        $this->assertEquals($adjustment, $resultElement->getAdjustment());
    }
}
