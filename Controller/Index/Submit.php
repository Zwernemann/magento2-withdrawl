<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\Session as WithdrawalSession;
use Zwernemann\Withdrawal\Service\WithdrawalService;

class Submit implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly CustomerSession $customerSession,
        private readonly Config $config,
        private readonly WithdrawalSession $withdrawalSession,
        private readonly WithdrawalService $withdrawalService,
    ) {}

    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        $fallbackRoute = $this->customerSession->isLoggedIn() ? 'sales/order/history' : 'withdrawal/guest/search';

        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));

            return $redirect->setPath($fallbackRoute);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please try again.'));

            return $redirect->setPath($fallbackRoute);
        }

        $order = $this->withdrawalSession->getWithdrawalOrder();

        if (!$order) {
            $this->messageManager->addErrorMessage(__('Invalid request.'));

            return $redirect->setPath($fallbackRoute);
        }

        try {
            $this->withdrawalService->submit((int) $order->getEntityId());
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $redirect->setPath('withdrawal/index/view');
        }

        $this->withdrawalSession->unsWithdrawalOrderId();
        $this->withdrawalSession->setLastWithdrawnOrderId((int) $order->getEntityId());

        return $redirect->setPath('withdrawal/index/success');
    }
}
