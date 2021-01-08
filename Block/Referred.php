<?php

namespace Zip\ZipPayment\Block;

use Magento\Framework\View\Element\Template;

/**
 * @author    Zip Plugin Team <integration@zip.co>
 * @copyright 2020 Zip Co Limited
 * @link      https://zip.co
 */
class Referred extends Template
{
    /**
     * @const string
     */
    const REFERRED_HEADER = 'payment/zippayment/zip_messages/referred_header';
    /**
     * @const string
     */
    const REFERRED_BODY = 'payment/zippayment/zip_messages/referred_body';

    protected $_messageManager;
    protected $_config;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Zip\ZipPayment\Model\Config $config,
        array $data = []
    ) {
        $this->_messageManager = $messageManager;
        $this->_config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Referred Body Text
     *
     * @return string
     */
    public function getBodyText()
    {
        $text = $this->_config->getStoreConfig(self::REFERRED_BODY);
        if (!$text) {
            $text = __(
                'Your application is currently under review by zipMoney and will be processed very shortly. '
                . 'You can contact the customer care at customercare@zipmoney.com.au for any enquiries'
            );
        }
        return $text;
    }

    /**
     * Prepares the layout.
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        $text = $this->_config->getStoreConfig(self::REFERRED_HEADER);

        if (!$text) {
            $text = "Your application has been referred";
        }

        $this->pageConfig->getTitle()->set(__($text));

        return parent::_prepareLayout();
    }
}
