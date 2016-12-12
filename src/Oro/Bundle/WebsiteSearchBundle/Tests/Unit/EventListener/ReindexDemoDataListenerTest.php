<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexDemoDataListener;

class ReindexDemoDataListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject */
    private $dispatcher;

    /** @var ReindexDemoDataListener */
    private $listener;

    public function setUp()
    {
        $this->dispatcher = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $this->listener   = new ReindexDemoDataListener($this->dispatcher);
    }

    /**
     * @dataProvider dataProviderForNotRunCase
     * @param string $commandName
     * @param int    $exitCode
     * @param string $fixturesType
     */
    public function testWillNotRunWhenNotAllRequirementsSatisfied($commandName, $exitCode, $fixturesType = 'not-a-demo')
    {
        $this->dispatcherShouldNotBeCalled();

        $this->listener->afterExecute($this->getEvent($commandName, $exitCode, $fixturesType));
    }

    public function testDispatchReindexationEvent()
    {
        $this->dispatcher->expects($this->once())->method('dispatch');
        $this->listener->afterExecute($this->getEvent(
            LoadDataFixturesCommand::COMMAND_NAME,
            0,
            LoadDataFixturesCommand::DEMO_FIXTURES_TYPE
        ));
    }

    /**
     * @return array
     */
    public function dataProviderForNotRunCase()
    {
        return [
            'not supported command' => [
                'non-supported', 0
            ],
            'wrong exit code #1'    => [
                LoadDataFixturesCommand::COMMAND_NAME, 1
            ],
            'wrong exit code #2'    => [
                LoadDataFixturesCommand::COMMAND_NAME, -1
            ],
            'not a demo fixture'    => [
                LoadDataFixturesCommand::COMMAND_NAME, 0,
            ],
        ];
    }

    /**
     * @param string $commandName
     * @param int    $exitCode
     * @param string $fixturesType
     *
     * @return ConsoleTerminateEvent
     */
    private function getEvent($commandName, $exitCode, $fixturesType = 'not-a-demo')
    {
        /** @var Command|\PHPUnit_Framework_MockObject_MockObject $command */
        $command = $this->getMockBuilder(Command::class)->disableOriginalConstructor()->getMock();
        $input   = $this->getMockBuilder(InputInterface::class)->getMock();
        $output  = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->expects($this->any())->method('getName')->willReturn($commandName);
        $input->expects($this->any())->method('getOption')->with('fixtures-type')->willReturn($fixturesType);

        return new ConsoleTerminateEvent($command, $input, $output, $exitCode);
    }

    private function dispatcherShouldNotBeCalled()
    {
        $this->dispatcher->expects($this->never())->method('dispatch');
    }
}
