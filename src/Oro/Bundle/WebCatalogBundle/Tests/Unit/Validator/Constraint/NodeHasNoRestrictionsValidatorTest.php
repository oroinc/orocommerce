<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NodeHasNoRestrictions;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NodeHasNoRestrictionsValidator;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NodeHasNoRestrictionsValidatorTest extends ConstraintValidatorTestCase
{
    private ConfigManager|MockObject $configManager;

    private ConfigHandler|MockObject $configHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->configHandler = $this->createMock(ConfigHandler::class);
        $this->configHandler->expects(self::any())
            ->method('getConfigManager')
            ->willReturn($this->configManager);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): NodeHasNoRestrictionsValidator
    {
        return new NodeHasNoRestrictionsValidator($this->configHandler);
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(null, new NotBlank());
    }

    public function testValidateUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new NodeHasNoRestrictions());
    }

    /**
     * @dataProvider getValidateValidDataProvider
     */
    public function testValidateValid(array $restrictions): void
    {
        $this->configManager->expects(self::once())
            ->method('getScopeEntityName')
            ->willReturn('app');

        $scope1 = new StubScope($restrictions);
        $contentNode = new ContentNode();
        $contentNode->addScope($scope1);

        $this->validator->validate($contentNode, new NodeHasNoRestrictions());

        $this->assertNoViolation();
    }

    public function getValidateValidDataProvider(): array
    {
        return [
            [
                [
                    'localization' => null,
                    'customer' => null,
                    'customerGroup' => null,
                    'website' => null,
                ],
            ],
            [
                [
                    'localization' => null,
                    'customer' => null,
                    'customerGroup' => null,
                    'website' => new Website(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider getValidateInvalidDataProvider
     */
    public function testValidateInvalid(array $restrictions): void
    {
        $this->configManager->expects(self::once())
            ->method('getScopeEntityName')
            ->willReturn('app');

        $scope1 = new StubScope($restrictions);
        $contentNode = new ContentNode();
        $contentNode->addScope($scope1);

        $constraint = new NodeHasNoRestrictions();
        $this->validator->validate($contentNode, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.contentNode')
            ->assertRaised();
    }

    public function getValidateInvalidDataProvider(): array
    {
        return [
            [
                [
                    'localization' => new Localization(),
                    'customer' => null,
                    'customerGroup' => null,
                    'website' => null,
                ],
            ],
            [
                [
                    'localization' => null,
                    'customer' => new Customer(),
                    'customerGroup' => null,
                    'website' => null,
                ],
            ],
            [
                [
                    'localization' => null,
                    'customer' => null,
                    'customerGroup' => new CustomerGroup(),
                    'website' => null,
                ],
            ],
            [
                [
                    'localization' => new Localization(),
                    'customer' => new Customer(),
                    'customerGroup' => new CustomerGroup(),
                    'website' => null,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getValidateInvalidForWebsiteScopeDataProvider
     */
    public function testValidateInvalidForWebsiteScope(array $restrictions): void
    {
        $this->configManager->expects(self::once())
            ->method('getScopeEntityName')
            ->willReturn('website');

        $scope1 = new StubScope($restrictions);
        $contentNode = new ContentNode();
        $contentNode->addScope($scope1);

        $constraint = new NodeHasNoRestrictions();
        $this->validator->validate($contentNode, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.contentNode')
            ->assertRaised();
    }

    public function getValidateInvalidForWebsiteScopeDataProvider(): array
    {
        return array_merge(
            $this->getValidateInvalidDataProvider(),
            [
                [
                    [
                        'localization' => null,
                        'customer' => null,
                        'customerGroup' => null,
                        'website' => new Website(),
                    ],
                ],
                [
                    [
                        'localization' => new Localization(),
                        'customer' => new Customer(),
                        'customerGroup' => new CustomerGroup(),
                        'website' => new Website(),
                    ],
                ],
            ]
        );
    }
}
