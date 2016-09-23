<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractSearchWebTestCase extends WebTestCase
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->addFrontendRequest();
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');
    }

    protected function tearDown()
    {
        $this->clearIndexTextTable();
        unset($this->requestStack);
    }

    /**
     * Workaround to clear MyISAM table as it's not rolled back by transaction.
     */
    protected function clearIndexTextTable()
    {
        /** @var OroEntityManager $manager */
        $manager = $this->getDoctrine()->getManager('search');
        $repository = $manager->getRepository(IndexText::class);
        $repository->createQueryBuilder('t')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @return Registry
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    protected function addFrontendRequest()
    {
        $this->requestStack = $this->getContainer()->get('request_stack');
        $this->requestStack->push(Request::create(''));
    }

    /**
     * @return int
     */
    protected function getDefaultLocalizationId()
    {
        $localizationManager = $this->getContainer()->get('oro_locale.manager.localization');
        return $localizationManager->getDefaultLocalization()->getId();
    }

    /**
     * @param string $eventName
     */
    protected function clearRestrictListeners($eventName)
    {
        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            $this->dispatcher->removeListener($eventName, $listener);
        }
    }

    /**
     * @param int $count
     * @param string $entityClass
     */
    protected function assertEntityCount($count, $entityClass)
    {
        $repository = $this->getRepository($entityClass);

        $actualCount = $repository->createQueryBuilder('t')
            ->select('count(t)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals($count, $actualCount);
    }

    /**
     * @param string $entity
     * @return EntityRepository
     */
    private function getRepository($entity)
    {
        return $this->getDoctrine()->getRepository($entity, 'search');
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    protected function getItemRepository()
    {
        return $this->getDoctrine()->getRepository(Item::class, 'search');
    }

    /**
     * @return int
     */
    protected function getDefaultWebsiteId()
    {
        return $this
            ->getDoctrine()
            ->getRepository(Website::class)
            ->getDefaultWebsite()
            ->getId();
    }
}
