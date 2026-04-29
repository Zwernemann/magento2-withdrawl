<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Guest;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Message\ManagerInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\Session as WithdrawalSession;
use Zwernemann\Withdrawal\Provider\GuestOrderProvider;

class Find implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly GuestOrderProvider $guestOrderProvider,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly Config $config,
        private readonly WithdrawalSession $withdrawalSession,
    ) {}

    public function execute()
    {
        $redirect = $this->redirectFactory->create();

        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));

            return $redirect->setPath('/');
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please try again.'));

            return $redirect->setPath('withdrawal/guest/search');
        }

        $incrementId = trim((string) $this->request->getParam('order_increment_id'));
        $email = trim((string) $this->request->getParam('email'));

        if (!$incrementId || !$email) {
            $this->messageManager->addErrorMessage(__('Please enter both order number and email address.'));

            return $redirect->setPath('withdrawal/guest/search');
        }

        $order = $this->guestOrderProvider->getByIncrementIdAndEmail($incrementId, $email);

        if (!$order || !$order->getId()) {
            $this->messageManager->addErrorMessage(__('No order found with the given order number and email address.'));

            return $redirect->setPath('withdrawal/guest/search');
        }

        $this->withdrawalSession->setWithdrawalOrderId((int) $order->getId());

        return $redirect->setPath('withdrawal/index/view');
    }
}
