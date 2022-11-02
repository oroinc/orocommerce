<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\EventListener\PriceListListener;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Test\FormInterface;

class PriceListListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CombinedPriceListActivationPlanBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var PriceListRelationTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var PriceRuleLexemeHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceRuleLexemeHandler;

    /** @var PriceList */
    private $priceList;

    /** @var PriceListListener */
    private $listener;

    protected function setUp(): void
    {
        $this->builder = $this->createMock(CombinedPriceListActivationPlanBuilder::class);
        $this->triggerHandler = $this->createMock(PriceListRelationTriggerHandler::class);
        $this->priceRuleLexemeHandler = $this->createMock(PriceRuleLexemeHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new PriceListListener(
            $this->builder,
            $this->triggerHandler,
            $this->priceRuleLexemeHandler
        );
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_price_lists_combined');

        $this->priceList = $this->createPriceList();
    }

    public function testPostSubmitWithoutCurrencyChange()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
        $this->listener->beforeSubmit($this->createFormProcessEvent($this->priceList));

        $form = $this->createMock(FormInterface::class);

        $event = new AfterFormProcessEvent($form, $this->priceList);

        $this->priceRuleLexemeHandler->expects($this->once())
            ->method('updateLexemes')
            ->with($this->priceList);

        $this->listener->onPostSubmit($event);
    }

    public function testWithoutChanges()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
        $this->listener->beforeSubmit($this->createFormProcessEvent($this->priceList));

        $this->builder->expects($this->never())
            ->method('buildByPriceList');

        $this->triggerHandler->expects($this->never())
            ->method('handlePriceListStatusChange');

        $this->listener->afterFlush($this->createAfterFormProcessEvent($this->priceList));
    }

    public function testAfterFlushCplFeatureDisabled()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);
        $this->listener->beforeSubmit($this->createFormProcessEvent($this->priceList));

        $this->priceList->setActive(false);
        $this->builder->expects($this->never())
            ->method('buildByPriceList');

        $this->triggerHandler->expects($this->once())
            ->method('handlePriceListStatusChange');

        $this->listener->afterFlush($this->createAfterFormProcessEvent($this->priceList));
    }

    public function testStatusChanged()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
        $this->listener->beforeSubmit($this->createFormProcessEvent($this->priceList));

        $this->priceList->setActive(false);
        $this->triggerHandler->expects($this->once())
            ->method('handlePriceListStatusChange');

        $this->listener->afterFlush($this->createAfterFormProcessEvent($this->priceList));
    }

    public function testWhenScheduleCollectionWasChanged()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
        $this->listener->beforeSubmit($this->createFormProcessEvent($this->priceList));

        /** @var PriceListSchedule $schedule */
        $schedule = $this->priceList->getSchedules()->first();
        $schedule->setActiveAt(new \DateTime('2016-01-01T22:00:00Z'));

        $this->builder->expects($this->once())
            ->method('buildByPriceList')
            ->with($this->priceList);

        $this->listener->afterFlush($this->createAfterFormProcessEvent($this->priceList));
    }

    public function testWhenScheduleCollectionElementDeleted()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
        $this->listener->beforeSubmit($this->createFormProcessEvent($this->priceList));

        $this->priceList->getSchedules()->remove(1);

        $this->builder->expects($this->once())
            ->method('buildByPriceList')
            ->with($this->priceList);

        $this->listener->afterFlush($this->createAfterFormProcessEvent($this->priceList));
    }

    private function createPriceList(): PriceList
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $schedule1 = new PriceListSchedule(
            new \DateTime('2016-03-01T22:00:00Z'),
            new \DateTime('2016-04-01T22:00:00Z')
        );
        $schedule2 = new PriceListSchedule(
            new \DateTime('2016-05-01T22:00:00Z'),
            new \DateTime('2016-06-01T22:00:00Z')
        );

        $priceList->setCurrencies(['USD']);

        $priceList->addSchedule($schedule1)
            ->addSchedule($schedule2);

        return $priceList;
    }

    private function createFormProcessEvent(PriceList $priceList): FormProcessEvent
    {
        return new FormProcessEvent(
            $this->createMock(\Symfony\Component\Form\FormInterface::class),
            $priceList
        );
    }

    private function createAfterFormProcessEvent(PriceList $priceList): AfterFormProcessEvent
    {
        return new AfterFormProcessEvent(
            $this->createMock(\Symfony\Component\Form\FormInterface::class),
            $priceList
        );
    }
}
