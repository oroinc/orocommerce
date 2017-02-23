<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Oro\Bundle\RedirectBundle\Model\TextSlugPrototypeWithRedirect;

trait TextSlugPrototypeWithRedirectAwareTrait
{
    use TextSlugPrototypeAwareTrait;

    /**
     * @var TextSlugPrototypeWithRedirect
     */
    protected $textSlugPrototypeWithRedirect;

    /**
     * @return TextSlugPrototypeWithRedirect
     */
    public function getTextSlugPrototypeWithRedirect()
    {
        if (!$this->textSlugPrototypeWithRedirect) {
            $this->textSlugPrototypeWithRedirect = new TextSlugPrototypeWithRedirect($this->textSlugPrototype);
        }

        return $this->textSlugPrototypeWithRedirect;
    }

    /**
     * @param TextSlugPrototypeWithRedirect $textSlugPrototypeWithRedirect
     *
     * @return $this
     */
    public function setTextSlugPrototypeWithRedirect(TextSlugPrototypeWithRedirect $textSlugPrototypeWithRedirect)
    {
        $this->textSlugPrototypeWithRedirect = $textSlugPrototypeWithRedirect;

        return $this;
    }
}
