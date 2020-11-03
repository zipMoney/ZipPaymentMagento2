<?php

namespace Zip\ZipPayment\Controller\Standard;

use Magento\Checkout\Model\Type\Onepage;

/**
 * @category  Zipmoney
 * @package   Zipmoney_ZipPayment
 * @author    Zip Plugin Team <integration@zip.co>
 * @copyright 2020 Zip Co Limited
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.zipmoney.com.au/
 */
class Referred extends AbstractStandard
{

    /**
     * Displays the referred view
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $this->_logger->info("In referredAction");

        try {
            $page_object = $this->_pageFactory->create();
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            $this->_messageManager->addError($e, $this->_helper->__('An error occurred during redirecting to referred page'));
        }

        return $page_object;
    }
}
