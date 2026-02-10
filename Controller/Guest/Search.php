<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Guest;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Zwernemann\Withdrawal\Helper\Config;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;

class Search implements HttpGetActionInterface
{
    private $pageFactory;
    private $config;
    private $redirectFactory;
    private $messageManager;

    public function __construct(
        PageFactory $pageFactory,
        Config $config,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager
    ) {
        $this->pageFactory = $pageFactory;
        $this->config = $config;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
    }

    public function execute()
    {
        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('/');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Search Order for Withdrawal'));
        return $page;
    }
}
