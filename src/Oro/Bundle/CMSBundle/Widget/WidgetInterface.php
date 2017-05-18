<?php

namespace Oro\Bundle\CMSBundle\Widget;

/**
 * All widget services should be defined as lazy to prevent instantiation when they are not used on page.
 */
interface WidgetInterface
{
    /**
     * @param array $options
     * @return string
     */
    public function render(array $options = []);
}
