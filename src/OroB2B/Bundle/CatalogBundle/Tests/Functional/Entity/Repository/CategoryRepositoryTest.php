<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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

        $defaultTitle = $root->getDefaultTitle();
        $this->assertEquals('Master catalog', $defaultTitle->getString());
    }
}
