<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\PricingBundle\Async\PriceListMessageFilter;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceListCurrenciesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;

class PriceListMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceListMessageFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new PriceListMessageFilter([
            ResolvePriceListAssignedProductsTopic::getName(),
            ResolvePriceRulesTopic::getName(),
            ResolveCombinedPriceByPriceListTopic::getName(),
            ResolveCombinedPriceListCurrenciesTopic::getName()
        ]);
    }

    public function testCollapsePriceListMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(
            ResolveCombinedPriceListCurrenciesTopic::getName(),
            ['product' => [1 => [], 2 => []]]
        );
        $buffer->addMessage(ResolveCombinedPriceListCurrenciesTopic::getName(), ['product' => [3 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [ResolveCombinedPriceListCurrenciesTopic::getName(), ['product' => [1 => [], 2 => [], 3 => []]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testCollapsePriceListProductsMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [1 => [11, 12], 2 => [11]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [2 => [12]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [3 => [11, 12]]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [
                    ResolveCombinedPriceByPriceListTopic::getName(),
                    ['product' => [1 => [11, 12], 2 => [11, 12], 3 => [11, 12]]]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testCollapseMixedMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [1 => [11, 12], 2 => [11]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [2 => [12], 4 => []]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [5 => [], 6 => [11, 12]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [3 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [
                    ResolveCombinedPriceByPriceListTopic::getName(),
                    ['product' => [1 => [11, 12], 2 => [11, 12], 6 => [11, 12]]]
                ],
                3 => [ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [5 => [], 3 => [], 4 => []]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testRemoveDuplicatedPriceListMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(ResolveCombinedPriceListCurrenciesTopic::getName(), ['product' => [1 => []]]);
        $buffer->addMessage(ResolveCombinedPriceListCurrenciesTopic::getName(), ['product' => [1 => [], 2 => []]]);
        $buffer->addMessage(ResolveCombinedPriceListCurrenciesTopic::getName(), ['product' => [2 => []]]);
        $buffer->addMessage(ResolveCombinedPriceListCurrenciesTopic::getName(), ['product' => [1 => [], 3 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [ResolveCombinedPriceListCurrenciesTopic::getName(), ['product' => [1 => [], 2 => [], 3 => []]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testRemoveDuplicatedPriceListProductsMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [1 => [11]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [1 => [11, 12], 2 => [11]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [2 => [11, 12]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [2 => [12]]]);
        $buffer->addMessage(
            ResolveCombinedPriceByPriceListTopic::getName(),
            ['product' => [1 => [12, 13], 3 => [11, 12]]]
        );

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [
                    ResolveCombinedPriceByPriceListTopic::getName(),
                    ['product' => [1 => [11, 12, 13], 2 => [11, 12], 3 => [11, 12]]]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testRemoveRedundantPriceListProductsMessages()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [4 => []]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [1 => [11]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [1 => [11, 12], 2 => [11]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [2 => [11, 12]]]);
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [2 => [12]]]);
        $buffer->addMessage(
            ResolveCombinedPriceByPriceListTopic::getName(),
            ['product' => [1 => [12, 13], 3 => [11, 12], 4 => [11]]]
        );
        $buffer->addMessage(ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [1 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [4 => [], 1 => []]]],
                3 => [ResolveCombinedPriceByPriceListTopic::getName(), ['product' => [2 => [11, 12], 3 => [11, 12]]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testRemoveRedundantMessagesForResolvePriceRules()
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('another', ['key' => 'val']);
        $buffer->addMessage(ResolvePriceListAssignedProductsTopic::getName(), ['product' => [2 => [11, 12]]]);
        $buffer->addMessage(ResolvePriceListAssignedProductsTopic::getName(), ['product' => [4 => []]]);
        $buffer->addMessage(ResolvePriceRulesTopic::getName(), ['product' => [4 => []]]);
        $buffer->addMessage(ResolvePriceRulesTopic::getName(), ['product' => [1 => [11]]]);
        $buffer->addMessage(ResolvePriceRulesTopic::getName(), ['product' => [2 => [11, 12]]]);
        $buffer->addMessage(
            ResolvePriceRulesTopic::getName(),
            ['product' => [1 => [12, 13], 3 => [11, 12], 4 => [11]]]
        );
        $buffer->addMessage(ResolvePriceRulesTopic::getName(), ['product' => [1 => []]]);
        $buffer->addMessage(ResolvePriceRulesTopic::getName(), ['product' => [5 => []]]);
        $buffer->addMessage(ResolvePriceListAssignedProductsTopic::getName(), ['product' => [1 => []]]);

        $this->filter->apply($buffer);
        $this->assertSame(
            [
                0 => ['another', ['key' => 'val']],
                1 => [ResolvePriceListAssignedProductsTopic::getName(), ['product' => [2 => [11, 12]]]],
                2 => [ResolvePriceListAssignedProductsTopic::getName(), ['product' => [4 => [], 1 => []]]],
                5 => [ResolvePriceRulesTopic::getName(), ['product' => [2 => [11, 12], 3 => [11, 12]]]],
                8 => [ResolvePriceRulesTopic::getName(), ['product' => [5 => []]]]
            ],
            $buffer->getMessages()
        );
    }
}
