<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProductType;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;

/**
 * @dbIsolation
 */
class IndexationRequestListenerTest extends WebTestCase
{
    use UserUtilityTrait;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testTriggersReindexationAfterProductCreation()
    {
        /**
         * @var EventDispatcher $eventDispatcher
         */
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        /**
         * @var ReindexationRequestEvent $triggeredEvent
         */
        $triggeredEvent = null;

        $eventDispatcher->addListener(ReindexationRequestEvent::EVENT_NAME, function ($event) use (& $triggeredEvent) {
            $triggeredEvent = $event;
        });

        $product = $this->createProduct();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertEquals(TestProduct::class, $triggeredEvent->getClassName());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
    }

    public function testTriggersReindexationAfterProductUpdate()
    {
        /**
         * @var EventDispatcher $eventDispatcher
         */
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $product = $this->createProduct();

        /**
         * @var ReindexationRequestEvent $triggeredEvent
         */
        $triggeredEvent = null;

        $eventDispatcher->addListener(ReindexationRequestEvent::EVENT_NAME, function ($event) use (& $triggeredEvent) {
            $triggeredEvent = $event;
        });

        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $product->setName($product->getName().'-changed');

        $em->persist($product);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertEquals(TestProduct::class, $triggeredEvent->getClassName());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
    }

    public function testDoesntTriggerReindexationAfterProductUpdatedWithNonIndexableField()
    {
        /**
         * @var EventDispatcher $eventDispatcher
         */
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $product = $this->createProduct();

        /**
         * @var ReindexationRequestEvent $triggeredEvent
         */
        $triggeredEvent = null;

        $eventDispatcher->addListener(ReindexationRequestEvent::EVENT_NAME, function ($event) use (& $triggeredEvent) {
            $triggeredEvent = $event;
        });

        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $productType = new TestProductType();
        $productType->setName('test123');
        $em->persist($productType);

        $product->setProductType($productType);

        $em->persist($product);
        $em->flush();

        $this->assertNull($triggeredEvent);
    }

    public function testTriggersReindexationAfterProductDelete()
    {
        /**
         * @var EventDispatcher $eventDispatcher
         */
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $product = $this->createProduct();

        /**
         * @var ReindexationRequestEvent $triggeredEvent
         */
        $triggeredEvent = null;

        $eventDispatcher->addListener(ReindexationRequestEvent::EVENT_NAME, function ($event) use (& $triggeredEvent) {
            $triggeredEvent = $event;
        });

        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $em->remove($product);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertEquals(TestProduct::class, $triggeredEvent->getClassName());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
    }

    /**
     * Helper method for creating a product which will be used for testing
     *
     * @return TestProduct
     */
    private function createProduct()
    {
        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $product = new TestProduct();
        $product->setName('test');

        $em->persist($product);
        $em->flush();

        return $product;
    }
}
