<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class WYSIWYGTypeStub extends WYSIWYGType
{
    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
    }
}
