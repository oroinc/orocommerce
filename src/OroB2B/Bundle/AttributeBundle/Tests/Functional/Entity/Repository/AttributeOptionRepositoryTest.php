<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\AttributeBundle\Entity\Repository\AttributeOptionRepository;
use OroB2B\Bundle\AttributeBundle\Tests\Functional\DataFixtures\LoadSelectAttributeWithOptions;

/**
 * @dbIsolation
 */
class AttributeOptionRepositoryTest extends WebTestCase
{
    /**
     * @var AttributeOptionRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAttributeBundle:AttributeOption');
    }

    public function testCreateAttributeOptionsQueryBuilder()
    {
        $this->loadFixtures([
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadLocaleData',
            'OroB2B\Bundle\AttributeBundle\Tests\Functional\DataFixtures\LoadSelectAttributeWithOptions',
        ]);

        $attribute = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAttributeBundle:Attribute')
            ->findOneBy(['code' => LoadSelectAttributeWithOptions::ATTRIBUTE_CODE]);

        $queryBuilder = $this->repository->createAttributeOptionsQueryBuilder($attribute);
        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $queryBuilder);

        /** @var AttributeOption[] $options */
        $options = $queryBuilder->getQuery()->getResult();

        $actualOptions = [];
        foreach ($options as $option) {
            $this->assertInstanceOf('OroB2B\Bundle\AttributeBundle\Entity\AttributeOption', $option);
            $actualOptions[$option->getOrder()] = $option->getValue();
        }

        $expectedOptions = LoadSelectAttributeWithOptions::$options[null];
        ksort($expectedOptions);

        $this->assertEquals($expectedOptions, $actualOptions);
    }
}
