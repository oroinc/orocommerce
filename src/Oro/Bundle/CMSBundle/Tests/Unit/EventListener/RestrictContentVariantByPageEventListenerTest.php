<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\EventListener\RestrictContentVariantByPageEventListener;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntitiesEvent;
use PHPUnit\Framework\TestCase;

class RestrictContentVariantByPageEventListenerTest extends TestCase
{
    private RestrictContentVariantByPageEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new RestrictContentVariantByPageEventListener();
    }

    public function testApplyRestrictionForEntities(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());

        $queryBuilder = (new QueryBuilder($entityManager))
            ->select('variant.id AS variantId')
            ->from(ContentVariant::class, 'variant');
        $event = new RestrictContentVariantByEntitiesEvent(
            $queryBuilder,
            Page::class,
            [1, 2],
            'variant',
            'ownerId'
        );

        $this->listener->applyRestrictionForEntities($event);

        self::assertTrue($event->isRestricted());
        self::assertSame(
            'SELECT variant.id AS variantId, IDENTITY(variant.cms_page) AS ownerId'
            . ' FROM Oro\Bundle\WebCatalogBundle\Entity\ContentVariant variant'
            . ' WHERE variant.cms_page IN(:entityIds)',
            $queryBuilder->getDQL()
        );
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('entityIds', $parameters[0]->getName());
        self::assertSame([1, 2], $parameters[0]->getValue());
        self::assertSame(ArrayParameterType::INTEGER, $parameters[0]->getType());
    }

    public function testApplyRestrictionForEntitiesForAnotherEntityClass(): void
    {
        $queryBuilder = (new QueryBuilder($this->createMock(EntityManagerInterface::class)))
            ->select('variant.id AS variantId')
            ->from(ContentVariant::class, 'variant');
        $event = new RestrictContentVariantByEntitiesEvent(
            $queryBuilder,
            \stdClass::class,
            [1, 2],
            'variant',
            'ownerId'
        );

        $this->listener->applyRestrictionForEntities($event);

        self::assertFalse($event->isRestricted());
        self::assertSame(
            'SELECT variant.id AS variantId FROM Oro\Bundle\WebCatalogBundle\Entity\ContentVariant variant',
            $queryBuilder->getDQL()
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }
}
