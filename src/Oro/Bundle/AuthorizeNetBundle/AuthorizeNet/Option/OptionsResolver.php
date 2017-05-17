<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver as BaseOptionsResolver;

class OptionsResolver extends BaseOptionsResolver
{
    const ACCESS_EXCEPTION_MESSAGE = 'addOption is locked during resolve process';

    /**
     * @var bool
     */
    protected $addOptionLocked = false;

    /**
     * @var OptionsDependentInterface[]
     */
    protected $dependentOptions = [];

    /**
     * {@inheritdoc}
     */
    public function resolve(array $options = [])
    {
        $this->addOptionLocked = true;
        $this->handleDependentOptions($options);

        $result = parent::resolve($options);
        $this->addOptionLocked = false;

        return $result;
    }

    /**
     * @param OptionInterface $option
     * @return $this
     * @throws AccessException
     */
    public function addOption(OptionInterface $option)
    {
        if ($this->addOptionLocked) {
            throw new AccessException(self::ACCESS_EXCEPTION_MESSAGE);
        }

        if ($option instanceof OptionsDependentInterface) {
            $this->dependentOptions[] = $option;
        }

        $option->configureOption($this);

        return $this;
    }

    /**
     * @param array $options
     */
    protected function handleDependentOptions(array $options)
    {
        foreach ($this->dependentOptions as $dependentOption) {
            if ($dependentOption->isApplicableDependent($options)) {
                $dependentOption->configureDependentOption($this, $options);
            }
        }
    }
}
