<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Resolver\UniqueContentNodeSlugPrototypesResolver;
use Oro\Component\Testing\Unit\EntityTrait;

class UniqueContentNodeSlugPrototypesResolverTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var UniqueContentNodeSlugPrototypesResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->resolver = new UniqueContentNodeSlugPrototypesResolver($this->registry);
    }

    public function testResolveSlugPrototypeUniqueness()
    {
        $parentNode = new ContentNode();
        /** @var ContentNode $contentNode */
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        /** @var LocalizedFallbackValue $slugPrototype1 */
        $slugPrototype1 = $this->getEntity(LocalizedFallbackValue::class, ['id' => 1]);
        $slugPrototype1->setString('Test');

        /** @var LocalizedFallbackValue $slugPrototype2 */
        $slugPrototype2 = $this->getEntity(LocalizedFallbackValue::class, ['id' => 2]);
        $slugPrototype2->setString('test-unq');

        $contentNode->addSlugPrototype($slugPrototype1);
        $contentNode->addSlugPrototype($slugPrototype2);

        $repo = $this->createMock(ContentNodeRepository::class);
        $repo->expects($this->once())
            ->method('getSlugPrototypesByParent')
            ->with($parentNode, $contentNode)
            ->willReturn(['test']);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->resolver->resolveSlugPrototypeUniqueness($parentNode, $contentNode);

        $actual = [];
        foreach ($contentNode->getSlugPrototypes() as $slugPrototype) {
            $actual[] = [$slugPrototype->getString(), $slugPrototype->getId()];
        }

        $expected = [
            ['test-1', null],
            ['test-unq', 2]
        ];

        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
