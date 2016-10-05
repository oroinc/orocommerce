<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

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
        $this->assertEquals(Product::class, $triggeredEvent->getClassName());
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

        $product->setSku('4050505');
        $em->persist($product);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertEquals(Product::class, $triggeredEvent->getClassName());
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

        $product->setHasVariants(true);
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
        $this->assertEquals(Product::class, $triggeredEvent->getClassName());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
    }

    /**
     * Helper method for createing a product which will be used for testing
     *
     * @return Product
     */
    private function createProduct()
    {
        static $sku = 0;

        /**
         * @var EntityManager $em
         */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $user = $this->getFirstUser($em);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $product = new Product();
        $product
            ->setSku(++$sku)
            ->setOwner($businessUnit)
            ->setOrganization($organization)
            ->setStatus('test');

        $em->persist($product);
        $em->flush();

        return $product;
    }
}
