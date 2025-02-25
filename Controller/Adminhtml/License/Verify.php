<?php

namespace Scommerce\Core\Controller\Adminhtml\License;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Scommerce\Core\Model\SendVerify;

class Verify extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var SendVerify
     */
    protected $sendVerify;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        SendVerify $sendVerify
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->sendVerify = $sendVerify;
    }

    /**
     * Execute the AJAX request
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('websiteId');
        $moduleName = $this->getRequest()->getParam('moduleName');

        if (!is_null($websiteId) && $moduleName) {
            $response = $this->sendVerify->sendVerify($moduleName, $websiteId);
        } else {
            $response = ['success' => false, 'message' => 'Data is incorrect'];
        }

        return $this->jsonFactory->create()->setData($response);
    }
}
