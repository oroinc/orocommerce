<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceType;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class ProductPriceCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceCollectionType
     */
    protected $formType;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new ProductPriceCollectionType($this->registry);
        $this->formType->setDataClass('\stdClass');
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testGetParent()
    {
        $this->assertInternalType('string', $this->formType->getParent());
        $this->assertEquals(CollectionType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals(ProductPriceCollectionType::NAME, $this->formType->getName());
    }

    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('type', $options);
                        $this->assertEquals(ProductPriceType::NAME, $options['type']);

                        $this->assertArrayHasKey('show_form_when_empty', $options);
                        $this->assertEquals(false, $options['show_form_when_empty']);

                        $this->assertArrayHasKey('options', $options);
                        $this->assertNotEmpty($options['options']);
                        $this->assertArrayHasKey('data_class', $options['options']);

                        return true;
                    }
                )
            );

        $this->formType->setDefaultOptions($resolver);
    }

    public function testFinishView()
    {
        $firstPriceList = new PriceList();
        $this->setProperty($firstPriceList, 'id', 1);
        $firstPriceList->setName('First Price List')
            ->setCurrencies(['USD', 'EUR']);

        $secondPriceList = new PriceList();
        $this->setProperty($secondPriceList, 'id', 2);
        $secondPriceList->setName('Second Price List')
            ->setCurrencies(['CAD', 'USD']);

        $priceLists = [$firstPriceList, $secondPriceList];

        /** @var \Symfony\Component\Form\FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = new FormView();

        /** @var \Symfony\Component\Form\FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($priceLists));

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BPricingBundle:PriceList')
            ->will($this->returnValue($repository));

        $this->formType->finishView($view, $form, []);
        $this->assertEquals(
            json_encode([
                '1' => ['USD', 'EUR'],
                '2' => ['CAD', 'USD']
            ]),
            $view->vars['attr']['data-currencies']
        );
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
