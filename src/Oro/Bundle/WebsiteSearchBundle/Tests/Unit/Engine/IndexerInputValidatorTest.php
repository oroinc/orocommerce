<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

class IndexerInputValidatorTest extends \PHPUnit\Framework\TestCase
{
    use ContextTrait;

    const WEBSITE_ID = 1;

    /**
     * @var WebsiteSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject
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
        $this->mappingProvider = $this->createMock(WebsiteSearchMappingProvider::class);

        $this->mappingProvider->method('isClassSupported')
            ->willReturn(true);

        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->websiteProvider->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn([self::WEBSITE_ID]);

        $this->testable = new IndexerInputValidator(
            $this->websiteProvider,
            $this->mappingProvider
        );
    }

    public function testIncoherentEntityInput()
    {
        $this->expectException(\LogicException::class);
        $context = [];
        $context = $this->setContextEntityIds($context, [1,2,3]);
        $this->testable->validateRequestParameters(['class1','class2'], $context);
    }

    public function testEmptyEntitiesInput()
    {
        $this->expectException(\LogicException::class);
        $this->testable->validateRequestParameters([], []);
    }

    public function testValidation()
    {
        $context = [];
        $context = $this->setContextEntityIds($context, [1,2,3]);
        $result = $this->testable->validateRequestParameters(['class1'], $context);

        $this->assertEquals(
            $result,
            [
                ['class1'],
                [self::WEBSITE_ID]
            ]
        );
    }
}
