<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixCollectionType;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Component\Testing\Unit\EntityTrait;

class MatrixGridOrderFormProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $formFactory;

    /** @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $router;

    /** @var MatrixGridOrderManager|\PHPUnit_Framework_MockObject_MockObject */
    private $matrixOrderManager;

    /** @var MatrixGridOrderFormProvider */
    private $provider;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->matrixOrderManager = $this->createMock(MatrixGridOrderManager::class);

        $this->provider = new MatrixGridOrderFormProvider($this->formFactory, $this->router);
        $this->provider->setMatrixOrderManager($this->matrixOrderManager);
    }

    public function testGetMatrixOrderForm()
    {
        /** @var Product $product **/
        $product = $this->getEntity(Product::class);

        $collection = new MatrixCollection();

        $form = $this->createMock(FormInterface::class);

        $this->matrixOrderManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product)
            ->willReturn($collection);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(MatrixCollectionType::class, $collection, [])
            ->willReturn($form);

        $this->assertSame($form, $this->provider->getMatrixOrderForm($product));
    }

    public function testGetMatrixOrderFormView()
    {
        /** @var Product $product **/
        $product = $this->getEntity(Product::class);

        $collection = new MatrixCollection();

        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(FormView::class);

        $this->matrixOrderManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product)
            ->willReturn($collection);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(MatrixCollectionType::class, $collection, [])
            ->willReturn($form);

        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->assertSame($formView, $this->provider->getMatrixOrderFormView($product));
    }
}
