<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceList\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceList\Processor\UpdatePriceListLexemes;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;

class UpdatePriceListLexemesTest extends FormProcessorTestCase
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceRuleLexemeHandler;

    /**
     * @var UpdatePriceListLexemes
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->priceRuleLexemeHandler = $this->createMock(PriceRuleLexemeHandler::class);

        $this->processor = new UpdatePriceListLexemes($this->priceRuleLexemeHandler);
    }

    public function testProcessWrongType()
    {
        $this->priceRuleLexemeHandler
            ->expects(static::never())
            ->method('updateLexemes');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $priceList = new PriceList();

        $this->priceRuleLexemeHandler
            ->expects(static::once())
            ->method('updateLexemes')
            ->with($priceList);

        $this->context->setResult($priceList);
        $this->processor->process($this->context);
    }
}
