<?php

/*
 * This file is part of aakb/jira_economics.
 *
 * (c) 2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace GraphicServiceOrder\Controller;

use GraphicServiceOrder\Entity\GsOrder;
use GraphicServiceOrder\Form\GraphicServiceOrderForm;
use GraphicServiceOrder\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class GraphicServiceOrderController.
 *
 * @Route("/", name="graphic_service_order_")
 */
class GraphicServiceOrderController extends AbstractController
{
    /**
     * Create a service order.
     *
     * @Route("/create-graphic-service-order", name="form")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function newOrder(Request $request, OrderService $orderService, TokenStorageInterface $tokenStorage)
    {
        $userEmail = $tokenStorage->getToken()->getUser()->getEmail();
        $jiraUser = $orderService->getUser($userEmail);

        if ($jiraUser && false === $jiraUser->active) {
            return $this->redirectToRoute('graphic_service_order_showerror', ['error' => 'blocked']);
        }

        $gsOrder = $orderService->prepareOrder();
        $options = [
            'used_debtors' => $orderService->getUsedDebtors(),
            'all_debtors' => json_encode($orderService->getAllDebtors()),
        ];
        $form = $this->createForm(GraphicServiceOrderForm::class, $gsOrder, $options);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orderService->createOrder($gsOrder, $form);

            // Go to form submitted page.
            return $this->redirectToRoute('graphic_service_order_submitted', ['id' => $gsOrder->getId()]);
        }

        // The initial form build.
        return $this->render('@GraphicServiceOrderBundle/createOrderForm.html.twig', [
            'form' => $form->createView(),
            'user_email' => $userEmail,
            'options' => $options,
        ]);
    }

    /**
     * Receipt page displayed when an order was created.
     *
     * @Route("/create-graphic-service-order/submitted/{id}", name="submitted")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showOrderSubmitted(GsOrder $order)
    {
        return $this->render('@GraphicServiceOrderBundle/showOrderSubmitted.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Receipt page displayed when an order was created.
     *
     * @Route("/jira/delivery_note/{id}", name="delivery_note")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showDeliveryNote(GsOrder $order)
    {
        return $this->render('@GraphicServiceOrderBundle/showDeliveryNote.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Error page displayed if something isn't working.
     *
     * @param string $error
     *
     * @Route("/create-graphic-service-order/error/{error}", name="showerror")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showError($error)
    {
        return $this->render('@GraphicServiceOrderBundle/showError.html.twig', [
            'error' => $error,
        ]);
    }
}
