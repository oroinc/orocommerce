<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class IndexationRequestListenerTest extends WebTestCase
{
    use UserUtilityTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return self::getContainer()->get('event_dispatcher');
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManager();
    }

    public function testTriggersReindexationAfterProductCreation()
    {
        /** @var ReindexationRequestEvent $triggeredEvent */
        $triggeredEvent = null;

        $this->getEventDispatcher()->addListener(
            ReindexationRequestEvent::EVENT_NAME,
            function ($event) use (&$triggeredEvent) {
                $triggeredEvent = $event;
            }
        );

        $product = $this->createProduct();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
    }

    public function testTriggersReindexationAfterProductUpdate()
    {
        $product = $this->createProduct();

        /** @var ReindexationRequestEvent $triggeredEvent */
        $triggeredEvent = null;

        $this->getEventDispatcher()->addListener(
            ReindexationRequestEvent::EVENT_NAME,
            function ($event) use (&$triggeredEvent) {
                $triggeredEvent = $event;
            }
        );

        $product->setName($product->getName() . '-changed');

        $em = $this->getEntityManager();
        $em->persist($product);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
    }

    public function testTriggersReindexationAfterProductUpdateButNoDoublesEntities()
    {
        $product = $this->createProduct();

        /** @var ReindexationRequestEvent $triggeredEvent */
        $triggeredEvent = null;

        $this->getEventDispatcher()->addListener(
            ReindexationRequestEvent::EVENT_NAME,
            function ($event) use (&$triggeredEvent) {
                $triggeredEvent = $event;
            }
        );

        $product->setName($product->getName() . '-changed');

        // trigger beforeEntityFlush with same entity to ensure that entity will be indexed only once
        $this->getEventDispatcher()->dispatch(
            new AfterFormProcessEvent($this->createMock(FormInterface::class), $product),
            Events::BEFORE_FLUSH
        );

        $em = $this->getEntityManager();
        $em->persist($product);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
        $this->assertCount(1, $triggeredEvent->getIds());
    }

    public function testTriggersReindexationAfterProductDelete()
    {
        $product = $this->createProduct();

        /** @var ReindexationRequestEvent $triggeredEvent */
        $triggeredEvent = null;

        $this->getEventDispatcher()->addListener(
            ReindexationRequestEvent::EVENT_NAME,
            function ($event) use (&$triggeredEvent) {
                $triggeredEvent = $event;
            }
        );

        $productId = $product->getId(); // retrieve now, cause after removing, it will be NULL

        $em = $this->getEntityManager();
        $em->remove($product);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
        $this->assertContains($productId, $triggeredEvent->getIds());
    }

    public function testTriggersReindexationAfterOnClear()
    {
        $product = $this->createProduct(false);

        /** @var ReindexationRequestEvent $triggeredEvent */
        $triggeredEvent = null;

        $this->getEventDispatcher()->addListener(
            ReindexationRequestEvent::EVENT_NAME,
            function ($event) use (&$triggeredEvent) {
                $triggeredEvent = $event;
            }
        );

        $em = $this->getEntityManager();
        $em->clear(Product::class);
        $em->flush();

        $this->assertNotNull($triggeredEvent, 'Event was not triggered.');
        $this->assertContains(TestProduct::class, $triggeredEvent->getClassesNames());
        $this->assertContains($product->getId(), $triggeredEvent->getIds());
    }

    /**
     * Helper method for creating a product which will be used for testing
     */
    private function createProduct(bool $flush = true): TestProduct
    {
        $product = new TestProduct();
        $product->setName('test');

        $em = $this->getEntityManager();
        $em->persist($product);
        if ($flush) {
            $em->flush();
        }

        return $product;
    }
}
