<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductPageTemplate;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductPageTemplateValidator;
use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductPageTemplateValidatorTest extends ConstraintValidatorTestCase
{
    private const VALID_CHOICES = ['short', 'two-columns', 'list'];

    /** @var PageTemplatesManager|\PHPUnit\Framework\MockObject\MockObject */
    private $pageTemplatesManager;

    protected function setUp(): void
    {
        $this->pageTemplatesManager = $this->createMock(PageTemplatesManager::class);

        /* values are saved in "choices" array as keys in the form. ex:
        choices = [
                "short" => 1,
                "two-columns" => 2,
                "list" => 3
            ]
        */
        $this->pageTemplatesManager->expects($this->any())
            ->method('getRoutePageTemplates')
            ->willReturn([
                ProductType::PAGE_TEMPLATE_ROUTE_NAME => ['choices' => array_flip(self::VALID_CHOICES)]
            ]);

        parent::setUp();
    }

    protected function createValidator()
    {
        return new ProductPageTemplateValidator($this->pageTemplatesManager);
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($scalarValue)
    {
        $constraint = new ProductPageTemplate(['route' => ProductType::PAGE_TEMPLATE_ROUTE_NAME]);
        $this->validator->validate($this->getEntityFieldFallbackValue($scalarValue), $constraint);

        $valueIsValid = (null === $scalarValue) || in_array($scalarValue, self::VALID_CHOICES, true);
        if ($valueIsValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->assertRaised();
        }
    }

    public function validateProvider(): array
    {
        return [
            ['short'],
            ['two-columns'],
            ['list'],
            ['short-invalid'],
            ['LIST'],
            ['two'],
            [null],
            [123]
        ];
    }

    private function getEntityFieldFallbackValue($scalarValue): EntityFieldFallbackValue
    {
        // entity is being validated after the transformer has been applied, so we set the value in arrayValue
        // see PageTemplateEntityFieldFallbackValueTransformer

        $entityFieldFallbackValue = new EntityFieldFallbackValue();
        $entityFieldFallbackValue->setArrayValue([ProductType::PAGE_TEMPLATE_ROUTE_NAME => $scalarValue]);

        return $entityFieldFallbackValue;
    }
}
