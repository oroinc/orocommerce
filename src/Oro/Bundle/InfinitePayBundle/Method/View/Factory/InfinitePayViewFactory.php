<?php

namespace Oro\Bundle\InfinitePayBundle\Method\View\Factory;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Method\View\InfinitePayView;
use Symfony\Component\Form\FormFactoryInterface;

class InfinitePayViewFactory implements InfinitePayViewFactoryInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    public function __construct(
        FormFactoryInterface $formFactory
    ) {
        $this->formFactory = $formFactory;
    }

    /**
     * @inheritDoc
     */
    public function create(InfinitePayConfigInterface $config)
    {
        return new InfinitePayView($config, $this->formFactory);
    }
}
