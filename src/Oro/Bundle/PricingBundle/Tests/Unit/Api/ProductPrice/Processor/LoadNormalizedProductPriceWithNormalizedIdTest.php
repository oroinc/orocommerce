<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\LoadNormalizedProductPriceWithNormalizedId;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class LoadNormalizedProductPriceWithNormalizedIdTest extends FormProcessorTestCase
{
    /** @var ActionProcessorBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processorBag;

    /** @var LoadNormalizedProductPriceWithNormalizedId */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);

        $this->processor = new LoadNormalizedProductPriceWithNormalizedId($this->processorBag);
    }

    public function testProcessCompiles()
    {
        $getContext = new GetContext($this->configProvider, $this->metadataProvider);

        $processor = $this->createMock(ActionProcessorInterface::class);
        $processor->expects(self::once())
            ->method('createContext')
            ->willReturn($getContext);

        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->willReturn($processor);

        $this->context->set('price_list_id', 12);
        $this->context->setId('test');
        $this->processor->process($this->context);
        self::assertEquals('test-12', $getContext->getId());
    }
}
