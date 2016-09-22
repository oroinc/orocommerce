<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexationTriggerListener;

class ReindexationTriggerListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASSNAME  = 'testClass';
    const TEST_WEBSITE_ID = 1234;

    /**
     * @var ReindexationTriggerListener
     */
    protected $listener;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManagerMock;

    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $regularIndexerMock;

    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $asyncIndexerMock;

    public function setUp()
    {
        $this->entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regularIndexerMock = $this->getMockBuilder(IndexerInterface::class)->getMock();
        $this->asyncIndexerMock = $this->getMockBuilder(IndexerInterface::class)->getMock();

        $this->listener = new ReindexationTriggerListener(
            $this->entityManagerMock,
            $this->regularIndexerMock,
            $this->asyncIndexerMock
        );
    }

    public function testProcess()
    {
        $event = new ReindexationTriggerEvent(
            self::TEST_CLASSNAME,
            self::TEST_WEBSITE_ID,
            null,
            false
        );
    }
}
