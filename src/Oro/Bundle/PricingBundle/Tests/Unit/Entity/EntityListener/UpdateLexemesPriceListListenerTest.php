<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\EntityListener\UpdateLexemesPriceListListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use PHPUnit\Framework\TestCase;

class UpdateLexemesPriceListListenerTest extends TestCase
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceRuleLexemeHandler;

    /**
     * @var UpdateLexemesPriceListListener
     */
    private $listener;

    protected function setUp()
    {
        $this->priceRuleLexemeHandler = $this->createMock(PriceRuleLexemeHandler::class);

        $this->listener = new UpdateLexemesPriceListListener($this->priceRuleLexemeHandler);
    }

    public function testUpdateLexemes()
    {
        $this->priceRuleLexemeHandler->expects(static::once())
            ->method('updateLexemes');

        $this->listener->updateLexemes(new PriceList());
    }
}
