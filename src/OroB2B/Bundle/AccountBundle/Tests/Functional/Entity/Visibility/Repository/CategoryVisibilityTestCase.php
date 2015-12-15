<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class CategoryVisibilityTestCase extends WebTestCase
{
    const ROOT_CATEGORY = 'Master Catalog';

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @return string
     */
    abstract protected function getRepositoryName();

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository($this->getRepositoryName());

        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData']);
    }

    /**
     * @param array $expectedData
     * @param array $actualData
     * @param array $fields
     */
    protected function assertVisibilities(array $expectedData, array $actualData, array $fields = [])
    {
        $expectedData = $this->prepareRawExpectedData($expectedData);
        $this->assertCount(count($expectedData), $actualData);
        foreach ($actualData as $i => $actual) {
            $this->assertArrayHasKey($i, $expectedData);
            $expected = $expectedData[$i];
            $this->assertEquals($expected['category_id'], $actual['category_id']);
            $this->assertEquals($expected['category_parent_id'], $actual['category_parent_id']);
            $this->assertEquals($expected['visibility'], $actual['visibility']);
            foreach ($fields as $field) {
                $this->assertEquals($expected[$field], $actual[$field]);
            }
        }
    }

    /**
     * @param array $expectedData
     * @return array
     */
    protected function prepareRawExpectedData(array $expectedData)
    {
        foreach ($expectedData as &$item) {
            $item['category_id'] = $this->getCategoryId($item['category']);
            unset($item['category']);
            $item['category_parent_id'] = $this->getCategoryId($item['category_parent']);
            unset($item['category_parent']);
        }

        return $expectedData;
    }

    /**
     * @param string $reference
     * @return integer
     */
    protected function getCategoryId($reference)
    {
        if ($reference === self::ROOT_CATEGORY) {
            return $this->getContainer()->get('doctrine')->getRepository('OroB2BCatalogBundle:Category')
                ->getMasterCatalogRoot()->getId();
        }

        return $reference ? $this->getReference($reference)->getId() : null;
    }
}
