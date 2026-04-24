<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Guest;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Zwernemann\Withdrawal\Service\GuestTokenManager;

class Verify implements HttpGetActionInterface
{
    protected RequestInterface $request;
    protected RedirectFactory $redirectFactory;
    protected ManagerInterface $messageManager;
    protected GuestTokenManager $tokenManager;
    protected CustomerSession $customerSession;
    protected LoggerInterface $logger;

    public function __construct(
        RequestInterface  $request,
        RedirectFactory   $redirectFactory,
        ManagerInterface  $messageManager,
        GuestTokenManager $tokenManager,
        CustomerSession   $customerSession,
        LoggerInterface   $logger
    )
    {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->tokenManager = $tokenManager;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        $token = (string)$this->request->getParam('token');

        if (!$token) {
            $this->messageManager->addErrorMessage(__('Invalid access link.'));
            return $redirect->setPath('withdrawal/guest/search');
        }

        $tokenData = $this->tokenManager->validateToken($token);

        if (!$tokenData) {
            $this->messageManager->addErrorMessage(
                __('The access link is invalid or has already been used.')
            );
            return $redirect->setPath('withdrawal/guest/search');
        }

        // Store token in session for verification
        $this->customerSession->setGuestWithdrawalToken($token);
        $this->customerSession->setGuestWithdrawalOrderId($tokenData['order_id']);
        $this->customerSession->setGuestWithdrawalEmail($tokenData['email']);

        $this->logger->info('Guest withdrawal access granted', [
            'order_id' => $tokenData['order_id'],
            'email' => $tokenData['email']
        ]);

        // Redirect to guest withdrawal view
        return $redirect->setPath('withdrawal/guest/view', [
            'order_id' => $tokenData['order_id']
        ]);
    }
}
