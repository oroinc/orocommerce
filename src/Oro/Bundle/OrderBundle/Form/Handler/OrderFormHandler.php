<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Create/update form handler for Order entity.
 */
class OrderFormHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param Order $data
     * @param FormInterface $form
     * @param Request $request
     *
     * @return bool
     * @throws \Exception
     */
    #[\Override]
    public function process($data, FormInterface $form, Request $request): bool
    {
        if (!$this->handleBeforeFormDataSet($form, $data)) {
            return false;
        }

        if ($data !== $form->getData()) {
            $form->setData($data);
        }

        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            return $this->handleFormSubmit($form, $data, $request);
        }

        return false;
    }

    private function handleBeforeFormDataSet(FormInterface $form, Order $order): bool
    {
        $event = new FormProcessEvent($form, $order);
        $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_DATA_SET);

        return !$event->isFormProcessInterrupted();
    }

    /**
     * @throws \Exception
     */
    private function handleFormSubmit(FormInterface $form, Order $order, Request $request): bool
    {
        if (!$this->handleBeforeFormSubmit($form, $order)) {
            return false;
        }

        $this->submitPostPutRequest($form, $request);

        if ($form->isValid()) {
            $this->persistOrder($form, $order);

            return true;
        }

        return false;
    }

    private function handleBeforeFormSubmit(FormInterface $form, Order $order): bool
    {
        $event = new FormProcessEvent($form, $order);
        $this->eventDispatcher->dispatch($event, Events::BEFORE_FORM_SUBMIT);

        return !$event->isFormProcessInterrupted();
    }

    /**
     * @throws \Exception
     */
    private function persistOrder(FormInterface $form, Order $order): void
    {
        $entityManager = $this->doctrine->getManagerForClass(Order::class);

        $entityManager->beginTransaction();
        try {
            $entityManager->persist($order);
            $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $order), Events::BEFORE_FLUSH);
            $entityManager->flush();
            $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $order), Events::AFTER_FLUSH);
            $entityManager->commit();
        } catch (\Exception $exception) {
            $entityManager->rollback();

            throw $exception;
        }
    }
}
