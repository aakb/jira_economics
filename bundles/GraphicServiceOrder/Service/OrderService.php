<?php

/*
 * This file is part of aakb/jira_economics.
 *
 * (c) 2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace GraphicServiceOrder\Service;

use App\Service\HammerService;
use App\Service\OwnCloudService;
use Doctrine\ORM\EntityManagerInterface;
use GraphicServiceOrder\Entity\Debtor;
use GraphicServiceOrder\Entity\GsOrder;
use GraphicServiceOrder\Message\OwnCloudShareMessage;
use GraphicServiceOrder\Repository\GsOrderRepository;
use ItkDev\UserManagementBundle\Doctrine\UserManager;
use Swift_Mailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class OrderService
{
    /* @var \App\Service\HammerService */
    private $hammerService;
    /* @var \App\Service\OwnCloudService */
    private $ownCloudService;
    /* @var \GraphicServiceOrder\Repository\GsOrderRepository */
    private $gsOrderRepository;
    /* @var \Symfony\Component\HttpKernel\KernelInterface */
    private $appKernel;
    /* @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;
    /* @var \Symfony\Component\Messenger\MessageBusInterface */
    private $messageBus;
    /* @var string */
    private $ownCloudFilesFolder;
    /* @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface */
    private $tokenStorage;
    /* @var \GraphicServiceOrder\Service\FileUploader */
    private $fileUploader;
    /** @var \ItkDev\UserManagementBundle\Doctrine\UserManager */
    private $userManager;
    /* @var \Swift_Mailer */
    private $swiftMailer;
    /* @var \Twig\Environment */
    private $twig;
    /* @var \Symfony\Contracts\Translation\TranslatorInterface */
    private $translator;
    /* @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface */
    private $params;
    /* @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface  */
    private $router;

    /**
     * OrderService constructor.
     *
     * @param \GraphicServiceOrder\Service\FileUploader $fileUploader
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        HammerService $hammerService,
        OwnCloudService $ownCloudService,
        GsOrderRepository $gsOrderRepository,
        KernelInterface $appKernel,
        MessageBusInterface $messageBus,
        string $ownCloudFilesFolder,
        TokenStorageInterface $tokenStorage,
        FileUploader $fileUploader,
        UserManager $userManager,
        Swift_Mailer $swiftMailer,
        Environment $twig,
        TranslatorInterface $translator,
        array $gsOrderConfiguration,
        UrlGeneratorInterface $router
    ) {
        $this->entityManager = $entityManager;
        $this->hammerService = $hammerService;
        $this->ownCloudService = $ownCloudService;
        $this->gsOrderRepository = $gsOrderRepository;
        $this->appKernel = $appKernel;
        $this->messageBus = $messageBus;
        $this->ownCloudFilesFolder = $ownCloudFilesFolder;
        $this->tokenStorage = $tokenStorage;
        $this->fileUploader = $fileUploader;
        $this->userManager = $userManager;
        $this->swiftMailer = $swiftMailer;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->params = new ParameterBag($gsOrderConfiguration);
        $this->router = $router;
    }

    /**
     * Preset some values from user entity.
     *
     * @return \GraphicServiceOrder\Entity\GsOrder
     */
    public function prepareOrder()
    {
        $gsOrder = new GsOrder();
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            $dontUseDefaults = $user->getNoDefaultSettings();
            if (false === $dontUseDefaults) {
                $gsOrder
                    ->setFullName($user->getFullName())
                    ->setPhone($user->getPhone())
                    ->setAddress($user->getAddress())
                    ->setDepartment($user->getDepartment())
                    ->setPostalcode($user->getPostalCode())
                    ->setCity($user->getCity());
            }
        }

        return $gsOrder;
    }

    /**
     * Get used debtors for GS form.
     *
     * @return array
     */
    public function getUsedDebtors($asString = true)
    {
        $usedDebtors = [];
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            $debtors = $user->getUsedDebtors();
            foreach ($debtors as $debtor) {
                if ($asString) {
                    $usedDebtors[] = (string) $debtor;
                } else {
                    $usedDebtors[] = $debtor->getId();
                }
            }
        }

        return $usedDebtors;
    }

    /**
     * Get all debtors from DB.
     *
     * @return array
     */
    public function getAllDebtors()
    {
        $debtors = [];
        $debtorEntities = $this->entityManager->getRepository(Debtor::class)
            ->findAll();
        foreach ($debtorEntities as $debtorEntity) {
            $debtors[$debtorEntity->getNumber()] = $debtorEntity->getLabel();
        }

        return $debtors;
    }

    /**
     * Get all libraries defined in jira_economics.local.yaml.
     *
     * @return mixed
     */
    public function getLibraries()
    {
        $libraries = $this->params->get('gs_libraries');

        return $libraries;
    }

    /**
     * Update active user with submitted values.
     *
     * @param $gsOrder
     */
    private function updateUserWithGSOrder(GsOrder $gsOrder)
    {
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            /** @var \App\Entity\User $user */
            $user = $token->getUser();
            $user
                ->setFullName($gsOrder->getFullName())
                ->setDepartment($gsOrder->getDepartment())
                ->setPhone($gsOrder->getPhone())
                ->setAddress($gsOrder->getAddress())
                ->setPostalCode($gsOrder->getPostalcode())
                ->setCity($gsOrder->getCity());

            /** @var \GraphicServiceOrder\Entity\Debtor $debtorOrder */
            $debtorOrder = $this
                ->entityManager->getRepository(Debtor::class)
                ->findOneBy(['number' => $gsOrder->getDebitor()]);
            if (isset($debtorOrder)) {
                $user->addUsedDebtor($debtorOrder);
            }

            $this->userManager->updateUser($user);
        }
    }

    /**
     * @param $form
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrder(GsOrder $gsOrder, $form)
    {
        // We don't allow null values for id and key, so we set a temporary
        // value here, only to change it immediately after from created jira issue.
        $gsOrder->setIssueId(0);
        $gsOrder->setIssueKey(0);
        $this->entityManager->persist($gsOrder);
        $this->entityManager->flush();
        // Create a task on a jira project.
        $taskCreated = $this->createOrderTask($gsOrder);
        if ($taskCreated) {
            // Add task values to order entity.
            $gsOrder->setIssueId($taskCreated->id);
            $gsOrder->setIssueKey($taskCreated->key);

            // Create a folder with issue key as name.
            $this->createFolder($taskCreated->key);

            // Store file locally.
            $gsOrder = $this->storeFile($gsOrder, $form);
            $gsOrder->setOrderStatus('new');

            $this->entityManager->persist($gsOrder);
            $this->entityManager->flush();

            // Notify messenger of new job.
            $this->messageBus->dispatch(new OwnCloudShareMessage($gsOrder->getId()));

            $this->sendReceiptMail($gsOrder);
            $this->updateUserWithGSOrder($gsOrder);
        }
    }

    /**
     * Get user from Jira by email.
     *
     * @param string $email
     *
     * @return mixed
     */
    public function getUser(string $email)
    {
        return $this->hammerService->getUser($email);
    }

    /**
     * Handle file transfer to OwnCloud.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handleOrderMessage(GsOrder $order)
    {
        $files = $order->getFiles();

        // If no files on order, consider all files received.
        if (empty($files)) {
            $order->setOrderStatus('received');
        } else {
            // Get all files on the order that have already been shared.
            $sharedFiles = $order->getOwnCloudSharedFiles();
            foreach ($files as $file) {
                // if a file exists on the entity that has not yet been shared.
                if (!\in_array($file, $sharedFiles)) {
                    // Attempt to share the file in owncloud.
                    $response = $this->shareFile($file, $order->getIssueKey());
                    $success = [201, 204];  // Successful responses;
                    // if file was shared successfully add to shared files array.
                    if (\in_array($response, $success)) {
                        $sharedFiles[] = $file;
                        $order->setOwnCloudSharedFiles($sharedFiles);
                    }
                }
            }
        }

        // If all files are considered shared change status to "received".
        $diff = array_diff($files, $order->getOwnCloudSharedFiles());
        if (empty($diff)) {
            $order->setOrderStatus('received');
            // Remove local files.
            foreach ($order->getOwnCloudSharedFiles() as $file) {
                unlink($this->params->get('gs_files_directory').'/'.$file);
            }
        }

        // Update entity.
        $this->entityManager->flush();
    }

    /**
     * Create a Jira task from a form submission.
     *
     * @return mixed
     */
    private function createOrderTask(GsOrder $gsOrder)
    {
        // Define author of task.
        $authorEmail = $this->tokenStorage->getToken()->getUser()->getEmail();
        $userSearch = $this->hammerService->searchUser($authorEmail);
        if (!empty($userSearch)) {
            // We fairly assume only one existing user matches the email.
            $author = $userSearch->name;
        } else {
            // If no match we create a new user.
            $userFields = [
                'name' => $authorEmail,
                'emailAddress' => $authorEmail,
                'displayName' => $authorEmail,
            ];

            $this->hammerService->createUser($userFields);
            $author = $userFields['name'];
        }

        $description = $this->getDescription($gsOrder);
        $data = [
            'fields' => [
                'project' => [
                    'id' => $this->params->get('gs_order_project_id'),
                ],
                'summary' => $gsOrder->getJobTitle(),
                'description' => $description,
                'issuetype' => [
                    'id' => $this->params->get('gs_order_issuetype_id'),
                ],
                'reporter' => [
                    'name' => $author,
                ],
                $this->hammerService->getCustomFieldId('Full name') => $gsOrder->getFullName(),
                $this->hammerService->getCustomFieldId('Debitor') => (string) $gsOrder->getDebitor(),
                $this->hammerService->getCustomFieldId('Marketing Account') => $gsOrder->getMarketingAccount() ? [0 => ['value' => 'Markedsføringskonto']] : null,
                $this->hammerService->getCustomFieldId('Delivery Note URL') => $this->router->generate('graphic_service_order_delivery_note', ['id' => $gsOrder->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                $this->hammerService->getCustomFieldId('Debitor') => (string) $gsOrder->getDebitor(),
                $this->hammerService->getCustomFieldId('Phone number') => (string) $gsOrder->getPhone(),
                $this->hammerService->getCustomFieldId('Department') => (string) $gsOrder->getDepartment(),
                $this->hammerService->getCustomFieldId('Delivery date') => $gsOrder->getDate()->format('Y-m-d'),
                $this->hammerService->getCustomFieldId('Order lines') => $this->getOrderLinesAsText($gsOrder),
                $this->hammerService->getCustomFieldId('Library') => $gsOrder->getLibrary(),
            ],
        ];

        $response = $this->hammerService->post('/rest/api/2/issue', $data);

        return $response;
    }

    /**
     * Create folder if none exists.
     *
     * @param $order_key
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function createFolder($order_key)
    {
        // @TODO: Fix path parameters.
        // Make sure folder does not already exist.
        $existing_folders = $this->ownCloudService->propFind('/owncloud/remote.php/dav/files/'.$_ENV['OWNCLOUD_USER_SHARED_DIR']);
        if (!\in_array($order_key.'/', $existing_folders)) {
            // Create folders
            $this->ownCloudService->mkCol('owncloud/remote.php/dav/files/'.$_ENV['OWNCLOUD_USER_SHARED_DIR'].$order_key);
            $this->ownCloudService->mkCol('owncloud/remote.php/dav/files/'.$_ENV['OWNCLOUD_USER_SHARED_DIR'].$order_key.'/_Materiale');
        }
    }

    /**
     * Share file in ownCloud.
     *
     * @param $fileName
     * @param $order_id
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function shareFile($fileName, $order_id)
    {
        // @TODO: Fix path parameters.
        $ownCloudPath = $_ENV['OWNCLOUD_USER_SHARED_DIR'].$order_id.'/_Materiale/';
        $file = file_get_contents($this->params->get('gs_files_directory').'/'.$fileName);
        $response = $this->ownCloudService->sendFile(
            'owncloud/remote.php/dav/files/'.$ownCloudPath.$fileName,
            $file
        );

        return $response;
    }

    /**
     * Crete orderlines as text.
     *
     * @return string
     */
    private function getOrderLinesAsText(GsOrder $orderData)
    {
        $orderLines = '';
        foreach ($orderData->getOrderLines() as $order) {
            $orderLines .= $order['amount'].' '.$order['type'].'\\\\ ';
        }

        return $orderLines;
    }

    /**
     * Create description for task.
     *
     * @param $orderData
     *
     * @return string
     */
    private function getDescription(GsOrder $orderData)
    {
        // Create task description.
        $description = '*Opgavebeskrivelse* \\\\ ';
        $description .= $orderData->getDescription().'\\\\ ';
        $description .= ' \\\\ ';
        $description .= '[Åbn filer i OwnCloud|'.$this->ownCloudFilesFolder.'] \\\\ ';

        $description .= ' \\\\ ';

        // Create delivery description.
        $description .= '*Hvor skal ordren leveres?* \\\\ ';
        $description .= $orderData->getDepartment().' \\\\ ';
        $description .= $orderData->getAddress().'\\\\ ';
        $description .= $orderData->getPostalcode().' '.$orderData->getCity().'\\\\ ';
        $description .= ' \\\\ ';
        $description .= $orderData->getDeliveryDescription();

        return $description;
    }

    /**
     * Store files locally.
     *
     * @param $gsOrder
     * @param $form
     *
     * @return mixed
     */
    private function storeFile(GsOrder $gsOrder, $form)
    {
        $uploadedFiles = [];
        $upload_files = $form['multi_upload']->getData();
        if ($upload_files) {
            foreach ($upload_files as $file) {
                if (isset($file)) {
                    $uploadedFileName = $this->fileUploader->upload($file, $gsOrder);
                    $uploadedFiles[] = $uploadedFileName;
                }
            }
        }
        $gsOrder->setFiles($uploadedFiles);

        return $gsOrder;
    }

    /**
     * Send receipt mail.
     *
     * @param $gsOrder
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function sendReceiptMail(GsOrder $gsOrder)
    {
        $message = (new \Swift_Message($this->translator->trans('service_order_email.subject')))
            ->setFrom($_ENV['MAILER_EMAIL'])
            ->setTo($this->tokenStorage->getToken()->getUser()->getEmail())
            ->setBody(
                $this->twig->render(
                    '@GraphicServiceOrderBundle/customerReceiptMail.twig',
                    ['order' => $gsOrder]
                ),
                'text/html'
            );

        $this->swiftMailer->send($message);
    }
}
