<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\RedirectBundle\Twig\TwigExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class TwigExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;
    use EntityTrait;

    /**
     * @var SlugEntityGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $generator;

    /**
     * @var TwigExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(SlugEntityGenerator::class);

        $container = self::getContainerBuilder()
            ->add(SlugEntityGenerator::class, $this->generator)
            ->getContainer($this);

        $this->extension = new TwigExtension($container);
    }

    public function testGetSlugsByEntitySlugPrototypes()
    {
        /** @var LocalizedFallbackValue $slugPrototype */
        $slugPrototype = $this->getEntity(LocalizedFallbackValue::class, ['string' => 'something']);
        $entity = (new SluggableEntityStub())->addSlugPrototype($slugPrototype);

        $slug = new Slug();
        $slugs = new ArrayCollection([$slug]);

        $this->generator->expects($this->once())
            ->method('getSlugsByEntitySlugPrototypes')
            ->with($entity)
            ->willReturn($slugs);

        $this->assertEquals(
            $slugs,
            self::callTwigFilter($this->extension, 'get_slug_urls_for_prototypes', [$entity])
        );
    }
}
