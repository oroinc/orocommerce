<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
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

    public function testObject()
    {
        $this->configManager->set('orob2b_tax.product_prices_include_tax', true);

        $manager = $this->getContainer()->get('orob2b_tax.manager.tax_manager');

        $order = $this->getReference(LoadOrders::ORDER_1);

        $result = $manager->getTax($order);

        $this->assertEquals('789', $result->getTotal()->getIncludingTax());
        $this->assertEquals('717.27', $result->getTotal()->getExcludingTax());
    }

    public function testFirstItem()
    {
        $this->configManager->set('orob2b_tax.product_prices_include_tax', true);

        $manager = $this->getContainer()->get('orob2b_tax.manager.tax_manager');

        $orderLineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_1);

        $result = $manager->getTax($orderLineItem);

        $this->assertEquals('15.99', $result->getUnit()->getIncludingTax());
        $this->assertEquals('14.54', $result->getUnit()->getExcludingTax());
        $this->assertEquals('1.45', $result->getUnit()->getTaxAmount());
        $this->assertEquals('0.0036', $result->getUnit()->getAdjustment());

        $this->assertEquals('79.95', $result->getRow()->getIncludingTax());
        $this->assertEquals('72.68', $result->getRow()->getExcludingTax());
        $this->assertEquals('7.27', $result->getRow()->getTaxAmount());
        $this->assertEquals('0.0018', $result->getRow()->getAdjustment());
    }
}
