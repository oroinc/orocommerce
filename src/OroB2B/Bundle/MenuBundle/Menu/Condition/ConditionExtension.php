<?php

namespace OroB2B\Bundle\MenuBundle\Menu\Condition;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\MenuBundle\Menu\BuilderInterface;

class ConditionExtension implements ExtensionInterface
{
    const CONDITION_KEY = 'condition';
    const DEFAULT_IS_ALLOWED_POLICY = true;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var ExpressionFunctionProviderInterface[] $providers
     */
    protected $providers = [];

    /**
     * @param ExpressionFunctionProviderInterface $provider
     */
    public function addProvider(ExpressionFunctionProviderInterface $provider)
    {
        if (!in_array($provider, $this->providers, true)) {
            $this->providers[] = $provider;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptions(array $options)
    {
        if (!empty($options['extras'][self::CONDITION_KEY]) && !$this->alreadyDenied($options)) {
            $language = new ExpressionLanguage(null, $this->providers);
            $result = (bool)$language->evaluate($options['extras'][self::CONDITION_KEY]);
            $options['extras'][BuilderInterface::IS_ALLOWED_OPTION_KEY] = $result;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildItem(ItemInterface $item, array $options)
    {
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function alreadyDenied(array $options)
    {
        return array_key_exists('extras', $options) &&
        array_key_exists(BuilderInterface::IS_ALLOWED_OPTION_KEY, $options['extras']) &&
        ($options['extras'][BuilderInterface::IS_ALLOWED_OPTION_KEY] === false);
    }
}
