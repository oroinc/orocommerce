<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Oro\Bundle\RedirectBundle\Model\TextSlugPrototypeWithRedirect;

interface TextSlugPrototypeWithRedirectAwareInterface extends TextSlugPrototypeAwareInterface
{
    /**
     * @return TextSlugPrototypeWithRedirect
     */
    public function getTextSlugPrototypeWithRedirect();

    /**
     * @param TextSlugPrototypeWithRedirect $textSlugPrototypeWithRedirect
     *
     * @return $this
     */
    public function setTextSlugPrototypeWithRedirect(TextSlugPrototypeWithRedirect $textSlugPrototypeWithRedirect);
}
