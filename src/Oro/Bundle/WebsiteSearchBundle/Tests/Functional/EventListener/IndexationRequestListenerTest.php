<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormInterface;

class IndexationRequestListenerTest extends WebTestCase
{
    use UserUtilityTrait;

    protected function setUp(): void
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

        $eventDispatcher->addListener(ReindexationRequestEvent::EVENT_NAME, function ($event) use (&$triggeredEvent) {
            $triggeredEvent = $event;
        });

        $product = $this->createProduct();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
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

        $eventDispatcher->addListener(ReindexationRequestEvent::EVENT_NAME, function ($event) use (&$triggeredEvent) {
            $triggeredEvent = $event;
        });

        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $product->setName($product->getName() . '-changed');

        $em->persist($product);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
    }

    public function testTriggersReindexationAfterProductUpdateButNoDoublesEntities()
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

        $eventDispatcher->addListener(ReindexationRequestEvent::EVENT_NAME, function ($event) use (&$triggeredEvent) {
            $triggeredEvent = $event;
        });

        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $product->setName($product->getName() . '-changed');

        // trigger beforeEntityFlush with same entity to ensure that entity will be indexed only once
        $eventDispatcher->dispatch(new AfterFormProcessEvent(
            $this->getMockBuilder(FormInterface::class)->getMock(),
            $product
        ), Events::BEFORE_FLUSH);
        $em->persist($product);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
        $this->assertCount(1, $triggeredEvent->getIds());
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

        $eventDispatcher->addListener(ReindexationRequestEvent::EVENT_NAME, function ($event) use (&$triggeredEvent) {
            $triggeredEvent = $event;
        });

        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $productId = $product->getId(); // retrieve now, cause after removing, it will be NULL

        $em->remove($product);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
        $this->assertContains($productId, $triggeredEvent->getIds());
    }

    public function testTriggersReindexationAfterOnClear()
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->client->getContainer()->get('event_dispatcher');

        $product = $this->createProduct(false);

        /**
         * @var ReindexationRequestEvent $triggeredEvent
         */
        $triggeredEvent = null;

        $eventDispatcher->addListener(ReindexationRequestEvent::EVENT_NAME, function ($event) use (&$triggeredEvent) {
            $triggeredEvent = $event;
        });

        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $em->clear(Product::class);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
    }

    /**
     * Helper method for creating a product which will be used for testing
     *
     * @param bool $flush
     * @return TestProduct
     */
    private function createProduct(bool $flush = true)
    {
        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $product = new TestProduct();
        $product->setName('test');

        $em->persist($product);

        if ($flush) {
            $em->flush();
        }

        return $product;
    }
}
