<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListLexemesProcessor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;

class UpdatePriceListLexemesProcessorTest extends FormProcessorTestCase
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceRuleLexemeHandler;

    /**
     * @var UpdatePriceListLexemesProcessor
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->priceRuleLexemeHandler = $this->createMock(PriceRuleLexemeHandler::class);

        $this->processor = new UpdatePriceListLexemesProcessor($this->priceRuleLexemeHandler);
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
