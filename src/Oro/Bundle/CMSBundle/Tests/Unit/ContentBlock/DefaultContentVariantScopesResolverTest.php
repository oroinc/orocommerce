<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentBlock;

use Oro\Bundle\CMSBundle\ContentBlock\DefaultContentVariantScopesResolver;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTrait;

class DefaultContentVariantScopesResolverTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DefaultContentVariantScopesResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DefaultContentVariantScopesResolver();
    }

    public function testResolve()
    {
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);

        $contentBlock = new ContentBlock();

        $defaultVariant = new TextContentVariant();
        $defaultVariant->setDefault(true);
        $defaultVariant->addScope($scope1);

        $variant = new TextContentVariant();
        $variant->setDefault(false);
        $variant->addScope($scope1);

        $contentBlock->addContentVariant($defaultVariant);
        $contentBlock->addContentVariant($variant);

        $this->resolver->resolve($contentBlock);

        $this->assertEmpty($contentBlock->getDefaultVariant()->getScopes());
        $this->assertCount(1, $variant->getScopes());
    }
}
