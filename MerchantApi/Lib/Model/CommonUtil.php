<?php
/**
 * CommonUtil
 *
 * @category Class
 * @package  zip
 * @author    Zip Plugin Team <integration@zip.co>
 */

namespace Zip\ZipPayment\MerchantApi\Lib\Model;

class CommonUtil
{
    // currencies
    const CURRENCY_AUD = 'AUD';
    const CURRENCY_NZD = 'NZD';
    const CURRENCY_GBP = 'GBP';
    const CURRENCY_USD = 'USD';

    // countries
    const AUSTRALIA = 'au';
    const NEW_ZEALAND = 'nz';
    const UNITED_KINGDOM = 'gb';
    const SOUTH_AFRICA = 'za';
    const UNITED_STATE = 'us';
    const MEXICO = 'mx';
    const CZECH_REPUBLIC = 'cz';
    const POLAND = 'pl';
    const UNITED_ARAB_EMIRATES = 'ae';

    // Zip widget will display inside the iframe
    const IFRAME = 'iframe';
    // Zip widget will display inline
    const INLINE = 'inline';

    // environment
    const SANDBOX = 'sandbox';
    const PRODUCTION = 'production';

    public static function isValidCurrency($currency)
    {
        $result = [
            'valid' => true,
            'message' => '',
        ];
        $allowed_values = self::getAllowedCurrencyList();
        if (!in_array($currency, $allowed_values)) {
            $result['valid'] = false;
            $result['message'] = "invalid value for 'currency', "
                . "must be one of '" . implode("','", $allowed_values) . "'.";
        }
        return $result;
    }

    /**
     * Gets allowable currencies of the enum
     * @return string[]
     */
    private static function getAllowedCurrencyList()
    {
        return [
            self::CURRENCY_AUD,
            self::CURRENCY_NZD,
            self::CURRENCY_USD,
            self::CURRENCY_GBP,
        ];
    }

    /**
     * Gets available region list
     * @return array[]
     */
    public static function getAvailableRegionList()
    {
        return [['value' => self::AUSTRALIA, 'label' => __('Australia')],
            ['value' => self::NEW_ZEALAND, 'label' => __('New Zealand')],
            ['value' => self::UNITED_KINGDOM, 'label' => __('United Kingdom')],
            ['value' => self::SOUTH_AFRICA, 'label' => __('South Africa')],
            ['value' => self::UNITED_STATE, 'label' => __('United State')],
            ['value' => self::MEXICO, 'label' => __('Mexico')],
            ['value' => self::CZECH_REPUBLIC, 'label' => __('Czech Republic')],
            ['value' => self::POLAND, 'label' => __('Poland')],
            ['value' => self::UNITED_ARAB_EMIRATES, 'label' => __('United Arab Emirates')],
        ];
    }

    /**
     * @return array[]
     */
    public static function getEnvironmentList()
    {
        return [['value' => self::SANDBOX, 'label' => __('Sandbox')],
            ['value' => self::PRODUCTION, 'label' => __('Production')]];
    }
}
