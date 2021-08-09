<?php

namespace Moloni;

/**
 * Multiple tools for handling recurring tasks
 * Class Tools
 * @package Moloni
 */
class Tools
{
    /**
     * Array with all european country codes
     *
     * @var string[]
     */
    public static $europeanCountryCodes = [
        'AT' => 'Austria',
        'BE'=> 'Belgium',
        'BG'=> 'Bulgaria',
        'HR'=> 'Croatia',
        'CY'=> 'Cyprus',
        'CZ'=> 'Czech Republic',
        'DK'=> 'Denmark',
        'EE'=> 'Estonia',
        'FI'=> 'Finland',
        'FR'=> 'France',
        'DE'=> 'Germany',
        'GR'=> 'Greece',
        'HU'=> 'Hungary',
        'IE'=> 'Ireland',
        'IT'=> 'Italy',
        'LV'=> 'Latvia',
        'LT'=> 'Lithuania',
        'LU'=> 'Luxembourg',
        'MT'=> 'Malta',
        'NL'=> 'Netherlands',
        'PL'=> 'Poland',
        'PT'=> 'Portugal',
        'RO'=> 'Romania',
        'SK'=> 'Slovakia',
        'SI'=> 'Slovenia',
        'ES'=> 'Spain',
        'SE'=> 'Sweden',
    ];

    /**
     * @param string $string
     * @param int $productId
     * @param int $variationId
     * @return string
     */
    public static function createReferenceFromString($string, $productId = 0, $variationId = 0)
    {
        $reference = '';

        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        $name = explode(' ', $string);

        if (!defined('USE_NAME_FOR_MOLONI_REFERENCE') || USE_NAME_FOR_MOLONI_REFERENCE == 1) {
            if ((int)$productId > 0) {
                $reference .= '_' . $productId;
            }

            if ((int)$variationId > 0) {
                $reference .= '_' . $variationId;
            }

            foreach ($name as $word) {
                $reference .= '_' . mb_substr($word, 0, 3);
            }
        } else {
            if ((int)$productId > 0) {
                $reference .= $productId;
            }

            if ((int)$variationId > 0) {
                $reference .= '_' . $variationId;
            }
        }

        return mb_substr($reference, 0, 30);
    }

    /**
     * Get full tax Object given a tax rate
     *
     * @param float $taxRate Tax rate value
     * @param string $countryCode Country code string
     *
     * @return array
     *
     * @throws Error
     */
    public static function getTaxFromRate($taxRate, $countryCode = 'PT')
    {
        $taxesList = Curl::simple('taxes/getAll', []);
        $moloniTax = false;
        $defaultTax = 0;

        if (!empty($taxesList) && is_array($taxesList)) {
            foreach ($taxesList as $tax) {
                if ((int)$tax['active_by_default'] === 1) {
                    $defaultTax = $tax;
                }

                if ($tax['fiscal_zone'] === $countryCode && (float)$tax['value'] === (float)$taxRate) {
                    $moloniTax = $tax;
                    break;
                }
            }
        }

        if (!$moloniTax) {
            $newTax = self::createTax($taxRate, $countryCode);

            if (isset($newTax['tax_id'])) {
                $moloniTax = $newTax;

                //The value here will always be this, we save a request to get the inserted tax
                $moloniTax['saft_type'] = "1";
            } else {
                //Fallback tax
                $moloniTax = $defaultTax;
            }
        }

        return $moloniTax;
    }

    /**
     * Creates a tax in Moloni
     *
     * @param float $taxRate Tax rate value
     * @param string $countryCode Country code string
     *
     * @return array|bool
     *
     * @throws Error
     */
    public static function createTax($taxRate, $countryCode = 'PT')
    {
        $values = [];

        $values['name'] = 'VAT ' . $countryCode;
        $values['value'] = $taxRate;
        $values['type'] = "1";
        $values['saft_type'] = "1";
        $values['vat_type'] = "OUT";
        $values['stamp_tax'] = "0";
        $values['exemption_reason'] = EXEMPTION_REASON;
        $values['fiscal_zone'] = $countryCode;
        $values['active_by_default'] = "0";

        return Curl::simple('taxes/insert', $values);
    }

    /**
     * @param $countryCode
     * @return string
     * @throws Error
     */
    public static function getCountryIdFromCode($countryCode)
    {
        $countriesList = Curl::simple('countries/getAll', []);
        if (!empty($countriesList) && is_array($countriesList)) {
            foreach ($countriesList as $country) {
                if (strtoupper($country['iso_3166_1']) === strtoupper($countryCode)) {
                    return $country['country_id'];
                    break;
                }
            }
        }

        return '1';
    }

    /**
     * @param int $from
     * @param int $to
     * @return float
     * @throws Error
     */
    public static function getCurrencyExchangeRate($from, $to)
    {
        $currenciesList = Curl::simple('currencyExchange/getAll', []);
        if (!empty($currenciesList) && is_array($currenciesList)) {
            foreach ($currenciesList as $currency) {
                if ((int)$currency['from'] === $from && (int)$currency['to'] === $to) {
                    return (float)$currency['value'];
                }
            }
        }

        return 1;
    }

    /**
     * @param string $currencyCode
     * @return int
     * @throws Error
     */
    public static function getCurrencyIdFromCode($currencyCode)
    {
        $currenciesList = Curl::simple('currencies/getAll', []);
        if (!empty($currenciesList) && is_array($currenciesList)) {
            foreach ($currenciesList as $currency) {
                if ($currency['iso4217'] === mb_strtoupper($currencyCode)) {
                    return $currency['currency_id'];
                }
            }
        }

        return 1;
    }

    /**
     * @param $input
     * @return string
     */
    public static function zipCheck($input)
    {
        $zipCode = trim(str_replace(' ', '', $input));
        $zipCode = preg_replace('/[^0-9]/', '', $zipCode);
        if (strlen($zipCode) == 7) {
            $zipCode = $zipCode[0] . $zipCode[1] . $zipCode[2] . $zipCode[3] . '-' . $zipCode[4] . $zipCode[5] . $zipCode[6];
        }
        if (strlen($zipCode) == 6) {
            $zipCode = $zipCode[0] . $zipCode[1] . $zipCode[2] . $zipCode[3] . '-' . $zipCode[4] . $zipCode[5] . '0';
        }
        if (strlen($zipCode) == 5) {
            $zipCode = $zipCode[0] . $zipCode[1] . $zipCode[2] . $zipCode[3] . '-' . $zipCode[4] . '00';
        }
        if (strlen($zipCode) == 4) {
            $zipCode = $zipCode . '-' . '000';
        }
        if (strlen($zipCode) == 3) {
            $zipCode = $zipCode . '0-' . '000';
        }
        if (strlen($zipCode) == 2) {
            $zipCode = $zipCode . '00-' . '000';
        }
        if (strlen($zipCode) == 1) {
            $zipCode = $zipCode . '000-' . '000';
        }
        if (strlen($zipCode) == 0) {
            $zipCode = '1000-100';
        }
        if (self::finalCheck($zipCode)) {
            return $zipCode;
        }

        return '1000-100';
    }

    /**
     * Validate a Zip Code format
     * @param string $zipCode
     * @return bool
     */
    private static function finalCheck($zipCode)
    {
        $regexp = "/[0-9]{4}\-[0-9]{3}/";
        if (preg_match($regexp, $zipCode)) {
            return (true);
        }

        return (false);
    }

}
