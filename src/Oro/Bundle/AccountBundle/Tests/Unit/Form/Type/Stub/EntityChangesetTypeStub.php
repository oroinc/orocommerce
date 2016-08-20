<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

class EntityChangesetTypeStub extends EntityChangesetType
{
    /** {@inheritdoc} */
    public function __construct()
    {
    }

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }
}
