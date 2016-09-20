<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\EntityListener\PriceListEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceListRelationTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerHandler;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var PriceListTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListTriggerHandler;

    /**
     * @var PriceRuleLexemeTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRuleLexemeTriggerHandler;

    /**
     * @var PriceListEntityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->triggerHandler = $this->getMockBuilder(PriceListRelationTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this->getMock(Cache::class);
        $this->priceListTriggerHandler = $this->getMockBuilder(PriceListTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceRuleLexemeTriggerHandler = $this->getMockBuilder(PriceRuleLexemeTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PriceListEntityListener(
            $this->triggerHandler,
            $this->cache,
            $this->priceListTriggerHandler,
            $this->priceRuleLexemeTriggerHandler
        );
    }

    public function testPreUpdate()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('ar_42');
        $this->priceListTriggerHandler->expects($this->once())
            ->method('addTriggerForPriceList')
            ->with(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, $priceList);

        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with('productAssignmentRule')
            ->willReturn(true);

        $dependentPriceList = $this->getEntity(PriceList::class, ['id' => 421]);
        $lexeme = new PriceRuleLexeme();
        $lexeme->setPriceList($dependentPriceList);
        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->willReturn([]);
        
        $this->listener->preUpdate($priceList, $event);
        $this->assertFalse($priceList->isActual());
    }

    public function testPreUpdateWithDependent()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $this->cache->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                ['ar_42'],
                ['ar_421'],
                ['pr_3']
            );
        $this->priceListTriggerHandler->expects($this->once())
            ->method('addTriggerForPriceList')
            ->with(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, $priceList);

        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with('productAssignmentRule')
            ->willReturn(true);

        $dependentPriceList = $this->getEntity(PriceList::class, ['id' => 421]);
        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($dependentPriceList);

        $priceRule = $this->getEntity(PriceRule::class, ['id' => 3]);
        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($dependentPriceList);
        $lexeme2->setPriceRule($priceRule);
        $lexemes = [$lexeme1, $lexeme2];
        $this->priceRuleLexemeTriggerHandler->expects($this->exactly(2))
            ->method('findEntityLexemes')
            ->withConsecutive(
                [PriceList::class, [PriceListEntityListener::FIELD_PRODUCT_ASSIGNMENT_RULE], 42],
                [PriceList::class, [PriceListEntityListener::FIELD_PRODUCT_ASSIGNMENT_RULE], 421]
            )
            ->willReturnOnConsecutiveCalls(
                $lexemes,
                []
            );
        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('addTriggersByLexemes')
            ->with($lexemes);

        $this->listener->preUpdate($priceList, $event);
        $this->assertFalse($priceList->isActual());
    }

    public function testPreUpdateNoChanges()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $this->cache->expects($this->never())
            ->method('delete');
        $this->priceListTriggerHandler->expects($this->never())
            ->method('addTriggerForPriceList');

        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with('productAssignmentRule')
            ->willReturn(false);
        $this->listener->preUpdate($priceList, $event);
        $this->assertTrue($priceList->isActual());
    }

    public function testPrePersist()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $priceList->setProductAssignmentRule('product.id == 1');
        $this->priceListTriggerHandler->expects($this->once())
            ->method('addTriggerForPriceList');

        $this->listener->prePersist($priceList);
        $this->assertFalse($priceList->isActual());
    }

    public function testPrePersistWithoutRule()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $this->priceListTriggerHandler->expects($this->never())
            ->method('addTriggerForPriceList');
        $this->listener->prePersist($priceList);
        $this->assertTrue($priceList->isActual());
    }

    public function testPreRemove()
    {
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 42]);
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList->addPriceRule($priceRule);
        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                ['ar_2'],
                ['pr_42']
            );
        $this->triggerHandler->expects($this->once())
            ->method('handleFullRebuild');
        $this->listener->preRemove($priceList);
    }
}
