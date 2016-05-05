<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository;

/**
 * @dbIsolation
 */
class LocaleRepositoryTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LocaleRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadLocaleData']);
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BWebsiteBundle:Locale');
        $this->repository = $this->em->getRepository('OroB2BWebsiteBundle:Locale');
    }

    public function testFindRootsWithChildren()
    {
        $locales = [$this->getCurrentLocale(), $this->getReference('en_US')];
        $queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());

        $prevLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();
        $this->em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        /** @var Locale[] $result */
        $result = $this->repository->findRootsWithChildren();

        $this->assertEquals($locales, $result);

        foreach ($result as $root) {
            $this->visitChildren($root);
        }
        
        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(1, $queries);

        $this->em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }

    /**
     * @param Locale $locale
     */
    protected function visitChildren(Locale $locale)
    {
        $locale->getCode();
        foreach ($locale->getChildLocales() as $child) {
            $this->visitChildren($child);
        }
    }
    
    /**
     * @return null|Locale
     */
    protected function getCurrentLocale()
    {
        $localeSettings = $this->getContainer()->get('oro_locale.settings');
        return $this->repository->findOneByCode($localeSettings->getLocale());
    }
}
