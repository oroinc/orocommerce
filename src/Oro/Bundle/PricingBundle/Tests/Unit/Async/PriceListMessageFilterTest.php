<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\PricingBundle\Async\PriceListMessageFilter;
use Oro\Bundle\PricingBundle\Async\Topics;

class PriceListMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceListMessageFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new PriceListMessageFilter([
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            Topics::RESOLVE_PRICE_RULES,
            Topics::RESOLVE_COMBINED_PRICES,
            Topics::RESOLVE_COMBINED_CURRENCIES
        ]);
    }

    public function testCollapsePriceListMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_CURRENCIES, ['product' => [1 => [], 2 => []]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_CURRENCIES, ['product' => [3 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [Topics::RESOLVE_COMBINED_CURRENCIES, ['product' => [1 => [], 2 => [], 3 => []]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testCollapsePriceListProductsMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [11, 12], 2 => [11]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [2 => [12]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [3 => [11, 12]]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [11, 12], 2 => [11, 12], 3 => [11, 12]]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testCollapseMixedMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [11, 12], 2 => [11]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [2 => [12], 4 => []]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [5 => [], 6 => [11, 12]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [3 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [11, 12], 2 => [11, 12], 6 => [11, 12]]]],
                3 => [Topics::RESOLVE_COMBINED_PRICES, ['product' => [5 => [], 3 => [], 4 => []]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testRemoveDuplicatedPriceListMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_CURRENCIES, ['product' => [1 => []]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_CURRENCIES, ['product' => [1 => [], 2 => []]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_CURRENCIES, ['product' => [2 => []]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_CURRENCIES, ['product' => [1 => [], 3 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [Topics::RESOLVE_COMBINED_CURRENCIES, ['product' => [1 => [], 2 => [], 3 => []]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testRemoveDuplicatedPriceListProductsMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [11]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [11, 12], 2 => [11]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [2 => [11, 12]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [2 => [12]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [12, 13], 3 => [11, 12]]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [11, 12, 13], 2 => [11, 12], 3 => [11, 12]]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testRemoveRedundantPriceListProductsMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [4 => []]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [11]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [11, 12], 2 => [11]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [2 => [11, 12]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [2 => [12]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => [12, 13], 3 => [11, 12], 4 => [11]]]);
        $buffer->addMessage(Topics::RESOLVE_COMBINED_PRICES, ['product' => [1 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [Topics::RESOLVE_COMBINED_PRICES, ['product' => [4 => [], 1 => []]]],
                3 => [Topics::RESOLVE_COMBINED_PRICES, ['product' => [2 => [11, 12], 3 => [11, 12]]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testRemoveRedundantMessagesForResolvePriceRules()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, ['product' => [2 => [11, 12]]]);
        $buffer->addMessage(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, ['product' => [4 => []]]);
        $buffer->addMessage(Topics::RESOLVE_PRICE_RULES, ['product' => [4 => []]]);
        $buffer->addMessage(Topics::RESOLVE_PRICE_RULES, ['product' => [1 => [11]]]);
        $buffer->addMessage(Topics::RESOLVE_PRICE_RULES, ['product' => [2 => [11, 12]]]);
        $buffer->addMessage(Topics::RESOLVE_PRICE_RULES, ['product' => [1 => [12, 13], 3 => [11, 12], 4 => [11]]]);
        $buffer->addMessage(Topics::RESOLVE_PRICE_RULES, ['product' => [1 => []]]);
        $buffer->addMessage(Topics::RESOLVE_PRICE_RULES, ['product' => [5 => []]]);
        $buffer->addMessage(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, ['product' => [1 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, ['product' => [2 => [11, 12]]]],
                2 => [Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, ['product' => [4 => [], 1 => []]]],
                5 => [Topics::RESOLVE_PRICE_RULES, ['product' => [2 => [11, 12], 3 => [11, 12]]]],
                8 => [Topics::RESOLVE_PRICE_RULES, ['product' => [5 => []]]]
            ],
            $buffer->getMessages()
        );
    }
}
