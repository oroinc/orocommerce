<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\LoadNormalizedProductPriceWithNormalizedId;
use Oro\Bundle\PricingBundle\Api\ProductPrice\ProductPriceIDByContextNormalizerInterface;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class LoadNormalizedProductPriceWithNormalizedIdTest extends FormProcessorTestCase
{
    /**
     * @var ActionProcessorBagInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processorBag;

    /**
     * @var ProductPriceIDByContextNormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $normalizer;

    /**
     * @var LoadNormalizedProductPriceWithNormalizedId
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);
        $this->normalizer = $this->createMock(ProductPriceIDByContextNormalizerInterface::class);

        $this->processor = new LoadNormalizedProductPriceWithNormalizedId(
            $this->processorBag,
            $this->normalizer
        );
    }

    public function testProcessCompiles()
    {
        $this->context->setId(1);

        $getContext = new GetContext($this->configProvider, $this->metadataProvider);

        $processor = $this->createMock(ActionProcessorInterface::class);
        $processor
            ->expects(static::once())
            ->method('createContext')
            ->willReturn($getContext);

        $this->processorBag
            ->expects(static::once())
            ->method('getProcessor')
            ->willReturn($processor);

        $this->processor->process($this->context);
    }
}
