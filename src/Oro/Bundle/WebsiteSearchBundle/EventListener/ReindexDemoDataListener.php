<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

/**
 * This listener listens for oro:migration:data:load and check --fixtures-type=demo.
 * If these conditions are satisfied then listener triggers full reindexation of website index.
 */
class ReindexDemoDataListener
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function afterExecute(ConsoleTerminateEvent $event)
    {
        $commandName = $event->getCommand()->getName();

        if ($commandName !== LoadDataFixturesCommand::COMMAND_NAME || $event->getExitCode() !== 0) {
            return;
        }

        if ($event->getInput()->getOption('fixtures-type') === LoadDataFixturesCommand::DEMO_FIXTURES_TYPE) {

            $event->getOutput()->writeln('Running full reindexation...');
            $this->dispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, new ReindexationRequestEvent(
                [],
                [],
                [],
                false
            ));
        }
    }
}
