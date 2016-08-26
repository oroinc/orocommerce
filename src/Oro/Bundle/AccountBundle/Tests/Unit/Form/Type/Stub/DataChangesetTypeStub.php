<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;

class DataChangesetTypeStub extends DataChangesetType
{
    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }
}
