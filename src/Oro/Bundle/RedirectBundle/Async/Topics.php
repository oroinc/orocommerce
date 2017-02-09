<?php

namespace Oro\Bundle\RedirectBundle\Async;

class Topics
{
    const GENERATE_DIRECT_URL_FOR_ENTITIES = 'oro.redirect.generate_direct_url.entity';
    const JOB_GENERATE_DIRECT_URL_FOR_ENTITIES = 'oro.redirect.job.generate_direct_url.entity';
    const REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE = 'oro.redirect.regenerate_direct_url.entity_type';
    const REMOVE_DIRECT_URL_FOR_ENTITY_TYPE = 'oro.redirect.remove_direct_url.entity_type';
    const SYNC_SLUG_REDIRECTS = 'oro.redirect.generate_slug_redirects';
}
