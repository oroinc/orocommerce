<?php

namespace Oro\Bundle\MenuBundle\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class LoggedInExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new ExpressionFunction('is_logged_in', function () {
                return 'is_logged_in()';
            }, [$this, 'isLoggedIn'])
        ];
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->securityFacade->getLoggedUser() !== null;
    }
}
