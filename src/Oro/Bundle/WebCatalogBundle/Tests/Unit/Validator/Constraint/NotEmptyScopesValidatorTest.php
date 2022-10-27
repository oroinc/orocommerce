<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Validator\Constraint;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NotEmptyScopes;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NotEmptyScopesValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotEmptyScopesValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $scopeManager;

    protected function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        parent::setUp();
    }

    protected function createValidator(): NotEmptyScopesValidator
    {
        return new NotEmptyScopesValidator($this->scopeManager);
    }

    private function getScope(int $id): Scope
    {
        $scope = new Scope();
        ReflectionUtil::setId($scope, $id);

        return $scope;
    }

    public function testValidateNull()
    {
        $constraint = new NotEmptyScopes();
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateEmptyCollection()
    {
        $constraint = new NotEmptyScopes();
        $this->validator->validate(new ArrayCollection(), $constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider validValuesDataProvider
     */
    public function testValidateValid(ArrayCollection $value, WebCatalog $webCatalog, Scope $defaultScope)
    {
        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with(
                'web_content',
                [ScopeWebCatalogProvider::WEB_CATALOG => $webCatalog]
            )
            ->willReturn($defaultScope);

        $constraint = new NotEmptyScopes();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function validValuesDataProvider(): array
    {
        $webCatalog = new WebCatalog();
        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $scope1 = $this->getScope(1);
        $scope2 = $this->getScope(2);
        $defaultScope = $this->getScope(3);

        return [
            'only default variant without default scope' => [
                new ArrayCollection([
                    (new ContentVariant())->addScope($scope1)->setNode($contentNode)->setDefault(true)
                ]),
                $webCatalog,
                $defaultScope
            ],
            'only default variant with default scope' => [
                new ArrayCollection([
                    (new ContentVariant())->addScope($defaultScope)->setNode($contentNode)->setDefault(true)
                ]),
                $webCatalog,
                $defaultScope
            ],
            'default variant without default scope and variant' => [
                new ArrayCollection([
                    (new ContentVariant())->addScope($scope1)->setNode($contentNode)->setDefault(true),
                    (new ContentVariant())->addScope($scope2)->setNode($contentNode)
                ]),
                $webCatalog,
                $defaultScope
            ],
            'default variant with default scope and variant' => [
                new ArrayCollection([
                    (new ContentVariant())->addScope($defaultScope)->setNode($contentNode)->setDefault(true),
                    (new ContentVariant())->addScope($scope2)->setNode($contentNode)
                ]),
                $webCatalog,
                $defaultScope
            ],
        ];
    }

    public function testValidateInvalid()
    {
        $webCatalog = new WebCatalog();
        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $scope1 = $this->getScope(1);
        $defaultScope = $this->getScope(3);

        $values = [
            (new ContentVariant())->addScope($scope1)->setNode($contentNode)->setDefault(true),
            (new ContentVariant())->addScope($defaultScope)->setNode($contentNode),
        ];

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with(
                'web_content',
                [ScopeWebCatalogProvider::WEB_CATALOG => $webCatalog]
            )
            ->willReturn($defaultScope);

        $constraint = new NotEmptyScopes();
        $this->validator->validate(new ArrayCollection($values), $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path[1].scopes')
            ->assertRaised();
    }
}
