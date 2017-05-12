<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Method\View\Factory;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\View\Factory\AuthorizeNetPaymentMethodViewFactory;
use Oro\Bundle\AuthorizeNetBundle\Method\View\AuthorizeNetPaymentMethodView;
use Symfony\Component\Form\FormFactoryInterface;

class AuthorizeNetPaymentMethodViewFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var AuthorizeNetPaymentMethodViewFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->factory = new AuthorizeNetPaymentMethodViewFactory($this->formFactory);
    }

    public function testCreate()
    {
        /** @var AuthorizeNetConfigInterface $config */
        $config = $this->createMock(AuthorizeNetConfigInterface::class);

        $expectedView = new AuthorizeNetPaymentMethodView($this->formFactory, $config);

        $this->assertEquals($expectedView, $this->factory->create($config));
    }
}
