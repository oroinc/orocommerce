<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductPageTemplate;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductPageTemplateValidator;
use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProductPageTemplateValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    protected static $validChoices = ['short', 'two-columns', 'list'];

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PageTemplatesManager
     */
    protected $pageTemplatesManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductPageTemplate
     */
    protected $constraint;

    /**
     * @var ProductPageTemplateValidator
     */
    protected $validator;

    protected function setUp(): void
    {
        $this->pageTemplatesManager = $this->getMockBuilder(PageTemplatesManager::class)
            ->disableOriginalConstructor()
            ->getMock();

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
                ProductType::PAGE_TEMPLATE_ROUTE_NAME => ['choices' => array_flip(self::$validChoices)]
            ]);

        $this->constraint = new ProductPageTemplate(['route' => ProductType::PAGE_TEMPLATE_ROUTE_NAME]);
        $this->validator = new ProductPageTemplateValidator($this->pageTemplatesManager);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($scalarValue)
    {
        $valueIsValid = (null == $scalarValue) || in_array($scalarValue, self::$validChoices, true);
        $this->context
            ->expects($valueIsValid ? $this->never() : $this->once())
            ->method('addViolation')
            ->with($this->constraint->message);

        $this->validator->validate(
            $this->getEntityFieldFallbackValue($scalarValue),
            $this->constraint
        );
    }

    /**
     * @return array
     */
    public function validateProvider()
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

    /**
     * @param $scalarValue
     * @return EntityFieldFallbackValue
     */
    private function getEntityFieldFallbackValue($scalarValue)
    {
        // entity is being validated after the transformer has been applied, so we set the value in arrayValue
        // see PageTemplateEntityFieldFallbackValueTransformer

        $entityFieldFallbackValue = new EntityFieldFallbackValue();
        $entityFieldFallbackValue->setArrayValue([ProductType::PAGE_TEMPLATE_ROUTE_NAME => $scalarValue]);

        return $entityFieldFallbackValue;
    }
}
