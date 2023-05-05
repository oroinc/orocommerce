<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\ConsentBundle\Validator\ConsentContentNodeValidator;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;

class ConsentContentNodeValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ConsentContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contextProvider;

    /** @var ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeTreeResolver;

    /** @var ConsentContentNodeValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->contextProvider = $this->createMock(ConsentContextProvider::class);
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);

        $this->validator = new ConsentContentNodeValidator(
            $this->logger,
            $this->contextProvider,
            $this->contentNodeTreeResolver
        );
    }

    /**
     * @dataProvider isValidProvider
     */
    public function testIsValid(
        Consent $consent,
        ContentNode $contentNode,
        ?Scope $scope,
        bool $logErrorsEnabled,
        bool $isContentNodeResolved,
        ResolvedContentVariant $contentVariant,
        bool $expectedError,
        string $errorMsg,
        bool $expectedResult
    ) {
        $this->contextProvider->expects($this->any())
            ->method('getScope')
            ->willReturn($scope);

        if ($expectedError) {
            $this->logger->expects($this->once())
                ->method('error')
                ->with($errorMsg);
        } else {
            $this->logger->expects($this->never())
                ->method('error');
        }

        $resolvedContentNode = null;
        if ($isContentNodeResolved) {
            $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
            $resolvedContentNode->expects($this->any())
                ->method('getResolvedContentVariant')
                ->willReturn($contentVariant);
        }

        $this->contentNodeTreeResolver->expects($this->any())
            ->method('getResolvedContentNode')
            ->with($contentNode, $scope, ['tree_depth' => 0])
            ->willReturn($resolvedContentNode);

        $this->assertEquals($expectedResult, $this->validator->isValid($contentNode, $consent, $logErrorsEnabled));
    }

    public function isValidProvider(): array
    {
        $consent = new Consent();
        ReflectionUtil::setId($consent, 1);

        $contentNode = new ContentNode();
        ReflectionUtil::setId($contentNode, 2);

        $scope = new Scope();
        ReflectionUtil::setId($scope, 123);

        $contentVariantWithIncorrectType = new ResolvedContentVariant();
        $contentVariantWithIncorrectType->setType('incorrect_type');

        $contentVariantWithCorrectType = new ResolvedContentVariant();
        $contentVariantWithCorrectType->setType(CmsPageContentVariantType::TYPE);

        return [
            "Scope isn't present at the context" => [
                'consent' => $consent,
                'contentNode' => $contentNode,
                'scope' => null,
                'logErrorsEnabled' => true,
                'isContentNodeResolved' => false,
                'contentVariant' => $contentVariantWithCorrectType,
                'expectedError' => false,
                'errorMsg' => '',
                'expectedResult' => false
            ],
            "Can't resolve content node" => [
                'consent' => $consent,
                'contentNode' => $contentNode,
                'scope' => $scope,
                'logErrorsEnabled' => true,
                'isContentNodeResolved' => false,
                'contentVariant' => $contentVariantWithCorrectType,
                'expectedError' => false,
                'errorMsg' => '',
                'expectedResult' => true
            ],
            'Content variant type is invalid and log validation error enabled' => [
                'consent' => $consent,
                'contentNode' => $contentNode,
                'scope' => $scope,
                'logErrorsEnabled' => true,
                'isContentNodeResolved' => true,
                'contentVariant' => $contentVariantWithIncorrectType,
                'expectedError' => true,
                'errorMsg' => "Expected 'ContentVariant' with type 'cms_page' but got 'incorrect_type' ".
                    "in Consent with id '1' with 'Scope' with id '123'!",
                'expectedResult' => false
            ],
            'Content variant type is invalid and log validation error disabled' => [
                'consent' => $consent,
                'contentNode' => $contentNode,
                'scope' => $scope,
                'logErrorsEnabled' => false,
                'isContentNodeResolved' => true,
                'contentVariant' => $contentVariantWithIncorrectType,
                'expectedError' => false,
                'errorMsg' => '',
                'expectedResult' => false
            ],
            'Content variant is valid' => [
                'consent' => $consent,
                'contentNode' => $contentNode,
                'scope' => $scope,
                'logErrorsEnabled' => true,
                'isContentNodeResolved' => true,
                'contentVariant' => $contentVariantWithCorrectType,
                'expectedError' => false,
                'errorMsg' => '',
                'expectedResult' => true
            ]
        ];
    }
}
