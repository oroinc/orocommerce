<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\Step;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Akeneo\Bundle\BatchBundle\Job\JobRepositoryInterface;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Model\Step\SendPriceRecalculationMessageStep;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SendPriceRecalculationMessageStepTest extends \PHPUnit\Framework\TestCase
{
    public function testExecute()
    {
        $name = 'test';
        $step = new SendPriceRecalculationMessageStep($name);

        $context = new ExecutionContext();
        $context->put('price_list_id', 1, null, null);
        $jobExecution = new JobExecution();
        $jobExecution->setExecutionContext($context);
        $stepExecution = new StepExecution('import', $jobExecution);

        $priceList = static::createMock(PriceList::class);
        $priceList->method('getId')->willReturn(1);

        $step->setEventDispatcher($this->createEventDispatcher());
        $step->setJobRepository($this->createJobRepository());
        $step->setLexemeTriggerHandler($this->createLexemeTriggerHandlerMock());
        $step->setRegistry($this->createRegistryMock($priceList));
        $step->setPriceListTriggerHandler($this->createPriceListTriggerHandler($priceList));
        static::assertEquals('test', $step->getName());
        $step->execute($stepExecution);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|PriceRuleLexemeTriggerHandler
     */
    private function createLexemeTriggerHandlerMock()
    {
        $handler = static::createMock(PriceRuleLexemeTriggerHandler::class);
        $handler->expects($this->once())->method('findEntityLexemes')->willReturn([]);
        $handler->expects($this->once())->method('addTriggersByLexemes');

        return $handler;
    }

    /**
     * @param $priceList
     * @return \PHPUnit\Framework\MockObject\MockObject|RegistryInterface
     */
    private function createRegistryMock($priceList)
    {
        $manager = static::createMock(EntityManager::class);
        $manager->expects($this->once())->method('find')
            ->with(PriceList::class, 1)
            ->willReturn($priceList);
        $registry = static::createMock(RegistryInterface::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        return $registry;
    }

    /**
     * @param $priceList
     * @return \PHPUnit\Framework\MockObject\MockObject|PriceListTriggerHandler
     */
    private function createPriceListTriggerHandler($priceList)
    {
        $handler = static::createMock(PriceListTriggerHandler::class);
        $handler->expects($this->once())
            ->method('addTriggerForPriceList')
            ->with(Topics::RESOLVE_COMBINED_PRICES, $priceList);
        $handler->expects($this->once())->method('sendScheduledTriggers');

        return $handler;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    private function createEventDispatcher()
    {
        $dispatcher = static::createMock(EventDispatcherInterface::class);
        return $dispatcher;
    }

    /**
     * @return JobRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createJobRepository()
    {
        return static::createMock(JobRepositoryInterface::class);
    }
}
