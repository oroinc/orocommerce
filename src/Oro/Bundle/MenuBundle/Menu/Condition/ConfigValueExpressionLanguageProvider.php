<?php

namespace Oro\Bundle\MenuBundle\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ConfigValueExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
        return $this->configManager->get($parameter);
    }
}
