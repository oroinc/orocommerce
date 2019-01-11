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
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class ConsentContentNodeValidatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var ConsentContextProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextProvider;

    /**
     * @var ContentNodeTreeResolverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentNodeTreeResolver;

    /**
     * @var ConsentContentNodeValidator
     */
    private $nodeValidator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->contextProvider = $this->createMock(ConsentContextProvider::class);
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);
        $this->nodeValidator = new ConsentContentNodeValidator(
            $this->logger,
            $this->contextProvider,
            $this->contentNodeTreeResolver
        );
    }

    /**
     * @dataProvider isValidProvider
     *
     * @param Consent                $consent
     * @param ContentNode            $contentNode
     * @param Scope|null             $scope
     * @param bool                   $logErrorsEnabled
     * @param bool                   $isContentNodeResolved
     * @param ResolvedContentVariant $contentVariant
     * @param bool                   $expectedError
     * @param string                 $errorMsg
     * @param bool                   $expectedResult
     */
    public function testIsValid(
        Consent $consent,
        ContentNode $contentNode,
        Scope $scope = null,
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

        if (!$expectedError) {
            $this->logger
                ->expects($this->never())
                ->method('error');
        } else {
            $this->logger
                ->expects($this->once())
                ->method('error')
                ->with($errorMsg);
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
            ->with($contentNode, $scope)
            ->willReturn($resolvedContentNode);

        $this->assertEquals($expectedResult, $this->nodeValidator->isValid($contentNode, $consent, $logErrorsEnabled));
    }

    /**
     * @return array
     */
    public function isValidProvider()
    {
        $consent = $this->getEntity(Consent::class, ['id' => 1]);
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 2]);

        $scope = $this->getEntity(Scope::class, ['id' => 123]);
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
                'errorMsg' => "",
                'expectedResult' => false
            ],
            "Can't resolve content node and log validation error enabled" => [
                'consent' => $consent,
                'contentNode' => $contentNode,
                'scope' => $scope,
                'logErrorsEnabled' => true,
                'isContentNodeResolved' => false,
                'contentVariant' => $contentVariantWithCorrectType,
                'expectedError' => true,
                'errorMsg' => "Failed to resolve 'ContentNode' in Consent with id '1' with Scope with id '123'!",
                'expectedResult' => false
            ],
            "Can't resolve content node and log validation error disabled" => [
                'consent' => $consent,
                'contentNode' => $contentNode,
                'scope' => $scope,
                'logErrorsEnabled' => false,
                'isContentNodeResolved' => false,
                'contentVariant' => $contentVariantWithCorrectType,
                'expectedError' => false,
                'errorMsg' => "",
                'expectedResult' => false
            ],
            "Content variant type is invalid and log validation error enabled" => [
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
            "Content variant type is invalid and log validation error disabled" => [
                'consent' => $consent,
                'contentNode' => $contentNode,
                'scope' => $scope,
                'logErrorsEnabled' => false,
                'isContentNodeResolved' => true,
                'contentVariant' => $contentVariantWithIncorrectType,
                'expectedError' => false,
                'errorMsg' => "",
                'expectedResult' => false
            ],
            "Content variant is valid" => [
                'consent' => $consent,
                'contentNode' => $contentNode,
                'scope' => $scope,
                'logErrorsEnabled' => true,
                'isContentNodeResolved' => true,
                'contentVariant' => $contentVariantWithCorrectType,
                'expectedError' => false,
                'errorMsg' => "",
                'expectedResult' => true
            ]
        ];
    }
}
