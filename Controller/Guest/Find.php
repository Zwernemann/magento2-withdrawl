<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Guest;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Psr\Log\LoggerInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Service\EmailSender;
use Zwernemann\Withdrawal\Service\GuestTokenManager;

class Find implements HttpPostActionInterface
{
    protected GuestTokenManager $tokenManager;
    protected EmailSender $emailSender;
    protected LoggerInterface $logger;
    protected $request;
    protected $redirectFactory;
    protected $messageManager;
    protected $pageFactory;
    protected $orderCollectionFactory;
    protected $formKeyValidator;
    protected $config;

    public function __construct(
        RequestInterface       $request,
        RedirectFactory        $redirectFactory,
        ManagerInterface       $messageManager,
        PageFactory            $pageFactory,
        OrderCollectionFactory $orderCollectionFactory,
        FormKeyValidator       $formKeyValidator,
        Config                 $config,
        GuestTokenManager      $tokenManager,
        EmailSender            $emailSender,
        LoggerInterface        $logger
    )
    {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->pageFactory = $pageFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->config = $config;
        $this->tokenManager = $tokenManager;
        $this->emailSender = $emailSender;
        $this->logger = $logger;
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please try again.'));
            return $redirect->setPath('withdrawal/guest/search');
        }

        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));
            return $redirect->setPath('/');
        }

        $incrementId = trim((string)$this->request->getParam('order_increment_id'));
        $email = trim((string)$this->request->getParam('email'));

        if (!$incrementId || !$email) {
            $this->messageManager->addErrorMessage(__('Please enter both order number and email address.'));
            return $redirect->setPath('withdrawal/guest/search');
        }

        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('increment_id', $incrementId);
        $collection->addFieldToFilter('customer_email', $email);
        $collection->setPageSize(1);

        $order = $collection->getFirstItem();

        if (!$order || !$order->getId()) {
            $this->messageManager->addErrorMessage(
                __('No order found with the given order number and email address.')
            );
            return $redirect->setPath('withdrawal/guest/search');
        }

        // Generate token and send email
        try {
            $token = $this->tokenManager->generateToken((int)$order->getId(), $email);
            $this->emailSender->sendGuestAccessEmail($order, $email, $token);

            $this->messageManager->addSuccessMessage(
                __('An email with an access link has been sent to %1. Please check your inbox.', $email)
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to send guest withdrawal access email', [
                'order_id' => $order->getId(),
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            $this->messageManager->addErrorMessage(
                __('We could not send the access email. Please try again later.')
            );
        }

        return $redirect->setPath('withdrawal/guest/search');
    }
}
