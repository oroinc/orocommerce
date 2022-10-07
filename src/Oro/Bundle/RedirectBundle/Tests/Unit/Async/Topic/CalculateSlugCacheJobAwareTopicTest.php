<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheJobAwareTopic;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheTopic;
use Oro\Bundle\RedirectBundle\Async\Topic\DirectUrlTopicHelper;
use Symfony\Component\OptionsResolver\Debug\OptionsResolverIntrospector;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalculateSlugCacheJobAwareTopicTest extends \PHPUnit\Framework\TestCase
{
    private DirectUrlTopicHelper|\PHPUnit\Framework\MockObject\MockObject $directUrlTopicHelper;

    private CalculateSlugCacheJobAwareTopic $topic;

    protected function setUp(): void
    {
        $this->directUrlTopicHelper = $this->createMock(DirectUrlTopicHelper::class);

        $this->topic = new CalculateSlugCacheJobAwareTopic(new CalculateSlugCacheTopic($this->directUrlTopicHelper));
    }

    public function testConfigureMessageBody(): void
    {
        $resolver = new OptionsResolver();
        $this->directUrlTopicHelper
            ->expects(self::once())
            ->method('configureIdOption')
            ->with($resolver);

        $this->directUrlTopicHelper
            ->expects(self::once())
            ->method('configureEntityClassOption')
            ->with($resolver);

        $this->directUrlTopicHelper
            ->expects(self::once())
            ->method('configureRedirectOption')
            ->with($resolver);

        $this->topic->configureMessageBody($resolver);

        $optionsResolverIntrospector = new OptionsResolverIntrospector($resolver);
        self::assertContains(CalculateSlugCacheJobAwareTopic::JOB_ID, $resolver->getDefinedOptions());
        self::assertEquals(
            ['int'],
            $optionsResolverIntrospector->getAllowedTypes(CalculateSlugCacheJobAwareTopic::JOB_ID)
        );
    }
}
