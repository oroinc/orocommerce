<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntitySelectTypeStub extends EntityTypeStub
{
    /** @var AbstractType | null */
    protected $formType;

    public function __construct(array $choices, $name = 'entity', $formType = null)
    {
        parent::__construct($choices, $name);
        $this->formType = $formType;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        if ($this->formType) {
            $this->formType->configureOptions($resolver);
        }
    }
}