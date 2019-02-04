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
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NotEmptyScopesTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeManager;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var NotEmptyScopesValidator
     */
    protected $validator;

    /**
     * @var NotEmptyScopes
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new NotEmptyScopes();

        $this->validator = new NotEmptyScopesValidator($this->scopeManager);
        $this->validator->initialize($this->context);
    }

    public function testValidateNull()
    {
        $value = null;
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateEmptyCollection()
    {
        $value = new ArrayCollection();
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @dataProvider validValuesDataProvider
     * @param ArrayCollection $value
     * @param WebCatalog $webCatalog
     * @param Scope $defaultScope
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

        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validValuesDataProvider(): array
    {
        $webCatalog = new WebCatalog();
        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);

        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);
        /** @var Scope $defaultScope */
        $defaultScope = $this->getEntity(Scope::class, ['id' => 3]);

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

        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        /** @var Scope $defaultScope */
        $defaultScope = $this->getEntity(Scope::class, ['id' => 3]);

        $values = [
            (new ContentVariant())->addScope($scope1)->setNode($contentNode)->setDefault(true),
            (new ContentVariant())->addScope($defaultScope)->setNode($contentNode),
        ];
        $value = new ArrayCollection($values);

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with(
                'web_content',
                [ScopeWebCatalogProvider::WEB_CATALOG => $webCatalog]
            )
            ->willReturn($defaultScope);

        $constraintBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $constraintBuilder->expects($this->once())
            ->method('atPath')
            ->with('[1].scopes')
            ->willReturnSelf();
        $constraintBuilder->expects($this->once())
            ->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($constraintBuilder);

        $this->validator->validate($value, $this->constraint);
    }
}
