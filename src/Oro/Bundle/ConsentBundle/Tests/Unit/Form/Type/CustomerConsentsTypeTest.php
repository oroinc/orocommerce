<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConsentBundle\Form\DataTransformer\CustomerConsentsTransformer;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerConsentsTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CustomerConsentsType
     */
    protected $formType;

    /**
     * @var CustomerConsentsTransformer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transformer = $this->createMock(CustomerConsentsTransformer::class);

        $this->formType = new CustomerConsentsType($this->transformer);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->transformer);

        $this->formType->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'mapped' => false,
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
