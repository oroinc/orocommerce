<?php

namespace Oro\Bundle\CMSBundle\Api;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

/**
 * Provides functionality to build a rendered version of a WYSIWYG value.
 */
class WYSIWYGValueRenderer implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function render(?string $value, ?string $style): ?string
    {
        $result = null;
        if ($value || $style) {
            $result = $this->getTwig()->render(
                '@OroCMS/Api/Field/render_content.html.twig',
                ['value' => (string)$value, 'style' => (string)$style]
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        /**
         * Inject TWIG service via the service locator because it is optional and not all API requests use it,
         * This solution improves performance of API requests that do not need TWIG.
         */
        return [
            Environment::class
        ];
    }

    private function getTwig(): Environment
    {
        return $this->container->get(Environment::class);
    }
}
