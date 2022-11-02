<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData\CustomizeFormDataProcessorTestCase;
use Oro\Bundle\ShippingBundle\Api\Processor\UpdateDimensionsByValueAndUnit;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Tests\Unit\Api\Stub\ProductShippingOptionsStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

class UpdateDimensionsByValueAndUnitTest extends CustomizeFormDataProcessorTestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var UpdateDimensionsByValueAndUnit */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->processor = new UpdateDimensionsByValueAndUnit($this->doctrine);
    }

    private function getStubEntity(Dimensions $dimensions = null): ProductShippingOptionsStub
    {
        $entity = new ProductShippingOptionsStub();
        $entity->setDimensions($dimensions);

        return $entity;
    }

    private function getForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => ProductShippingOptionsStub::class])
            ->add('dimensionsUnit', TextType::class, ['mapped' => false])
            ->add('dimensionsLength', TextType::class, ['mapped' => false])
            ->add('dimensionsWidth', TextType::class, ['mapped' => false])
            ->add('dimensionsHeight', TextType::class, ['mapped' => false])
            ->getForm();
    }

    /**
     * @dataProvider requestDataProvider
     */
    public function testPreSubmit(
        array                      $data,
        ProductShippingOptionsStub $entity,
        ProductShippingOptionsStub $expectedEntity
    ): void {
        $form = $this->getForm();
        $form->setData($entity);

        if (isset($data['dimensionsUnit']['id'])) {
            $expectedLengthUnit = $this->getEntity(LengthUnit::class, ['code' => $data['dimensionsUnit']['id']]);
            $repository = $this->createMock(EntityRepository::class);

            $this->doctrine->expects(self::once())
                ->method('getRepository')
                ->with(LengthUnit::class)
                ->willReturn($repository);
            $repository->expects(self::once())
                ->method('find')
                ->with($data['dimensionsUnit']['id'])
                ->willReturn($expectedLengthUnit);
        }

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertEquals($expectedEntity, $entity);
    }

    public function requestDataProvider(): array
    {
        /** @var LengthUnit $lengthUnit */
        $lengthUnit = $this->getEntity(LengthUnit::class, ['code' => 'm']);
        /** @var LengthUnit $usLengthUnit */
        $usLengthUnit = $this->getEntity(LengthUnit::class, ['code' => 'inch']);

        return [
            'empty request'             => [
                'data'           => [],
                'entity'         => $this->getStubEntity(),
                'expectedEntity' => $this->getStubEntity()
            ],
            'empty unit'            => [
                'data'           => [
                    'dimensionsLength' => 1,
                    'dimensionsWidth' => 10,
                    'dimensionsHeight' => 100
                ],
                'entity'         => $this->getStubEntity(),
                'expectedEntity' => $this->getStubEntity(Dimensions::create(1, 10, 100, null))
            ],
            'empty value'               => [
                'data'           => [
                    'dimensionsUnit' => ['id' => 'm']
                ],
                'entity'         => $this->getStubEntity(),
                'expectedEntity' => $this->getStubEntity(Dimensions::create(null, null, null, $lengthUnit))
            ],
            'empty unit, has dimensions object' => [
                'data'           => [
                    'dimensionsLength' => 1,
                    'dimensionsWidth' => 10,
                    'dimensionsHeight' => 100
                ],
                'entity'         => $this->getStubEntity(Dimensions::create(2, 20, 200, $lengthUnit)),
                'expectedEntity' => $this->getStubEntity(Dimensions::create(1, 10, 100, $lengthUnit))
            ],
            'partial values, has dimensions object' => [
                'data'           => [
                    'dimensionsLength' => 1,
                    'dimensionsHeight' => 100
                ],
                'entity'         => $this->getStubEntity(Dimensions::create(2, 20, 200, $lengthUnit)),
                'expectedEntity' => $this->getStubEntity(Dimensions::create(1, 20, 100, $lengthUnit))
            ],
            'empty value, has dimensions object'    => [
                'data'           => [
                    'dimensionsUnit' => ['id' => 'm']
                ],
                'entity'         => $this->getStubEntity(Dimensions::create(1, 10, 100, $usLengthUnit)),
                'expectedEntity' => $this->getStubEntity(Dimensions::create(1, 10, 100, $lengthUnit))
            ],
            'value & unit exist'    => [
                'data'           => [
                    'dimensionsLength' => 1,
                    'dimensionsWidth' => 10,
                    'dimensionsHeight' => 100,
                    'dimensionsUnit' => ['id' => 'm']
                ],
                'entity'         => $this->getStubEntity(),
                'expectedEntity' => $this->getStubEntity(Dimensions::create(1, 10, 100, $lengthUnit))
            ]
        ];
    }

    public function testPostValidate(): void
    {
        $form = $this->getForm();
        $form->addError(new FormError(
            'some error uno',
            null,
            [],
            null,
            new ConstraintViolation('', '', [], null, 'data.dimensions.value', '')
        ));
        $form->addError(new FormError(
            'some error due',
            null,
            [],
            null,
            new ConstraintViolation('', '', [], null, 'data.other', '')
        ));

        $this->context->setEvent(CustomizeFormDataContext::EVENT_POST_VALIDATE);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertEquals(
            ['data.value', 'data.other'],
            array_map(
                static function ($error) {
                    return $error->getCause()->getPropertyPath();
                },
                iterator_to_array($form->getErrors())
            )
        );
    }
}
