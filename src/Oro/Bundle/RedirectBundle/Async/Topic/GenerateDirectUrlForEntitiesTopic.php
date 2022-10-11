<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to generate Slug URLs for the specified entities.
 */
class GenerateDirectUrlForEntitiesTopic extends AbstractTopic
{
    private DirectUrlTopicHelper $directUrlCommonTopicHelper;

    public function __construct(DirectUrlTopicHelper $directUrlCommonTopicHelper)
    {
        $this->directUrlCommonTopicHelper = $directUrlCommonTopicHelper;
    }

    public static function getName(): string
    {
        return 'oro.redirect.generate_direct_url.entity';
    }

    public static function getDescription(): string
    {
        return 'Generate Slug URLs for the specified entities.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->directUrlCommonTopicHelper->configureIdOption($resolver);
        $this->directUrlCommonTopicHelper->configureEntityClassOption($resolver);
        $this->directUrlCommonTopicHelper->configureRedirectOption($resolver);
    }
}
