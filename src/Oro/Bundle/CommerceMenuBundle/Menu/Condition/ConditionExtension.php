<?php

namespace Oro\Bundle\CommerceMenuBundle\Menu\Condition;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ConditionExtension implements ExtensionInterface
{
    const IS_ALLOWED_OPTION_KEY     = 'isAllowed';
    const CONDITION_KEY             = 'conditions';
    const DEFAULT_IS_ALLOWED_POLICY = true;

    /** @var ExpressionFunctionProviderInterface[] */
    private $providers = [];

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
            $options['extras'][self::IS_ALLOWED_OPTION_KEY] = $result;
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
     *
     * @return bool
     */
    private function alreadyDenied(array $options)
    {
        return array_key_exists('extras', $options) &&
        array_key_exists(self::IS_ALLOWED_OPTION_KEY, $options['extras']) &&
        ($options['extras'][self::IS_ALLOWED_OPTION_KEY] === false);
    }
}
