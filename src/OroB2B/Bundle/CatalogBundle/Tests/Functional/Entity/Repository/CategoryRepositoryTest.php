<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\CategoryTitle;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

/**
 * @dbIsolation
 */
class CategoryRepositoryTest extends WebTestCase
{
    /**
     * @var CategoryRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BCatalogBundle:Category');
    }

    public function testGetMasterCatalogRoot()
    {
        $root = $this->repository->getMasterCatalogRoot();
        $this->assertInstanceOf('OroB2B\Bundle\CatalogBundle\Entity\Category', $root);

        $titles = $root->getTitles()->filter(function (CategoryTitle $title) {
            return null === $title->getLocale();
        });
        $this->assertNotEmpty($titles->toArray());

        /** @var CategoryTitle $defaultTitle */
        $defaultTitle = $titles->first();
        $this->assertEquals('Master catalog', $defaultTitle->getValue());
    }
}
