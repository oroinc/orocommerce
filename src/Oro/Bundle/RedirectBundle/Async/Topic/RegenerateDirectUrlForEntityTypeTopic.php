<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to regenerate Slug URLs for the entities of specified type.
 */
class RegenerateDirectUrlForEntityTypeTopic extends AbstractTopic
{
    public const NAME = 'oro.redirect.regenerate_direct_url.entity_type';

    private DirectUrlTopicHelper $directUrlCommonTopicHelper;

    public function __construct(DirectUrlTopicHelper $directUrlCommonTopicHelper)
    {
        $this->directUrlCommonTopicHelper = $directUrlCommonTopicHelper;
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Regenerate Slug URLs for the entities of specified type.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->directUrlCommonTopicHelper->configureIdOption($resolver);
        $this->directUrlCommonTopicHelper->configureEntityClassOption($resolver);
        $this->directUrlCommonTopicHelper->configureRedirectOption($resolver);
    }
}
