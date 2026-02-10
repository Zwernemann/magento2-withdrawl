<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Success implements HttpGetActionInterface
{
    private $request;
    private $pageFactory;
    private $redirectFactory;

    public function __construct(
        RequestInterface $request,
        PageFactory $pageFactory,
        RedirectFactory $redirectFactory
    ) {
        $this->request = $request;
        $this->pageFactory = $pageFactory;
        $this->redirectFactory = $redirectFactory;
    }

    public function execute()
    {
        $orderId = (int) $this->request->getParam('order_id');
        if (!$orderId) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('/');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Withdrawal Submitted Successfully'));
        return $page;
    }
}
