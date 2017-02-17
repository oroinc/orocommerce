<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

class IndexerInputValidatorTest extends \PHPUnit_Framework_TestCase
{
    use ContextTrait;

    const WEBSITE_ID = 1;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var WebsiteSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mappingProvider;

    /**
     * @var IndexerInputValidator
     */
    private $testable;

    public function setUp()
    {
        $websiteRepository = $this->createMock(WebsiteRepository::class);
        $websiteRepository->method('getWebsiteIdentifiers')
            ->willReturn([self::WEBSITE_ID]);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->doctrineHelper->method('getEntityRepository')
            ->with(Website::class)
            ->willReturn($websiteRepository);

        $this->mappingProvider = $this->createMock(WebsiteSearchMappingProvider::class);

        $this->mappingProvider->method('isClassSupported')
            ->willReturn(true);

        $this->testable = new IndexerInputValidator(
            $this->doctrineHelper,
            $this->mappingProvider
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testIncoherentEntityInput()
    {
        $context = [];
        $context = $this->setContextEntityIds($context, [1,2,3]);
        $this->testable->validateReindexRequest(['class1','class2'], $context);
    }

    /**
     * @expectedException \LogicException
     */
    public function testEmptyEntitiesInput()
    {
        $this->testable->validateReindexRequest([], []);
    }

    public function testValidation()
    {
        $context = [];
        $context = $this->setContextEntityIds($context, [1,2,3]);
        $result = $this->testable->validateReindexRequest(['class1'], $context);

        $this->assertEquals(
            $result,
            [
                ['class1'],
                [self::WEBSITE_ID]
            ]
        );
    }
}
