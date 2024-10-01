<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\EntityIdentifierRepository;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadEmployeesToIndex;

class EntityIdentifierRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmployeesToIndex::class]);
    }

    public function testGetIds()
    {
        $doctrineHelper = static::getContainer()->get('oro_entity.doctrine_helper');

        $repository = new EntityIdentifierRepository($doctrineHelper);

        $actual = $repository->getIds(TestEmployee::class);

        self::assertInstanceOf(BufferedQueryResultIteratorInterface::class, $actual);

        $expected = [
            $this->getReference(LoadEmployeesToIndex::REFERENCE_PERSON1)->getId(),
            $this->getReference(LoadEmployeesToIndex::REFERENCE_PERSON2)->getId(),
        ];
        self::assertEquals($expected, iterator_to_array($actual));
    }
}
