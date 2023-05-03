<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\EntityListener\PriceListEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PriceListRelationTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var RuleCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTriggerHandler;

    /** @var PriceRuleLexemeTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceRuleLexemeTriggerHandler;

    /** @var PriceListEntityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->triggerHandler = $this->createMock(PriceListRelationTriggerHandler::class);
        $this->cache = $this->createMock(RuleCache::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->priceRuleLexemeTriggerHandler = $this->createMock(PriceRuleLexemeTriggerHandler::class);

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
        $priceList->setActual(true);
        $priceList->setActive(true);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with('ar_42');
        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(ResolvePriceListAssignedProductsTopic::getName(), $priceList);

        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['productAssignmentRule'],
                ['active']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $dependentPriceList = $this->getEntity(PriceList::class, ['id' => 421]);
        $lexeme = new PriceRuleLexeme();
        $lexeme->setPriceList($dependentPriceList);
        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->willReturn([]);

        $this->listener->preUpdate($priceList, $event);
        $this->assertFalse($priceList->isActual());
    }

    public function testPreUpdateActiveChanged()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $priceList->setActual(true);
        $priceList->setActive(true);

        $this->cache->expects($this->never())
            ->method('delete');
        $this->priceRuleLexemeTriggerHandler->expects($this->never())
            ->method('findEntityLexemes');

        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(ResolvePriceListAssignedProductsTopic::getName(), $priceList);

        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['productAssignmentRule'],
                ['active']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $dependentPriceList = $this->getEntity(PriceList::class, ['id' => 421]);
        $lexeme = new PriceRuleLexeme();
        $lexeme->setPriceList($dependentPriceList);

        $this->listener->preUpdate($priceList, $event);
        $this->assertFalse($priceList->isActual());
    }

    public function testPreUpdateWithDependent()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $priceList->setActual(true);
        $priceList->setActive(true);

        $this->cache->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                ['ar_42'],
                ['ar_421'],
                ['pr_3']
            );
        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(ResolvePriceListAssignedProductsTopic::getName(), $priceList);

        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['productAssignmentRule'],
                ['active']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

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
            ->method('processLexemes')
            ->with($lexemes);

        $this->listener->preUpdate($priceList, $event);
        $this->assertFalse($priceList->isActual());
    }

    public function testPreUpdateNoChanges()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $priceList->setActual(true);
        $priceList->setActive(true);

        $this->cache->expects($this->never())
            ->method('delete');
        $this->priceListTriggerHandler->expects($this->never())
            ->method('handlePriceListTopic');

        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['productAssignmentRule'],
                ['active']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false
            );
        $this->listener->preUpdate($priceList, $event);
        $this->assertTrue($priceList->isActual());
    }

    public function testPostPersist()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $priceList->setActual(true);
        $priceList->setActive(true);

        $priceList->setProductAssignmentRule('product.id == 1');
        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic');

        $this->listener->postPersist($priceList);
        $this->assertFalse($priceList->isActual());
    }

    public function testPrePersistWithoutRule()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $this->priceListTriggerHandler->expects($this->never())
            ->method('handlePriceListTopic');
        $this->listener->postPersist($priceList);
        $this->assertTrue($priceList->isActual());
    }

    public function testPreRemove()
    {
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 42]);
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList->addPriceRule($priceRule);

        $cpl1 = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $cpl2 = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $cpls = new \ArrayIterator([$cpl1, $cpl2]);
        $repo = $this->createMock(CombinedPriceListRepository::class);
        $repo->expects($this->once())
            ->method('getCombinedPriceListsByPriceList')
            ->with($priceList)
            ->willReturn($cpls);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(CombinedPriceList::class)
            ->willReturn($repo);
        $em->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$cpl1],
                [$cpl2]
            );
        $args = $this->createMock(LifecycleEventArgs::class);
        $args->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($em);

        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->willReturn([]);

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                ['ar_2'],
                ['pr_42']
            );
        $this->triggerHandler->expects($this->once())
            ->method('handlePriceListStatusChange');
        $this->listener->preRemove($priceList, $args);
    }
}
