<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

abstract class PriceListRelationTriggerHandlerProcessorTestCase extends TestCase
{
    /**
     * @var PriceListRelationTriggerHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $relationChangesHandler;

    protected function setUp()
    {
        $this->relationChangesHandler = $this->createMock(PriceListRelationTriggerHandler::class);
    }

    /**
     * @param mixed $result
     *
     * @return ContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createContext($result)
    {
        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getResult')
            ->willReturn($result);

        return $context;
    }

    /**
     * @param int $id
     *
     * @return Website|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createWebsite(int $id)
    {
        $website = $this->createMock(Website::class);
        $website->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $website;
    }
}
