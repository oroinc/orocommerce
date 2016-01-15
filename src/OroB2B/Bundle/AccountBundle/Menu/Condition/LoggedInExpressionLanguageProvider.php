<?php

namespace OroB2B\Bundle\AccountBundle\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

class LoggedInExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var AccountUserProvider
     */
    protected $accountUserProvider;

    /**
     * @param AccountUserProvider $accountUserProvider
     */
    public function __construct(AccountUserProvider $accountUserProvider)
    {
        $this->accountUserProvider = $accountUserProvider;
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
        return $this->accountUserProvider->getLoggedUser() !== null;
    }
}
