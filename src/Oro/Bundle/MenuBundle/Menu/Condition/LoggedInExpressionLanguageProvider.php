<?php

namespace Oro\Bundle\MenuBundle\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

use Oro\Component\DependencyInjection\ServiceLink;

class LoggedInExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var ServiceLink
     */
    protected $securityFacadeLink;

    /**
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(ServiceLink $securityFacadeLink)
    {
        $this->securityFacadeLink = $securityFacadeLink;
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
        return $this->securityFacadeLink->getService()->getLoggedUser() !== null;
    }
}
