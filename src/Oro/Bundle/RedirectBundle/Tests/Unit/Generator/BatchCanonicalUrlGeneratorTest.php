<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Generator\BatchCanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BatchCanonicalUrlGeneratorTest extends TestCase
{
    private const string ENTITY_CLASS = SluggableEntityStub::class;

    private CanonicalUrlGenerator&MockObject $canonicalUrlGenerator;
    private DoctrineHelper&MockObject $doctrineHelper;
    private BatchCanonicalUrlGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->generator = new BatchCanonicalUrlGenerator($this->canonicalUrlGenerator, $this->doctrineHelper);
    }

    /**
     * A stub of the query builder returned by the doctrine helper, it holds no expectation.
     *
     * @param array $rows [['url' => string, 'localizationId' => int|null, 'ownerId' => int], ...]
     */
    private function createSlugQueryBuilder(array $rows): QueryBuilder
    {
        $query = $this->createMock(Query::class);
        $query->method('getArrayResult')
            ->willReturn($rows);
        // the query builder configures the query with a fluent chain
        $query->method('setParameters')
            ->willReturnSelf();
        $query->method('setFirstResult')
            ->willReturnSelf();
        $query->method('setMaxResults')
            ->willReturnSelf();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQuery')
            ->willReturn($query);

        return (new QueryBuilder($entityManager))->from(self::ENTITY_CLASS, 'owner');
    }

    public function testGetUrlsWithoutIds(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method(self::anything());
        $this->canonicalUrlGenerator->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->generator->getUrls(self::ENTITY_CLASS, []));
    }

    public function testGetDirectUrlsWithoutIds(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method(self::anything());
        $this->canonicalUrlGenerator->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->generator->getDirectUrls(self::ENTITY_CLASS, []));
    }

    public function testGetUrlsWhenDirectUrlIsDisabled(): void
    {
        $reference1 = new SluggableEntityStub();
        $reference2 = new SluggableEntityStub();

        $this->canonicalUrlGenerator->expects(self::once())
            ->method('isDirectUrlEnabled')
            ->with(null)
            ->willReturn(false);
        $this->doctrineHelper->expects(self::never())
            ->method('createQueryBuilder');
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityReference')
            ->withConsecutive([self::ENTITY_CLASS, 1], [self::ENTITY_CLASS, 2])
            ->willReturnOnConsecutiveCalls($reference1, $reference2);
        $this->canonicalUrlGenerator->expects(self::exactly(2))
            ->method('getSystemUrl')
            ->withConsecutive([$reference1, null], [$reference2, null])
            ->willReturnOnConsecutiveCalls('/system/1', '/system/2');

        self::assertSame(
            [1 => '/system/1', 2 => '/system/2'],
            $this->generator->getUrls(self::ENTITY_CLASS, [1, 2])
        );
    }

    public function testGetUrlsFallsBackToSystemUrlForEntitiesWithoutSlug(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 7);
        $localization = new Localization();
        ReflectionUtil::setId($localization, 42);
        $reference = new SluggableEntityStub();

        $slugQueryBuilder = $this->createSlugQueryBuilder([
                ['url' => '/slug/1', 'localizationId' => 42, 'ownerId' => 1],
                ['url' => '/slug/3', 'localizationId' => null, 'ownerId' => 3],
            ]);

        $this->canonicalUrlGenerator->expects(self::once())
            ->method('isDirectUrlEnabled')
            ->with($website)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::ENTITY_CLASS)
            ->willReturn('id');
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(self::ENTITY_CLASS, 'owner')
            ->willReturn($slugQueryBuilder);
        $this->canonicalUrlGenerator->expects(self::exactly(2))
            ->method('getAbsoluteUrl')
            ->withConsecutive(['/slug/1', $website], ['/slug/3', $website])
            ->willReturnOnConsecutiveCalls('http://example.com/slug/1', 'http://example.com/slug/3');
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(self::ENTITY_CLASS, 2)
            ->willReturn($reference);
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getSystemUrl')
            ->with($reference, $website)
            ->willReturn('/system/2');

        // the order of the returned URLs follows the order of the requested identifiers
        self::assertSame(
            [
                1 => 'http://example.com/slug/1',
                2 => '/system/2',
                3 => 'http://example.com/slug/3',
            ],
            $this->generator->getUrls(self::ENTITY_CLASS, [1, 2, 3], $localization, $website)
        );
    }

    public function testGetDirectUrlsPrefersLocalizedSlugAndSkipsEntitiesWithoutUsableSlug(): void
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, 42);

        $slugQueryBuilder = $this->createSlugQueryBuilder([
                // the localized slug of the requested localization wins over the base one
                ['url' => '/slug/1-base', 'localizationId' => null, 'ownerId' => 1],
                ['url' => '/slug/1-localized', 'localizationId' => 42, 'ownerId' => 1],
                // a slug of another localization is not usable
                ['url' => '/slug/2-other', 'localizationId' => 100, 'ownerId' => 2],
                ['url' => '/slug/3-base', 'localizationId' => null, 'ownerId' => 3],
            ]);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::ENTITY_CLASS)
            ->willReturn('id');
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(self::ENTITY_CLASS, 'owner')
            ->willReturn($slugQueryBuilder);
        $this->canonicalUrlGenerator->expects(self::exactly(2))
            ->method('getAbsoluteUrl')
            ->withConsecutive(['/slug/1-localized', null], ['/slug/3-base', null])
            ->willReturnOnConsecutiveCalls('http://example.com/slug/1-localized', 'http://example.com/slug/3-base');

        // the entity 4 has no slug at all, the entity 2 has no usable one, neither of them is returned
        self::assertSame(
            [
                1 => 'http://example.com/slug/1-localized',
                3 => 'http://example.com/slug/3-base',
            ],
            $this->generator->getDirectUrls(self::ENTITY_CLASS, [1, 2, 3, 4], $localization)
        );
    }

    public function testGetDirectUrlsResolvesLocalizationWhenItIsNotGiven(): void
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, 42);

        $queryBuilder = $this->createSlugQueryBuilder([['url' => '/slug/1', 'localizationId' => 42, 'ownerId' => 1]]);

        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getLocalization')
            ->willReturn($localization);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::ENTITY_CLASS)
            ->willReturn('id');
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(self::ENTITY_CLASS, 'owner')
            ->willReturn($queryBuilder);
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with('/slug/1', null)
            ->willReturn('http://example.com/slug/1');

        self::assertSame(
            [1 => 'http://example.com/slug/1'],
            $this->generator->getDirectUrls(self::ENTITY_CLASS, [1])
        );

        self::assertSame(
            'SELECT s.url, IDENTITY(s.localization) AS localizationId, owner.id AS ownerId'
            . ' FROM Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub owner'
            . ' INNER JOIN owner.slugs s'
            . ' WHERE owner IN (:ids) AND (s.localization = :localization OR s.localization IS NULL)'
            . ' ORDER BY s.id ASC',
            $queryBuilder->getDQL()
        );
        $parameters = $queryBuilder->getParameters();
        self::assertCount(2, $parameters);
        self::assertSame('ids', $parameters[0]->getName());
        self::assertSame([1], $parameters[0]->getValue());
        self::assertSame(ArrayParameterType::INTEGER, $parameters[0]->getType());
        self::assertSame('localization', $parameters[1]->getName());
        self::assertSame(42, $parameters[1]->getValue());
        self::assertSame(Types::INTEGER, $parameters[1]->getType());
    }

    public function testGetDirectUrlsUsesBaseSlugsOnlyWhenLocalizationIsNotResolved(): void
    {
        $queryBuilder = $this->createSlugQueryBuilder(
            [['url' => '/slug/1-base', 'localizationId' => null, 'ownerId' => 1]]
        );

        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getLocalization')
            ->willReturn(null);
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::ENTITY_CLASS)
            ->willReturn('id');
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(self::ENTITY_CLASS, 'owner')
            ->willReturn($queryBuilder);
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getAbsoluteUrl')
            ->with('/slug/1-base', null)
            ->willReturn('http://example.com/slug/1-base');

        self::assertSame(
            [1 => 'http://example.com/slug/1-base'],
            $this->generator->getDirectUrls(self::ENTITY_CLASS, [1])
        );

        self::assertSame(
            'SELECT s.url, IDENTITY(s.localization) AS localizationId, owner.id AS ownerId'
            . ' FROM Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub owner'
            . ' INNER JOIN owner.slugs s'
            . ' WHERE owner IN (:ids) AND s.localization IS NULL'
            . ' ORDER BY s.id ASC',
            $queryBuilder->getDQL()
        );
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('ids', $parameters[0]->getName());
    }

    public function testGetDirectUrlsDoesNotResolveLocalizationWhenItIsGiven(): void
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, 42);

        $slugQueryBuilder = $this->createSlugQueryBuilder(
            [['url' => '/slug/1', 'localizationId' => 42, 'ownerId' => 1]]
        );

        $this->canonicalUrlGenerator->expects(self::never())
            ->method('getLocalization');
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturn('id');
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($slugQueryBuilder);
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getAbsoluteUrl')
            ->willReturn('http://example.com/slug/1');

        self::assertSame(
            [1 => 'http://example.com/slug/1'],
            $this->generator->getDirectUrls(self::ENTITY_CLASS, [1], $localization)
        );
    }
}
