<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData\CustomizeFormDataProcessorTestCase;
use Oro\Bundle\ShippingBundle\Api\Processor\UpdateWeightByValueAndUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Tests\Unit\Api\Stub\ProductShippingOptionsStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

class UpdateWeightByValueAndUnitTest extends CustomizeFormDataProcessorTestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var UpdateWeightByValueAndUnit */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->processor = new UpdateWeightByValueAndUnit($this->doctrine);
    }

    private function getStubEntity(Weight $weight = null): ProductShippingOptionsStub
    {
        $entity = new ProductShippingOptionsStub();
        $entity->setWeight($weight);

        return $entity;
    }

    private function getForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => ProductShippingOptionsStub::class])
            ->add('weightUnit', TextType::class, ['mapped' => false])
            ->add('weightValue', TextType::class, ['mapped' => false])
            ->getForm();
    }

    /**
     * @dataProvider requestDataProvider
     */
    public function testPreSubmit(
        array $data,
        ProductShippingOptionsStub $entity,
        ProductShippingOptionsStub $expectedEntity
    ): void {
        $form = $this->getForm();
        $form->setData($entity);

        if (isset($data['weightUnit']['id'])) {
            $expectedWeightUnit = $this->getEntity(WeightUnit::class, ['code' => $data['weightUnit']['id']]);
            $repository = $this->createMock(EntityRepository::class);

            $this->doctrine->expects(self::once())
                ->method('getRepository')
                ->with(WeightUnit::class)
                ->willReturn($repository);
            $repository->expects(self::once())
                ->method('find')
                ->with($data['weightUnit']['id'])
                ->willReturn($expectedWeightUnit);
        }

        $this->context->setEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT);
        $this->context->setData($data);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertEquals($expectedEntity, $entity);
    }

    public function requestDataProvider(): array
    {
        /** @var WeightUnit $weightUnit */
        $weightUnit = $this->getEntity(WeightUnit::class, ['code' => 'kg']);
        /** @var WeightUnit $usWeightUnit */
        $usWeightUnit = $this->getEntity(WeightUnit::class, ['code' => 'lbs']);

        return [
            'empty request'             => [
                'data'           => [],
                'entity'         => $this->getStubEntity(),
                'expectedEntity' => $this->getStubEntity()
            ],
            'empty unit'            => [
                'data'           => ['weightValue' => 10],
                'entity'         => $this->getStubEntity(),
                'expectedEntity' => $this->getStubEntity(Weight::create(10, null))
            ],
            'empty value'               => [
                'data'           => ['weightUnit' => ['id' => 'kg']],
                'entity'         => $this->getStubEntity(),
                'expectedEntity' => $this->getStubEntity(Weight::create(null, $weightUnit))
            ],
            'empty unit, has weight object' => [
                'data'           => ['weightValue' => 10],
                'entity'         => $this->getStubEntity(Weight::create(20, $weightUnit)),
                'expectedEntity' => $this->getStubEntity(Weight::create(10, $weightUnit))
            ],
            'empty value, has weight object'    => [
                'data'           => ['weightUnit' => ['id' => 'kg']],
                'entity'         => $this->getStubEntity(Weight::create(10, $usWeightUnit)),
                'expectedEntity' => $this->getStubEntity(Weight::create(10, $weightUnit))
            ],
            'value & unit exist'    => [
                'data'           => ['weightUnit' => ['id' => 'kg'], 'weightValue' => 10],
                'entity'         => $this->getStubEntity(),
                'expectedEntity' => $this->getStubEntity(Weight::create(10, $weightUnit))
            ]
        ];
    }

    public function testPostValidate(): void
    {
        $form = $this->getForm();
        $form->addError(new FormError(
            'some error one',
            null,
            [],
            null,
            new ConstraintViolation('', '', [], null, 'data.weight.value', '')
        ));
        $form->addError(new FormError(
            'some error two',
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
