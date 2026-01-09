<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class WebsiteReindexationOnCreateDeleteListenerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testTriggersEventWhenWebsiteIsAdded()
    {
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        /** @var ReindexationRequestEvent|null $triggeredEvent */
        $triggeredEvent = null;

        $eventDispatcher->addListener(
            ReindexationRequestEvent::EVENT_NAME,
            function (ReindexationRequestEvent $event) use (&$triggeredEvent) {
                $triggeredEvent = $event;
            }
        );

        $website = $this->createWebsite();

        $this->assertNotNull($triggeredEvent);
        $this->assertContains($website->getId(), $triggeredEvent->getWebsitesIds());
    }

    public function testTriggersEventWhenWebsiteIsDeleted()
    {
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        /** @var ReindexationRequestEvent|null $triggeredEvent */
        $triggeredEvent = null;

        $website = $this->createWebsite();

        $eventDispatcher->addListener(
            ReindexationRequestEvent::EVENT_NAME,
            function (ReindexationRequestEvent $event) use (&$triggeredEvent) {
                $triggeredEvent = $event;
            }
        );

        $this->assertNull($triggeredEvent);

        $removedWebsiteId = $website->getId();
        $this->entityManager->remove($website);
        $this->entityManager->flush();

        $this->assertNotNull($triggeredEvent);
        $this->assertContains($removedWebsiteId, $triggeredEvent->getWebsitesIds());
    }

    public function testDoesNotTriggersEventWhenWebsiteIsUpdated()
    {
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        /** @var ReindexationRequestEvent|null $triggeredEvent */
        $triggeredEvent = null;

        $website = $this->createWebsite();

        $eventDispatcher->addListener(
            ReindexationRequestEvent::EVENT_NAME,
            function (ReindexationRequestEvent $event) use (&$triggeredEvent) {
                $triggeredEvent = $event;
            }
        );

        $website->setName('updated_' . $website->getName());

        $this->entityManager->persist($website);
        $this->entityManager->flush();

        $this->assertNull($triggeredEvent);
    }

    private function createWebsite()
    {
        $website = new Website();

        $website->setName('test_' . uniqid());

        $this->entityManager->persist($website);
        $this->entityManager->flush();

        return $website;
    }
}
