<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Guest;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Zwernemann\Withdrawal\Helper\Config;

class Find implements HttpPostActionInterface
{
    private $request;
    private $redirectFactory;
    private $messageManager;
    private $pageFactory;
    private $orderCollectionFactory;
    private $formKeyValidator;
    private $config;

    public function __construct(
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        PageFactory $pageFactory,
        OrderCollectionFactory $orderCollectionFactory,
        FormKeyValidator $formKeyValidator,
        Config $config
    ) {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->pageFactory = $pageFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->config = $config;
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

        $incrementId = trim((string) $this->request->getParam('order_increment_id'));
        $email = trim((string) $this->request->getParam('email'));

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

        // Redirect to guest view page
        return $redirect->setPath('withdrawal/guest/view', [
            'order_id' => $order->getId(),
            'email' => $email,
        ]);
    }
}
