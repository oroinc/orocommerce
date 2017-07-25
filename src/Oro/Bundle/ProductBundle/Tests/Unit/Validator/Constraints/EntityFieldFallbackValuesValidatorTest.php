<?php

namespace Oro\Bundle\ProductBundle\Tests\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Validator\Constraints\EntityFieldFallbackValues;
use Oro\Bundle\ProductBundle\Validator\Constraints\EntityFieldFallbackValuesValidator;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;

class EntityFieldFallbackValuesValidatorTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'OroEntityBundle:EntityFieldFallbackValue';
    protected $validChoices = ["short", "two-columns", "list"];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PageTemplatesManager
     */
    protected $pageTemplatesManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityFieldFallbackValues
     */
    protected $constraint;

    /**
     * @var EntityFieldFallbackValuesValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->pageTemplatesManager = $this->getMockBuilder('Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager')
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
                ProductType::PAGE_TEMPLATE_ROUTE_NAME => ["choices" => array_flip($this->validChoices)]
            ]);

        $this->constraint = new EntityFieldFallbackValues(['route' => ProductType::PAGE_TEMPLATE_ROUTE_NAME]);
        $this->validator = new EntityFieldFallbackValuesValidator($this->pageTemplatesManager);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    /**
     * @param $scalarValue
     * @dataProvider validateProvider
     */
    public function testValidate($scalarValue)
    {
        $this->context
            ->expects(null == $scalarValue
            || in_array($scalarValue, $this->validChoices)? $this->never() : $this->once())
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
            ["short"],
            ["two-columns"],
            ["list"],
            ["short-invalid"],
            ["LIST"],
            ["two"],
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
