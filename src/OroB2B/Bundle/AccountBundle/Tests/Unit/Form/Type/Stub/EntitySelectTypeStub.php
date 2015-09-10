<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;

class EntitySelectTypeStub extends EntityTypeStub
{
    /** @var AbstractType|null */
    protected $formType;

    /**
     * @param array $choices
     * @param string $name
     * @param AbstractType|null $formType
     */
    public function __construct(array $choices, $name, $formType = null)
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
