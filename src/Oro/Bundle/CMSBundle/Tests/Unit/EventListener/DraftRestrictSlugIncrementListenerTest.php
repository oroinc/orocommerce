<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Event\RestrictSlugIncrementEvent;
use Oro\Bundle\RedirectBundle\EventListener\DraftRestrictSlugIncrementListener;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

class DraftRestrictSlugIncrementListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilder;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var DraftRestrictSlugIncrementListener */
    private $listener;

    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->queryBuilder = new QueryBuilder($this->entityManager);

        $this->listener = new DraftRestrictSlugIncrementListener(
            $this->entityManager
        );
    }

    public function testOnRestrictSlugIncrementEventNotDraftableEntity(): void
    {
        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        $event = new RestrictSlugIncrementEvent($this->queryBuilder, $entity);

        $this->listener->onRestrictSlugIncrementEvent($event);
        $this->assertEmpty($this->queryBuilder->getDQLPart('where'));
    }

    public function testOnRestrictSlugIncrementEventWithDraft(): void
    {
        $entity = new Page();
        $entity->setDraftUuid(UUIDGenerator::v4());
        $event = new RestrictSlugIncrementEvent($this->queryBuilder, $entity);

        $this->listener->onRestrictSlugIncrementEvent($event);

        $this->assertEquals(new Expr\Andx(['1 = 0']), $this->queryBuilder->getDQLPart('where'));
    }

    public function testOnRestrictSlugIncrementEventWithNonDraft(): void
    {
        $entity = new Page();
        $event = new RestrictSlugIncrementEvent($this->queryBuilder, $entity);

        $mapping = [
            'fieldName' => 'slugs'
        ];
        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with('slugs')
            ->willReturn($mapping);
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(Page::class)
            ->willReturn($metadata);
        $this->entityManager
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());

        $subQueryBuilder = new QueryBuilder($this->entityManager);
        $subQueryBuilder->select('entity')
            ->from(Page::class, 'entity');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('entity')
            ->willReturn($subQueryBuilder);
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Page::class)
            ->willReturn($repository);

        $this->listener->onRestrictSlugIncrementEvent($event);

        $expectedSubQueryDQL = sprintf(
            'SELECT entitySlug.id FROM %s entity INNER JOIN entity.slugs entitySlug WHERE entity.draftUuid IS NULL',
            Page::class
        );
        $this->assertEquals(
            new Expr\Andx([new Expr\Func('slug IN', [$expectedSubQueryDQL])]),
            $this->queryBuilder->getDQLPart('where')
        );
    }
}
