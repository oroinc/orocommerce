<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;

class WebsiteCrudReindexationTriggeringListenerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testTriggersEventWhenWebsiteIsAdded()
    {
        /**
         * @var EventDispatcher $eventDispatcher
         */
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        /**
         * @var ReindexationTriggerEvent $triggeredEvent
         */
        $triggeredEvent = null;

        $eventDispatcher->addListener(ReindexationTriggerEvent::EVENT_NAME, function (ReindexationTriggerEvent $event) use (& $triggeredEvent) {
            $triggeredEvent = $event;
        });

        $website = $this->createWebsite();

        $this->assertNotNull($triggeredEvent);
        $this->assertEquals($website->getId(), $triggeredEvent->getWebsiteId());
    }

    public function testTriggersEventWhenWebsiteIsDeleted()
    {
        /**
         * @var EventDispatcher $eventDispatcher
         */
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        /**
         * @var ReindexationTriggerEvent $triggeredEvent
         */
        $triggeredEvent = null;

        $website = $this->createWebsite();

        $eventDispatcher->addListener(ReindexationTriggerEvent::EVENT_NAME, function (ReindexationTriggerEvent $event) use (& $triggeredEvent) {
            $triggeredEvent = $event;
        });

        $this->assertNull($triggeredEvent);

        $this->entityManager->remove($website);

        $this->assertNotNull($triggeredEvent);
        $this->assertEquals($website->getId(), $triggeredEvent->getWebsiteId());
    }

    private function createWebsite()
    {
        $website = new Website();

        $website->setName('test_'.uniqid());

        $this->entityManager->persist($website);
        $this->entityManager->flush();

        return $website;
    }
}
