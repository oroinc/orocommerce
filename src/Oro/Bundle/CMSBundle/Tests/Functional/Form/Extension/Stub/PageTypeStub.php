<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Form\Extension\Stub;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PageTypeStub extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', WYSIWYGType::class, [
            'label' => 'label',
            'required' => false
        ]);
    }
}
