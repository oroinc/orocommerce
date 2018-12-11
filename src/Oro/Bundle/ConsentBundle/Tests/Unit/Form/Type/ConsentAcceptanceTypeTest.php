<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConsentBundle\Form\DataTransformer\CustomerConsentsTransformer;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsentAcceptanceTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConsentAcceptanceType
     */
    private $formType;

    /**
     * @var CustomerConsentsTransformer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transformer = $this->createMock(CustomerConsentsTransformer::class);

        $this->formType = new ConsentAcceptanceType($this->transformer);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->transformer);

        $this->formType->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'property_path' => 'acceptedConsents',
                'empty_data' => [],
                'label' => false
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_customer_consents', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(HiddenType::class, $this->formType->getParent());
    }
}
