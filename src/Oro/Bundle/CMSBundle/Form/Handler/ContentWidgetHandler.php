<?php

namespace Oro\Bundle\CMSBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The handler for the content widget form.
 */
class ContentWidgetHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    /** @var string */
    public const UPDATE_MARKER = 'formUpdateMarker';

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process($data, FormInterface $form, Request $request): bool
    {
        if (!$data instanceof ContentWidget) {
            throw new \InvalidArgumentException('Argument data should be instance of ContentWidget entity');
        }

        $form->setData($data);

        if (\in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT], true)) {
            $updateMarker = $request->get(self::UPDATE_MARKER, false);

            $this->submitPostPutRequest($form, $request, !$updateMarker);

            if (!$updateMarker && $form->isValid()) {
                $this->onSuccess($data);
                return true;
            }
        }

        return false;
    }

    /**
     * @param ContentWidget $contentWidget
     */
    protected function onSuccess(ContentWidget $contentWidget): void
    {
        $manager = $this->registry->getManagerForClass(ContentWidget::class);
        $manager->persist($contentWidget);
        $manager->flush();
    }
}
