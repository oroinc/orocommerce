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
        if (isset($options['extras']) && isset($options['extras'][self::CONDITION_KEY])) {
            $language = new ExpressionLanguage(null, $this->providers);
            $result = (bool)$language->evaluate($options['extras'][self::CONDITION_KEY]);
            if (!$result) {
                $options['extras'][BuilderInterface::IS_ALLOWED_OPTION_KEY] = false;
            }
        }
        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildItem(ItemInterface $item, array $options)
    {

    }
}
