<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\RedirectBundle\Twig\RedirectExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RedirectExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private SlugEntityGenerator&MockObject $generator;
    private RedirectExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->generator = $this->createMock(SlugEntityGenerator::class);

        $container = self::getContainerBuilder()
            ->add(SlugEntityGenerator::class, $this->generator)
            ->getContainer($this);

        $this->extension = new RedirectExtension($container);
    }

    public function testGetSlugsByEntitySlugPrototypes()
    {
        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('something');

        $entity = new SluggableEntityStub();
        $entity->addSlugPrototype($slugPrototype);

        $slug = new Slug();
        $slugs = new ArrayCollection([$slug]);

        $this->generator->expects(self::once())
            ->method('getSlugsByEntitySlugPrototypes')
            ->with($entity)
            ->willReturn($slugs);

        self::assertEquals(
            $slugs,
            self::callTwigFilter($this->extension, 'get_slug_urls_for_prototypes', [$entity])
        );
    }
}
