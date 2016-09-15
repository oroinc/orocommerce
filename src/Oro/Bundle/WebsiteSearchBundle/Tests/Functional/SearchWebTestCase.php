<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

abstract class SearchWebTestCase extends WebTestCase
{
    /**
     * Workaround to clear MyISAM table as it's not rolled back by transaction.
     */
    protected function clearIndexTextTable()
    {
        /** @var OroEntityManager $manager */
        $manager = $this->getDoctrine()->getManager('search');

        if ($manager->getConnection()->getDatabasePlatform()->getName() === DatabasePlatformInterface::DATABASE_MYSQL) {
            $repository = $manager->getRepository(IndexText::class);

            $repository->createQueryBuilder('t')
                ->delete()
                ->getQuery()
                ->execute();
        }
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
        $requestStack = $this->getContainer()->get('request_stack');
        $requestStack->push(Request::create(''));
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
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        foreach ($dispatcher->getListeners($eventName) as $listener) {
            $dispatcher->removeListener($eventName, $listener);
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
