<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;

class IndexerInputValidatorTest extends \PHPUnit\Framework\TestCase
{
    use ContextTrait;

    const WEBSITE_ID = 1;

    /**
     * @var SearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mappingProvider;

    /**
     * @var WebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteProvider;

    /**
     * @var IndexerInputValidator
     */
    private $testable;

    protected function setUp(): void
    {
        $this->mappingProvider = $this->createMock(SearchMappingProvider::class);

        $this->mappingProvider
            ->expects($this->any())
            ->method('isClassSupported')
            ->willReturnCallback(fn ($class) => class_exists($class, true));
        $this->mappingProvider
            ->expects($this->any())
            ->method('getEntityClasses')
            ->willReturn([TestActivity::class]);

        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->websiteProvider
            ->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn([101,102]);

        $this->testable = new IndexerInputValidator(
            $this->websiteProvider,
            $this->mappingProvider
        );
    }

    public function testIncoherentEntityInput()
    {
        $this->expectException(\LogicException::class);
        $context = [];
        $context = $this->setContextEntityIds($context, [1, 2, 3]);
        $this->testable->validateRequestParameters(['class1', 'class2'], $context);
    }
}
