<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Guest;

use Magento\Framework\View\Element\Template;

class Search extends Template
{
    public function getFormAction(): string
    {
        return $this->getUrl('withdrawal/guest/find');
    }
}
