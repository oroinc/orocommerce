<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderRegistry;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\RegexPlaceholderDecorator;

class RegexPlaceholderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    public function testReplaceDefault()
    {
        /** @var PlaceholderRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(PlaceholderRegistry::class);

        /** @var RegexPlaceholderDecorator $placeholder */
        $placeholder = new RegexPlaceholderDecorator($registry);

        $placeholder1 = $this->createMock(PlaceholderInterface::class);
        $placeholder1->expects($this->once())->method('getPlaceholder')
            ->willReturn('PLACEHOLDER1');

        $placeholder2 = $this->createMock(PlaceholderInterface::class);
        $placeholder2->expects($this->once())->method('getPlaceholder')
            ->willReturn('PLACEHOLDER2');

        $registry->expects($this->once())->method('getPlaceholders')->willReturn([$placeholder1, $placeholder2]);

        $this->assertEquals(
            'string_.+?_.+?',
            $placeholder->replaceDefault('string_PLACEHOLDER1_PLACEHOLDER2')
        );
    }
}
