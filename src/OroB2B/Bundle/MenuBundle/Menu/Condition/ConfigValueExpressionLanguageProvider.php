<?php

namespace OroB2B\Bundle\MenuBundle\Menu\Condition;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ConfigValueExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new ExpressionFunction('config_value', function ($parameter) {
                return sprintf('config_value(%s)', $parameter);
            }, [$this, 'getConfigValue']),
        ];
    }

    /**
     * @param array $variables
     * @param string $parameter
     * @return string|null
     */
    public function getConfigValue(array $variables, $parameter)
    {
        return $this->container->getParameter($parameter);
    }
}
