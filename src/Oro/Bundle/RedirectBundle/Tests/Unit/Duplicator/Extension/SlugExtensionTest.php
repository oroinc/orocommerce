<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Duplicator\Extension;

use DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\DraftBundle\Duplicator\DraftContext;
use Oro\Bundle\DraftBundle\Duplicator\Matcher\PropertiesNameMatcher;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\RedirectBundle\Duplicator\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\Testing\Unit\EntityTrait;

class SlugExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var SlugExtension */
    private $extension;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $classMetaData = $this->getEntity(
            ClassMetadataInfo::class,
            [
                'associationMappings' => [
                    'field1' => [
                        'targetEntity' => Slug::class
                    ]
                ]
            ],
            [
                $this->getEntity(DraftableEntityStub::class)
            ]
        );
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetaData);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry
            ->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        $this->extension = new SlugExtension($this->registry);
    }

    public function testGetFilter(): void
    {
        $this->assertEquals(new DoctrineEmptyCollectionFilter(), $this->extension->getFilter());
    }

    public function testGetMatcher(): void
    {
        $context = new DraftContext();
        $context->offsetSet('source', $this->getEntity(DraftableEntityStub::class));
        $this->extension->setContext($context);
        $this->assertEquals(new PropertiesNameMatcher(['field1']), $this->extension->getMatcher());
    }

    public function testIsSupport(): void
    {
        $source = new Page();
        $this->assertTrue($this->extension->isSupport($source));
    }
}
