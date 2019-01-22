<?php

namespace Oro\Bundle\RedirectBundle\Routing;

/**
 * This class is used to substitute MatchedUrlDecisionMaker during installation of the application.
 * It is supposed that all URLs are management console URLs until the installation is finished.
 * @see \Oro\Bundle\RedirectBundle\DependencyInjection\OroRedirectExtension::configureMatchedUrlDecisionMaker
 */
class NotInstalledMatchedUrlDecisionMaker extends MatchedUrlDecisionMaker
{
    /**
     * {@inheritdoc}
     */
    public function matches($url)
    {
        return false;
    }
}
