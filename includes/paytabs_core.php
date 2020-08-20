<?php

/**
 * PayTabs PHP SDK
 * Version: 1.2.2
 */


class PaytabsHelper
{
    static function paymentType($key)
    {
        return PaytabsApi::PAYMENT_TYPES[$key]['name'];
    }

    static function paymentAllowed($code, $currencyCode)
    {
        $row = null;
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                $row = $value;
                break;
            }
        }
        if (!$row) {
            return false;
        }
        $list = $row['currencies'];
        if ($list == null) {
            return true;
        }

        $currencyCode = strtoupper($currencyCode);

        return in_array($currencyCode, $list);
    }

    static function isPayTabsPayment($code)
    {
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return the first non-empty var from the vars list
     */
    public static function getNonEmpty(...$vars)
    {
        foreach ($vars as $var) {
            if (!empty($var)) return $var;
        }
        return false;
    }

    /**
     * convert non-english digits to English
     * used for fileds that accepts only English digits like: "postal_code"
     */
    public static function convertAr2En($string)
    {
        $nonEnglish = [
            // Arabic
            [
                '٠',
                '١',
                '٢',
                '٣',
                '٤',
                '٥',
                '٦',
                '٧',
                '٨',
                '٩'
            ],
            // Persian
            [
                '۰',
                '۱',
                '۲',
                '۳',
                '۴',
                '۵',
                '۶',
                '۷',
                '۸',
                '۹'
            ]
        ];

        $num = range(0, 9);

        $englishNumbersOnly = $string;
        foreach ($nonEnglish as $oldNum) {
            $englishNumbersOnly = str_replace($oldNum, $num, $englishNumbersOnly);
        }

        return $englishNumbersOnly;
    }

    /**
     * check Strings that require to be a valid Word, not [. (dot) or digits ...]
     * if the parameter is not a valid "Word", convert it to "NA"
     */
    public static function pt_fillIfEmpty(&$string)
    {
        if (empty(preg_replace('/[\W]/', '', $string))) {
            $string .= 'NA';
        }
    }

    public static function log($msg, $severity = 1)
    {
        try {
            paytabs_error_log($msg, $severity);
        } catch (\Throwable $th) {
            try {
                $_prefix = date('c') . ' PayTabs: ';
                $_msg = ($_prefix . $msg . PHP_EOL);
                file_put_contents('debug_paytabs.log', $_msg, FILE_APPEND);
            } catch (\Throwable $th) {
                // var_export($th);
            }
        }
    }

    static function getTokenInfo($return_values)
    {
        $fields = [
            'pt_token',
            'pt_customer_email',
            'pt_customer_password'
        ];

        $tokenInfo = [];

        foreach ($fields as $field) {
            if (!isset($return_values[$field])) return false;
            $tokenInfo[$field] = $return_values[$field];
        }

        return $tokenInfo;
    }

    public static function getCountryDetails($iso_2)
    {
        $countryPhoneList = array(
            'AD' => array('name' => 'ANDORRA', 'code' => '376'),
            'AE' => array('name' => 'UNITED ARAB EMIRATES', 'code' => '971'),
            'AF' => array('name' => 'AFGHANISTAN', 'code' => '93'),
            'AG' => array('name' => 'ANTIGUA AND BARBUDA', 'code' => '1268'),
            'AI' => array('name' => 'ANGUILLA', 'code' => '1264'),
            'AL' => array('name' => 'ALBANIA', 'code' => '355'),
            'AM' => array('name' => 'ARMENIA', 'code' => '374'),
            'AN' => array('name' => 'NETHERLANDS ANTILLES', 'code' => '599'),
            'AO' => array('name' => 'ANGOLA', 'code' => '244'),
            'AQ' => array('name' => 'ANTARCTICA', 'code' => '672'),
            'AR' => array('name' => 'ARGENTINA', 'code' => '54'),
            'AS' => array('name' => 'AMERICAN SAMOA', 'code' => '1684'),
            'AT' => array('name' => 'AUSTRIA', 'code' => '43'),
            'AU' => array('name' => 'AUSTRALIA', 'code' => '61'),
            'AW' => array('name' => 'ARUBA', 'code' => '297'),
            'AZ' => array('name' => 'AZERBAIJAN', 'code' => '994'),
            'BA' => array('name' => 'BOSNIA AND HERZEGOVINA', 'code' => '387'),
            'BB' => array('name' => 'BARBADOS', 'code' => '1246'),
            'BD' => array('name' => 'BANGLADESH', 'code' => '880'),
            'BE' => array('name' => 'BELGIUM', 'code' => '32'),
            'BF' => array('name' => 'BURKINA FASO', 'code' => '226'),
            'BG' => array('name' => 'BULGARIA', 'code' => '359'),
            'BH' => array('name' => 'BAHRAIN', 'code' => '973'),
            'BI' => array('name' => 'BURUNDI', 'code' => '257'),
            'BJ' => array('name' => 'BENIN', 'code' => '229'),
            'BL' => array('name' => 'SAINT BARTHELEMY', 'code' => '590'),
            'BM' => array('name' => 'BERMUDA', 'code' => '1441'),
            'BN' => array('name' => 'BRUNEI DARUSSALAM', 'code' => '673'),
            'BO' => array('name' => 'BOLIVIA', 'code' => '591'),
            'BR' => array('name' => 'BRAZIL', 'code' => '55'),
            'BS' => array('name' => 'BAHAMAS', 'code' => '1242'),
            'BT' => array('name' => 'BHUTAN', 'code' => '975'),
            'BW' => array('name' => 'BOTSWANA', 'code' => '267'),
            'BY' => array('name' => 'BELARUS', 'code' => '375'),
            'BZ' => array('name' => 'BELIZE', 'code' => '501'),
            'CA' => array('name' => 'CANADA', 'code' => '1'),
            'CC' => array('name' => 'COCOS (KEELING) ISLANDS', 'code' => '61'),
            'CD' => array('name' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE', 'code' => '243'),
            'CF' => array('name' => 'CENTRAL AFRICAN REPUBLIC', 'code' => '236'),
            'CG' => array('name' => 'CONGO', 'code' => '242'),
            'CH' => array('name' => 'SWITZERLAND', 'code' => '41'),
            'CI' => array('name' => 'COTE D IVOIRE', 'code' => '225'),
            'CK' => array('name' => 'COOK ISLANDS', 'code' => '682'),
            'CL' => array('name' => 'CHILE', 'code' => '56'),
            'CM' => array('name' => 'CAMEROON', 'code' => '237'),
            'CN' => array('name' => 'CHINA', 'code' => '86'),
            'CO' => array('name' => 'COLOMBIA', 'code' => '57'),
            'CR' => array('name' => 'COSTA RICA', 'code' => '506'),
            'CU' => array('name' => 'CUBA', 'code' => '53'),
            'CV' => array('name' => 'CAPE VERDE', 'code' => '238'),
            'CX' => array('name' => 'CHRISTMAS ISLAND', 'code' => '61'),
            'CY' => array('name' => 'CYPRUS', 'code' => '357'),
            'CZ' => array('name' => 'CZECH REPUBLIC', 'code' => '420'),
            'DE' => array('name' => 'GERMANY', 'code' => '49'),
            'DJ' => array('name' => 'DJIBOUTI', 'code' => '253'),
            'DK' => array('name' => 'DENMARK', 'code' => '45'),
            'DM' => array('name' => 'DOMINICA', 'code' => '1767'),
            'DO' => array('name' => 'DOMINICAN REPUBLIC', 'code' => '1809'),
            'DZ' => array('name' => 'ALGERIA', 'code' => '213'),
            'EC' => array('name' => 'ECUADOR', 'code' => '593'),
            'EE' => array('name' => 'ESTONIA', 'code' => '372'),
            'EG' => array('name' => 'EGYPT', 'code' => '20'),
            'ER' => array('name' => 'ERITREA', 'code' => '291'),
            'ES' => array('name' => 'SPAIN', 'code' => '34'),
            'ET' => array('name' => 'ETHIOPIA', 'code' => '251'),
            'FI' => array('name' => 'FINLAND', 'code' => '358'),
            'FJ' => array('name' => 'FIJI', 'code' => '679'),
            'FK' => array('name' => 'FALKLAND ISLANDS (MALVINAS)', 'code' => '500'),
            'FM' => array('name' => 'MICRONESIA, FEDERATED STATES OF', 'code' => '691'),
            'FO' => array('name' => 'FAROE ISLANDS', 'code' => '298'),
            'FR' => array('name' => 'FRANCE', 'code' => '33'),
            'GA' => array('name' => 'GABON', 'code' => '241'),
            'GB' => array('name' => 'UNITED KINGDOM', 'code' => '44'),
            'GD' => array('name' => 'GRENADA', 'code' => '1473'),
            'GE' => array('name' => 'GEORGIA', 'code' => '995'),
            'GH' => array('name' => 'GHANA', 'code' => '233'),
            'GI' => array('name' => 'GIBRALTAR', 'code' => '350'),
            'GL' => array('name' => 'GREENLAND', 'code' => '299'),
            'GM' => array('name' => 'GAMBIA', 'code' => '220'),
            'GN' => array('name' => 'GUINEA', 'code' => '224'),
            'GQ' => array('name' => 'EQUATORIAL GUINEA', 'code' => '240'),
            'GR' => array('name' => 'GREECE', 'code' => '30'),
            'GT' => array('name' => 'GUATEMALA', 'code' => '502'),
            'GU' => array('name' => 'GUAM', 'code' => '1671'),
            'GW' => array('name' => 'GUINEA-BISSAU', 'code' => '245'),
            'GY' => array('name' => 'GUYANA', 'code' => '592'),
            'HK' => array('name' => 'HONG KONG', 'code' => '852'),
            'HN' => array('name' => 'HONDURAS', 'code' => '504'),
            'HR' => array('name' => 'CROATIA', 'code' => '385'),
            'HT' => array('name' => 'HAITI', 'code' => '509'),
            'HU' => array('name' => 'HUNGARY', 'code' => '36'),
            'ID' => array('name' => 'INDONESIA', 'code' => '62'),
            'IE' => array('name' => 'IRELAND', 'code' => '353'),
            'IL' => array('name' => 'ISRAEL', 'code' => '972'),
            'IM' => array('name' => 'ISLE OF MAN', 'code' => '44'),
            'IN' => array('name' => 'INDIA', 'code' => '91'),
            'IQ' => array('name' => 'IRAQ', 'code' => '964'),
            'IR' => array('name' => 'IRAN, ISLAMIC REPUBLIC OF', 'code' => '98'),
            'IS' => array('name' => 'ICELAND', 'code' => '354'),
            'IT' => array('name' => 'ITALY', 'code' => '39'),
            'JM' => array('name' => 'JAMAICA', 'code' => '1876'),
            'JO' => array('name' => 'JORDAN', 'code' => '962'),
            'JP' => array('name' => 'JAPAN', 'code' => '81'),
            'KE' => array('name' => 'KENYA', 'code' => '254'),
            'KG' => array('name' => 'KYRGYZSTAN', 'code' => '996'),
            'KH' => array('name' => 'CAMBODIA', 'code' => '855'),
            'KI' => array('name' => 'KIRIBATI', 'code' => '686'),
            'KM' => array('name' => 'COMOROS', 'code' => '269'),
            'KN' => array('name' => 'SAINT KITTS AND NEVIS', 'code' => '1869'),
            'KP' => array('name' => 'KOREA DEMOCRATIC PEOPLES REPUBLIC OF', 'code' => '850'),
            'KR' => array('name' => 'KOREA REPUBLIC OF', 'code' => '82'),
            'KW' => array('name' => 'KUWAIT', 'code' => '965'),
            'KY' => array('name' => 'CAYMAN ISLANDS', 'code' => '1345'),
            'KZ' => array('name' => 'KAZAKSTAN', 'code' => '7'),
            'LA' => array('name' => 'LAO PEOPLES DEMOCRATIC REPUBLIC', 'code' => '856'),
            'LB' => array('name' => 'LEBANON', 'code' => '961'),
            'LC' => array('name' => 'SAINT LUCIA', 'code' => '1758'),
            'LI' => array('name' => 'LIECHTENSTEIN', 'code' => '423'),
            'LK' => array('name' => 'SRI LANKA', 'code' => '94'),
            'LR' => array('name' => 'LIBERIA', 'code' => '231'),
            'LS' => array('name' => 'LESOTHO', 'code' => '266'),
            'LT' => array('name' => 'LITHUANIA', 'code' => '370'),
            'LU' => array('name' => 'LUXEMBOURG', 'code' => '352'),
            'LV' => array('name' => 'LATVIA', 'code' => '371'),
            'LY' => array('name' => 'LIBYAN ARAB JAMAHIRIYA', 'code' => '218'),
            'MA' => array('name' => 'MOROCCO', 'code' => '212'),
            'MC' => array('name' => 'MONACO', 'code' => '377'),
            'MD' => array('name' => 'MOLDOVA, REPUBLIC OF', 'code' => '373'),
            'ME' => array('name' => 'MONTENEGRO', 'code' => '382'),
            'MF' => array('name' => 'SAINT MARTIN', 'code' => '1599'),
            'MG' => array('name' => 'MADAGASCAR', 'code' => '261'),
            'MH' => array('name' => 'MARSHALL ISLANDS', 'code' => '692'),
            'MK' => array('name' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'code' => '389'),
            'ML' => array('name' => 'MALI', 'code' => '223'),
            'MM' => array('name' => 'MYANMAR', 'code' => '95'),
            'MN' => array('name' => 'MONGOLIA', 'code' => '976'),
            'MO' => array('name' => 'MACAU', 'code' => '853'),
            'MP' => array('name' => 'NORTHERN MARIANA ISLANDS', 'code' => '1670'),
            'MR' => array('name' => 'MAURITANIA', 'code' => '222'),
            'MS' => array('name' => 'MONTSERRAT', 'code' => '1664'),
            'MT' => array('name' => 'MALTA', 'code' => '356'),
            'MU' => array('name' => 'MAURITIUS', 'code' => '230'),
            'MV' => array('name' => 'MALDIVES', 'code' => '960'),
            'MW' => array('name' => 'MALAWI', 'code' => '265'),
            'MX' => array('name' => 'MEXICO', 'code' => '52'),
            'MY' => array('name' => 'MALAYSIA', 'code' => '60'),
            'MZ' => array('name' => 'MOZAMBIQUE', 'code' => '258'),
            'NA' => array('name' => 'NAMIBIA', 'code' => '264'),
            'NC' => array('name' => 'NEW CALEDONIA', 'code' => '687'),
            'NE' => array('name' => 'NIGER', 'code' => '227'),
            'NG' => array('name' => 'NIGERIA', 'code' => '234'),
            'NI' => array('name' => 'NICARAGUA', 'code' => '505'),
            'NL' => array('name' => 'NETHERLANDS', 'code' => '31'),
            'NO' => array('name' => 'NORWAY', 'code' => '47'),
            'NP' => array('name' => 'NEPAL', 'code' => '977'),
            'NR' => array('name' => 'NAURU', 'code' => '674'),
            'NU' => array('name' => 'NIUE', 'code' => '683'),
            'NZ' => array('name' => 'NEW ZEALAND', 'code' => '64'),
            'OM' => array('name' => 'OMAN', 'code' => '968'),
            'PA' => array('name' => 'PANAMA', 'code' => '507'),
            'PE' => array('name' => 'PERU', 'code' => '51'),
            'PF' => array('name' => 'FRENCH POLYNESIA', 'code' => '689'),
            'PG' => array('name' => 'PAPUA NEW GUINEA', 'code' => '675'),
            'PH' => array('name' => 'PHILIPPINES', 'code' => '63'),
            'PK' => array('name' => 'PAKISTAN', 'code' => '92'),
            'PL' => array('name' => 'POLAND', 'code' => '48'),
            'PM' => array('name' => 'SAINT PIERRE AND MIQUELON', 'code' => '508'),
            'PN' => array('name' => 'PITCAIRN', 'code' => '870'),
            'PR' => array('name' => 'PUERTO RICO', 'code' => '1'),
            'PS' => array('name' => 'PALESTINE', 'code' => '970'),
            'PT' => array('name' => 'PORTUGAL', 'code' => '351'),
            'PW' => array('name' => 'PALAU', 'code' => '680'),
            'PY' => array('name' => 'PARAGUAY', 'code' => '595'),
            'QA' => array('name' => 'QATAR', 'code' => '974'),
            'RO' => array('name' => 'ROMANIA', 'code' => '40'),
            'RS' => array('name' => 'SERBIA', 'code' => '381'),
            'RU' => array('name' => 'RUSSIAN FEDERATION', 'code' => '7'),
            'RW' => array('name' => 'RWANDA', 'code' => '250'),
            'SA' => array('name' => 'SAUDI ARABIA', 'code' => '966'),
            'SB' => array('name' => 'SOLOMON ISLANDS', 'code' => '677'),
            'SC' => array('name' => 'SEYCHELLES', 'code' => '248'),
            'SD' => array('name' => 'SUDAN', 'code' => '249'),
            'SE' => array('name' => 'SWEDEN', 'code' => '46'),
            'SG' => array('name' => 'SINGAPORE', 'code' => '65'),
            'SH' => array('name' => 'SAINT HELENA', 'code' => '290'),
            'SI' => array('name' => 'SLOVENIA', 'code' => '386'),
            'SK' => array('name' => 'SLOVAKIA', 'code' => '421'),
            'SL' => array('name' => 'SIERRA LEONE', 'code' => '232'),
            'SM' => array('name' => 'SAN MARINO', 'code' => '378'),
            'SN' => array('name' => 'SENEGAL', 'code' => '221'),
            'SO' => array('name' => 'SOMALIA', 'code' => '252'),
            'SR' => array('name' => 'SURINAME', 'code' => '597'),
            'ST' => array('name' => 'SAO TOME AND PRINCIPE', 'code' => '239'),
            'SV' => array('name' => 'EL SALVADOR', 'code' => '503'),
            'SY' => array('name' => 'SYRIAN ARAB REPUBLIC', 'code' => '963'),
            'SZ' => array('name' => 'SWAZILAND', 'code' => '268'),
            'TC' => array('name' => 'TURKS AND CAICOS ISLANDS', 'code' => '1649'),
            'TD' => array('name' => 'CHAD', 'code' => '235'),
            'TG' => array('name' => 'TOGO', 'code' => '228'),
            'TH' => array('name' => 'THAILAND', 'code' => '66'),
            'TJ' => array('name' => 'TAJIKISTAN', 'code' => '992'),
            'TK' => array('name' => 'TOKELAU', 'code' => '690'),
            'TL' => array('name' => 'TIMOR-LESTE', 'code' => '670'),
            'TM' => array('name' => 'TURKMENISTAN', 'code' => '993'),
            'TN' => array('name' => 'TUNISIA', 'code' => '216'),
            'TO' => array('name' => 'TONGA', 'code' => '676'),
            'TR' => array('name' => 'TURKEY', 'code' => '90'),
            'TT' => array('name' => 'TRINIDAD AND TOBAGO', 'code' => '1868'),
            'TV' => array('name' => 'TUVALU', 'code' => '688'),
            'TW' => array('name' => 'TAIWAN, PROVINCE OF CHINA', 'code' => '886'),
            'TZ' => array('name' => 'TANZANIA, UNITED REPUBLIC OF', 'code' => '255'),
            'UA' => array('name' => 'UKRAINE', 'code' => '380'),
            'UG' => array('name' => 'UGANDA', 'code' => '256'),
            'US' => array('name' => 'UNITED STATES', 'code' => '1'),
            'UY' => array('name' => 'URUGUAY', 'code' => '598'),
            'UZ' => array('name' => 'UZBEKISTAN', 'code' => '998'),
            'VA' => array('name' => 'HOLY SEE (VATICAN CITY STATE)', 'code' => '39'),
            'VC' => array('name' => 'SAINT VINCENT AND THE GRENADINES', 'code' => '1784'),
            'VE' => array('name' => 'VENEZUELA', 'code' => '58'),
            'VG' => array('name' => 'VIRGIN ISLANDS, BRITISH', 'code' => '1284'),
            'VI' => array('name' => 'VIRGIN ISLANDS, U.S.', 'code' => '1340'),
            'VN' => array('name' => 'VIET NAM', 'code' => '84'),
            'VU' => array('name' => 'VANUATU', 'code' => '678'),
            'WF' => array('name' => 'WALLIS AND FUTUNA', 'code' => '681'),
            'WS' => array('name' => 'SAMOA', 'code' => '685'),
            'XK' => array('name' => 'KOSOVO', 'code' => '381'),
            'YE' => array('name' => 'YEMEN', 'code' => '967'),
            'YT' => array('name' => 'MAYOTTE', 'code' => '262'),
            'ZA' => array('name' => 'SOUTH AFRICA', 'code' => '27'),
            'ZM' => array('name' => 'ZAMBIA', 'code' => '260'),
            'ZW' => array('name' => 'ZIMBABWE', 'code' => '263')
        );

        $arr = array();

        if (isset($countryPhoneList[$iso_2])) {
            $phcountry = $countryPhoneList[$iso_2];
            $arr['phone'] = $phcountry['code'];
            $arr['country'] = $phcountry['name'];
        }

        return $arr;
    }

    public static function countryGetiso3($iso_2)
    {
        $iso = array(
            'AND' => 'AD',
            'ARE' => 'AE',
            'AFG' => 'AF',
            'ATG' => 'AG',
            'AIA' => 'AI',
            'ALB' => 'AL',
            'ARM' => 'AM',
            'AGO' => 'AO',
            'ATA' => 'AQ',
            'ARG' => 'AR',
            'ASM' => 'AS',
            'AUT' => 'AT',
            'AUS' => 'AU',
            'ABW' => 'AW',
            'ALA' => 'AX',
            'AZE' => 'AZ',
            'BIH' => 'BA',
            'BRB' => 'BB',
            'BGD' => 'BD',
            'BEL' => 'BE',
            'BFA' => 'BF',
            'BGR' => 'BG',
            'BHR' => 'BH',
            'BDI' => 'BI',
            'BEN' => 'BJ',
            'BLM' => 'BL',
            'BMU' => 'BM',
            'BRN' => 'BN',
            'BOL' => 'BO',
            'BES' => 'BQ',
            'BRA' => 'BR',
            'BHS' => 'BS',
            'BTN' => 'BT',
            'BVT' => 'BV',
            'BWA' => 'BW',
            'BLR' => 'BY',
            'BLZ' => 'BZ',
            'CAN' => 'CA',
            'CCK' => 'CC',
            'COD' => 'CD',
            'CAF' => 'CF',
            'COG' => 'CG',
            'CHE' => 'CH',
            'CIV' => 'CI',
            'COK' => 'CK',
            'CHL' => 'CL',
            'CMR' => 'CM',
            'CHN' => 'CN',
            'COL' => 'CO',
            'CRI' => 'CR',
            'CUB' => 'CU',
            'CPV' => 'CV',
            'CUW' => 'CW',
            'CXR' => 'CX',
            'CYP' => 'CY',
            'CZE' => 'CZ',
            'DEU' => 'DE',
            'DJI' => 'DJ',
            'DNK' => 'DK',
            'DMA' => 'DM',
            'DOM' => 'DO',
            'DZA' => 'DZ',
            'ECU' => 'EC',
            'EST' => 'EE',
            'EGY' => 'EG',
            'ESH' => 'EH',
            'ERI' => 'ER',
            'ESP' => 'ES',
            'ETH' => 'ET',
            'FIN' => 'FI',
            'FJI' => 'FJ',
            'FLK' => 'FK',
            'FSM' => 'FM',
            'FRO' => 'FO',
            'FRA' => 'FR',
            'GAB' => 'GA',
            'GBR' => 'GB',
            'GRD' => 'GD',
            'GEO' => 'GE',
            'GUF' => 'GF',
            'GGY' => 'GG',
            'GHA' => 'GH',
            'GIB' => 'GI',
            'GRL' => 'GL',
            'GMB' => 'GM',
            'GIN' => 'GN',
            'GLP' => 'GP',
            'GNQ' => 'GQ',
            'GRC' => 'GR',
            'SGS' => 'GS',
            'GTM' => 'GT',
            'GUM' => 'GU',
            'GNB' => 'GW',
            'GUY' => 'GY',
            'HKG' => 'HK',
            'HMD' => 'HM',
            'HND' => 'HN',
            'HRV' => 'HR',
            'HTI' => 'HT',
            'HUN' => 'HU',
            'IDN' => 'ID',
            'IRL' => 'IE',
            'ISR' => 'IL',
            'IMN' => 'IM',
            'IND' => 'IN',
            'IOT' => 'IO',
            'IRQ' => 'IQ',
            'IRN' => 'IR',
            'ISL' => 'IS',
            'ITA' => 'IT',
            'JEY' => 'JE',
            'JAM' => 'JM',
            'JOR' => 'JO',
            'JPN' => 'JP',
            'KEN' => 'KE',
            'KGZ' => 'KG',
            'KHM' => 'KH',
            'KIR' => 'KI',
            'COM' => 'KM',
            'KNA' => 'KN',
            'PRK' => 'KP',
            'KOR' => 'KR',
            'XKX' => 'XK',
            'KWT' => 'KW',
            'CYM' => 'KY',
            'KAZ' => 'KZ',
            'LAO' => 'LA',
            'LBN' => 'LB',
            'LCA' => 'LC',
            'LIE' => 'LI',
            'LKA' => 'LK',
            'LBR' => 'LR',
            'LSO' => 'LS',
            'LTU' => 'LT',
            'LUX' => 'LU',
            'LVA' => 'LV',
            'LBY' => 'LY',
            'MAR' => 'MA',
            'MCO' => 'MC',
            'MDA' => 'MD',
            'MNE' => 'ME',
            'MAF' => 'MF',
            'MDG' => 'MG',
            'MHL' => 'MH',
            'MKD' => 'MK',
            'MLI' => 'ML',
            'MMR' => 'MM',
            'MNG' => 'MN',
            'MAC' => 'MO',
            'MNP' => 'MP',
            'MTQ' => 'MQ',
            'MRT' => 'MR',
            'MSR' => 'MS',
            'MLT' => 'MT',
            'MUS' => 'MU',
            'MDV' => 'MV',
            'MWI' => 'MW',
            'MEX' => 'MX',
            'MYS' => 'MY',
            'MOZ' => 'MZ',
            'NAM' => 'NA',
            'NCL' => 'NC',
            'NER' => 'NE',
            'NFK' => 'NF',
            'NGA' => 'NG',
            'NIC' => 'NI',
            'NLD' => 'NL',
            'NOR' => 'NO',
            'NPL' => 'NP',
            'NRU' => 'NR',
            'NIU' => 'NU',
            'NZL' => 'NZ',
            'OMN' => 'OM',
            'PAN' => 'PA',
            'PER' => 'PE',
            'PYF' => 'PF',
            'PNG' => 'PG',
            'PHL' => 'PH',
            'PAK' => 'PK',
            'POL' => 'PL',
            'SPM' => 'PM',
            'PCN' => 'PN',
            'PRI' => 'PR',
            'PSE' => 'PS',
            'PRT' => 'PT',
            'PLW' => 'PW',
            'PRY' => 'PY',
            'QAT' => 'QA',
            'REU' => 'RE',
            'ROU' => 'RO',
            'SRB' => 'RS',
            'RUS' => 'RU',
            'RWA' => 'RW',
            'SAU' => 'SA',
            'SLB' => 'SB',
            'SYC' => 'SC',
            'SDN' => 'SD',
            'SSD' => 'SS',
            'SWE' => 'SE',
            'SGP' => 'SG',
            'SHN' => 'SH',
            'SVN' => 'SI',
            'SJM' => 'SJ',
            'SVK' => 'SK',
            'SLE' => 'SL',
            'SMR' => 'SM',
            'SEN' => 'SN',
            'SOM' => 'SO',
            'SUR' => 'SR',
            'STP' => 'ST',
            'SLV' => 'SV',
            'SXM' => 'SX',
            'SYR' => 'SY',
            'SWZ' => 'SZ',
            'TCA' => 'TC',
            'TCD' => 'TD',
            'ATF' => 'TF',
            'TGO' => 'TG',
            'THA' => 'TH',
            'TJK' => 'TJ',
            'TKL' => 'TK',
            'TLS' => 'TL',
            'TKM' => 'TM',
            'TUN' => 'TN',
            'TON' => 'TO',
            'TUR' => 'TR',
            'TTO' => 'TT',
            'TUV' => 'TV',
            'TWN' => 'TW',
            'TZA' => 'TZ',
            'UKR' => 'UA',
            'UGA' => 'UG',
            'UMI' => 'UM',
            'USA' => 'US',
            'URY' => 'UY',
            'UZB' => 'UZ',
            'VAT' => 'VA',
            'VCT' => 'VC',
            'VEN' => 'VE',
            'VGB' => 'VG',
            'VIR' => 'VI',
            'VNM' => 'VN',
            'VUT' => 'VU',
            'WLF' => 'WF',
            'WSM' => 'WS',
            'YEM' => 'YE',
            'MYT' => 'YT',
            'ZAF' => 'ZA',
            'ZMB' => 'ZM',
            'ZWE' => 'ZW',
            'SCG' => 'CS',
            'ANT' => 'AN',
        );

        $iso_3 = "";

        foreach ($iso as $key => $val) {
            if ($val == $iso_2) {
                $iso_3 = $key;
                break;
            }
        }

        return $iso_3;
    }
}


/**
 * Holder class that holds PayTabs's request's values
 */
class PaytabsHolder
{
    const GLUE = ' || ';
    const THRESHOLD = 1.0;

    /**
     * payment_type
     */
    private $payment_code;

    /**
     * title
     * msg_lang
     */
    private $invoiceInfo;

    /**
     * currency
     * amount
     * other_charges
     * discount
     */
    private $payment;

    /**
     * products_per_title
     * quantity
     * unit_price
     */
    private $products;

    /**
     * reference_no
     */
    private $reference_num;

    /**
     * cc_first_name
     * cc_last_name
     * cc_phone_number
     * phone_number
     * email
     */
    private $customer_info;

    /**
     * billing_address
     * state
     * city
     * postal_code
     * country
     */
    private $billing;

    /**
     * shipping_firstname
     * shipping_lastname
     * address_shipping
     * city_shipping
     * state_shipping
     * postal_code_shipping
     * country_shipping
     */
    private $shipping;

    /**
     * hide_personal_info
     * hide_billing
     * hide_view_invoice
     */
    private $hide_options;

    /**
     * site_url
     * return_url
     */
    private $urls;

    /**
     * cms_with_version
     */
    private $cms_version;

    /**
     * ip_customer
     */
    private $ip_customer;

    /**
     * is_preauth
     */
    private $preauth;

    /**
     * is_tokenization
     * is_existing_customer
     */
    private $tokenization;

    /**
     * valu_product_id
     * valu_down_payment
     */
    private $valu_params;

    //

    /**
     * Try to get ride of the ugly message: "Your total amount is not matching to sum of unit price amounts per quantity".
     * Because of rounding products' prices, may occur some diff between the sums of products & total amount.
     * if that diff is under 1 then change the "amount" to total sums.
     * @return true if the calculation is correct or the amount been rounded in @param $post_arr['amount']
     */
    public function fix_amounts()
    {
        $pay = array_merge($this->payment, $this->products);

        $amount = $pay['amount'];
        $other_charges = $pay['other_charges'];

        $quantities = $pay['quantity'];
        $unit_prices = $pay['unit_price'];

        $sums = 0;
        $products_q = explode(' || ', $quantities);
        $products_p = explode(' || ', $unit_prices);
        for ($i = 0; $i < count($products_p); $i++) {
            $sums += ($products_p[$i] * $products_q[$i]);
        }

        $sums += $other_charges;

        $diff = round($amount - $sums, 2);
        if ($diff != 0) {
            $_logParams = json_encode($pay);

            if (self::THRESHOLD >= 0 && abs($diff) > self::THRESHOLD) {
                PaytabsHelper::log("PaytabsHelper::round_amount: diff = {$diff}, [{$_logParams}]", 3);
            } else {
                PaytabsHelper::log("PaytabsHelper::round_amount: diff = {$diff} added to 'other_charges', [{$_logParams}]", 2);

                $other_charges += $diff;
                $this->payment['other_charges'] = $other_charges;
            }

            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function pt_build($fix_amounts = true)
    {
        if ($fix_amounts) {
            $this->fix_amounts();
        }

        $all = array_merge(
            $this->payment_code,
            $this->invoiceInfo,
            $this->payment,
            $this->products,
            $this->reference_num,
            $this->customer_info,
            $this->billing,
            $this->shipping,
            $this->urls,
            $this->cms_version,
            $this->ip_customer
        );

        if ($this->payment_code['payment_type'] === 'valu') {
            $all = array_merge($all, $this->valu_params);
        }

        if ($this->hide_options) {
            $all = array_merge($all, $this->hide_options);
        }

        if ($this->preauth) {
            $all = array_merge($all, $this->preauth);
        }

        if ($this->tokenization) {
            $all = array_merge($all, $this->tokenization);
        }

        return $all;
    }

    private function _fill(&$var, ...$options)
    {
        $var = trim($var);
        $var = PaytabsHelper::getNonEmpty($var, ...$options);
    }

    //

    public function set01PaymentCode($code)
    {
        $this->payment_code = ['payment_type' => $code];

        return $this;
    }

    public function set02ReferenceNum($reference_num)
    {
        $this->reference_num = ['reference_no' => $reference_num];

        return $this;
    }

    public function set03InvoiceInfo($title, $lang = 'English')
    {
        $this->invoiceInfo = [
            'title' => $title,
            'msg_lang' => $lang,
        ];

        return $this;
    }

    public function set04Payment($currency, $amount, $other_charges, $discount)
    {
        $this->payment = [
            'currency'      => $currency,
            'amount'        => $amount,
            'other_charges' => $other_charges,
            'discount'      => $discount,
        ];

        return $this;
    }


    /**
     * @param $items: array of the products, each product has the format ['name' => xx, 'quantity' => x, 'price' => x]
     */
    public function set05Products($products)
    {
        $products_str = implode(self::GLUE, array_map(function ($p) {
            $name = str_replace('||', '/', $p['name']);
            return $name;
        }, $products));

        $quantity = implode(self::GLUE, array_map(function ($p) {
            return $p['quantity'];
        }, $products));

        $unit_price = implode(self::GLUE, array_map(function ($p) {
            return round($p['price'], 2);
        }, $products));

        //

        $this->products = [
            'products_per_title' => $products_str,
            'quantity'           => $quantity,
            'unit_price'         => $unit_price,
        ];

        return $this;
    }

    public function set06CustomerInfo($firstname, $lastname, $phone_prefix, $phone_number, $email)
    {
        // Remove country_code from phone_number if it is same as the user's Country code
        $phone_number = preg_replace("/^[\+|00]+{$phone_prefix}/", '', $phone_number);

        PaytabsHelper::pt_fillIfEmpty($firstname);
        PaytabsHelper::pt_fillIfEmpty($lastname);

        //

        $this->customer_info = [
            'cc_first_name'   => $firstname,
            'cc_last_name'    => $lastname,
            'cc_phone_number' => $phone_prefix,
            'phone_number'    => $phone_number,
            'email'           => $email,
        ];

        return $this;
    }

    public function set07Billing($address, $state, $city, $postal_code, $country)
    {
        $this->_fill($address, 'NA');

        PaytabsHelper::pt_fillIfEmpty($city);

        $this->_fill($state, $city, 'NA');

        $this->_fill($postal_code, '11111');
        $postal_code = PaytabsHelper::convertAr2En($postal_code);

        //

        $this->billing = [
            'billing_address' => $address,
            'state'           => $state,
            'city'            => $city,
            'postal_code'     => $postal_code,
            'country'         => $country,
        ];

        return $this;
    }

    public function set08Shipping($firstname, $lastname, $address, $state, $city, $postal_code, $country)
    {
        $this->_fill($firstname, $this->customer_info['cc_first_name']);
        $this->_fill($lastname, $this->customer_info['cc_last_name']);

        $this->_fill($address, $this->billing['billing_address']);
        $this->_fill($city, $this->billing['city']);
        $this->_fill($state, $city, $this->billing['state']);
        $this->_fill($postal_code, $this->billing['postal_code']);
        $this->_fill($country, $this->billing['country']);

        //

        $this->shipping = [
            'shipping_firstname'   => $firstname,
            'shipping_lastname'    => $lastname,
            'address_shipping'     => $address,
            'city_shipping'        => $city,
            'state_shipping'       => $state,
            'postal_code_shipping' => $postal_code,
            'country_shipping'     => $country,
        ];

        return $this;
    }

    /**
     * Optional method
     */
    public function set09HideOptions($hide_personal_info, $hide_billing, $hide_view_invoice)
    {
        $this->hide_options = [
            'hide_personal_info' => $hide_personal_info ? 'true' : '',
            'hide_billing' => $hide_billing ? 'true' : '',
            'hide_view_invoice' => $hide_view_invoice ? 'true' : '',
        ];

        return $this;
    }

    public function set10URLs($site_url, $return_url)
    {
        $this->urls = [
            'site_url'   => $site_url,
            'return_url' => $return_url,
        ];

        return $this;
    }

    public function set11CMSVersion($cms_version)
    {
        $this->cms_version = ['cms_with_version' => $cms_version];

        return $this;
    }

    public function set12IPCustomer($ip_customer)
    {
        $this->ip_customer = ['ip_customer' => $ip_customer];

        return $this;
    }

    /**
     * Optional method
     * https://dev.paytabs.com/docs/paypage.html#pre-authorization-using-api
     */
    public function set13PreAuth($isPreAuth = false)
    {
        $this->preauth = [
            'is_preauth' => $isPreAuth ? 1 : 0,
        ];

        return $this;
    }

    /**
     * Optional method
     * Call it only in case you want to use Tokenization feature
     * https://dev.paytabs.com/docs/tokenization/
     */
    public function set14Tokenization($isTokenization = false, $isExistingCustomer = false)
    {
        $this->tokenization = [
            'is_tokenization' => $isTokenization ? 'TRUE' : 'FALSE',
            'is_existing_customer' => $isExistingCustomer ? 'TRUE' : 'FALSE',
        ];

        return $this;
    }

    /**
     * Required method if Payment method = valU
     */
    public function set20ValuParams($valu_product_id, $valu_down_payment = 0)
    {
        $this->valu_params = [
            'valu_product_id' => $valu_product_id,
            'valu_down_payment' => $valu_down_payment,
        ];

        return $this;
    }
}


/**
 * Holder class that holds PayTabs's request's values
 */
class PaytabsTokenizeHolder
{
    /**
     * order_id
     * title
     * product_name
     */
    private $invoiceInfo;

    /**
     * amount
     * currency
     */
    private $payment;

    /**
     * cc_first_name
     * cc_last_name
     * phone_number
     * customer_email
     */
    private $customer_info;

    /**
     * address_billing
     * state_billing
     * city_billing
     * postal_code_billing
     * country_billing
     */
    private $billing;

    /**
     * address_shipping
     * city_shipping
     * state_shipping
     * postal_code_shipping
     * country_shipping
     */
    private $shipping;

    /**
     * billing_shipping_details
     */
    private $billing_shipping_details;

    /**
     * pt_token
     * pt_customer_email
     * pt_customer_password
     */
    private $tokenInfo;


    //

    /**
     * @return array
     */
    public function pt_build()
    {
        $all = array_merge(
            $this->invoiceInfo,
            $this->payment,
            $this->customer_info,
            $this->tokenInfo
        );

        if ($this->billing_shipping_details) {
            $all = array_merge(
                $all,
                $this->billing_shipping_details
            );
        } else {
            $all = array_merge(
                $all,
                $this->billing,
                $this->shipping
            );
        }

        return $all;
    }

    private function _fill(&$var, ...$options)
    {
        $var = trim($var);
        $var = PaytabsHelper::getNonEmpty($var, ...$options);
    }

    //

    public function set01InvoiceInfo($orderId, $title, $productName)
    {
        $this->invoiceInfo = [
            'order_id' => $orderId,
            'title' => $title,
            'product_name' => $productName
        ];

        return $this;
    }

    public function set02Payment($currency, $amount)
    {
        $this->payment = [
            'currency' => $currency,
            'amount'   => $amount,
        ];

        return $this;
    }

    public function set03CustomerInfo($firstname, $lastname, $phone_number, $email)
    {
        PaytabsHelper::pt_fillIfEmpty($firstname);
        PaytabsHelper::pt_fillIfEmpty($lastname);

        //

        $this->customer_info = [
            'cc_first_name'  => $firstname,
            'cc_last_name'   => $lastname,
            'phone_number'   => $phone_number,
            'customer_email' => $email,
        ];

        return $this;
    }

    /**
     * Optional method
     */
    public function set04Billing($address, $state, $city, $postal_code, $country)
    {
        $this->_fill($address, 'NA');

        PaytabsHelper::pt_fillIfEmpty($city);

        $this->_fill($state, $city, 'NA');

        $this->_fill($postal_code, '11111');
        $postal_code = PaytabsHelper::convertAr2En($postal_code);

        //

        $this->billing = [
            'address_billing'     => $address,
            'state_billing'       => $state,
            'city_billing'        => $city,
            'postal_code_billing' => $postal_code,
            'country_billing'     => $country,
        ];

        return $this;
    }

    /**
     * Optional method
     */
    public function set05Shipping($address, $state, $city, $postal_code, $country)
    {
        $this->_fill($address, $this->billing['billing_address']);
        $this->_fill($city, $this->billing['city']);
        $this->_fill($state, $city, $this->billing['state']);
        $this->_fill($postal_code, $this->billing['postal_code']);
        $this->_fill($country, $this->billing['country']);

        //

        $this->shipping = [
            'address_shipping'     => $address,
            'city_shipping'        => $city,
            'state_shipping'       => $state,
            'postal_code_shipping' => $postal_code,
            'country_shipping'     => $country,
        ];

        return $this;
    }

    /**
     * @param $on if TRUE => Do not pass Billing & Shipping parameters to the server
     */
    public function set06NoBillingAndShipping($on = false)
    {
        if ($on) {
            $this->billing_shipping_details = [
                'billing_shipping_details' => 'no'
            ];
        } else {
            $this->billing_shipping_details = false;
        }

        return $this;
    }

    public function set07TokenInfo($token, $customer_email, $customer_password)
    {
        $this->tokenInfo = [
            'pt_token' => $token,
            'pt_customer_email' => $customer_email,
            'pt_customer_password' => $customer_password,
        ];

        return $this;
    }
}


/**
 * Holder class that holds PayTabs's request's values
 */
class PaytabsRefundHolder
{

    /**
     * refund_amount
     * refund_reason
     */
    private $refundInfo;

    /**
     * transaction_id
     * order_id *
     */
    private $transaction_id;


    //

    /**
     * @return array
     */
    public function pt_build()
    {
        $all = array_merge(
            $this->refundInfo,
            $this->transaction_id
        );

        return $all;
    }

    //

    public function set01RefundInfo($amount, $reason)
    {
        $this->refundInfo = [
            'refund_amount' => $amount,
            'refund_reason' => $reason,
        ];

        return $this;
    }

    public function set02Transaction($transaction_id)
    {
        $this->transaction_id = [
            'transaction_id' => $transaction_id
        ];

        return $this;
    }
}


/**
 * API class which contacts PayTabs server's API
 */
class PaytabsApi
{
    const PAYMENT_TYPES = [
        '1'  => ['name' => 'stcpay', 'title' => 'PayTabs - StcPay', 'currencies' => ['SAR']],
        '2'  => ['name' => 'stcpayqr', 'title' => 'PayTabs - StcPay(QR)', 'currencies' => ['SAR']],
        '3'  => ['name' => 'applepay', 'title' => 'PayTabs - ApplePay', 'currencies' => ['AED', 'SAR']],
        '4'  => ['name' => 'omannet', 'title' => 'PayTabs - OmanNet', 'currencies' => ['OMR']],
        '5'  => ['name' => 'mada', 'title' => 'PayTabs - Mada', 'currencies' => ['SAR']],
        '6'  => ['name' => 'creditcard', 'title' => 'PayTabs - CreditCard', 'currencies' => null],
        '7'  => ['name' => 'sadad', 'title' => 'PayTabs - Sadad', 'currencies' => ['SAR']],
        '8'  => ['name' => 'atfawry', 'title' => 'PayTabs - @Fawry', 'currencies' => ['EGP']],
        '9'  => ['name' => 'knpay', 'title' => 'PayTabs - KnPay', 'currencies' => ['KWD']],
        '10' => ['name' => 'amex', 'title' => 'PayTabs - Amex', 'currencies' => ['AED', 'SAR']],
        '11' => ['name' => 'valu', 'title' => 'PayTabs - valU', 'currencies' => ['EGP']],
    ];
    const BASE_URL = 'https://www.paytabs.com/';

    const AUTHENTICATION_URL = PaytabsApi::BASE_URL . 'apiv2/validate_secret_key';
    const PAYPAGE_URL        = PaytabsApi::BASE_URL . 'apiv2/create_pay_page';
    const VERIFY_URL         = PaytabsApi::BASE_URL . 'apiv2/verify_payment';
    const TOKENIZATION_URL   = PaytabsApi::BASE_URL . 'apiv3/tokenized_transaction_prepare';
    const REFUND_URL         = PaytabsApi::BASE_URL . 'apiv2/refund_process';

    //

    private $merchant_email;
    private $secret_key;

    //

    private static $instance = null;

    //

    public static function getInstance($merchant_id, $key)
    {
        if (self::$instance == null) {
            self::$instance = new PaytabsApi($merchant_id, $key);
        }

        // self::$instance->setAuth($merchant_email, $secret_key);

        return self::$instance;
    }

    private function __construct($merchant_email, $secret_key)
    {
        $this->setAuth($merchant_email, $secret_key);
    }

    private function setAuth($merchant_email, $secret_key)
    {
        $this->merchant_email = $merchant_email;
        $this->secret_key = $secret_key;
    }

    private function appendAuth(&$values)
    {
        $values['merchant_email'] = $this->merchant_email;
        $values['secret_key'] = $this->secret_key;
    }

    /** start: API calls */

    function authentication()
    {
        $values = [];
        $this->appendAuth($values);

        $obj = json_decode($this->runPost(self::AUTHENTICATION_URL, $values), TRUE);

        if ($obj->response_code == "4000") {
            return TRUE;
        }
        return FALSE;
    }

    function create_pay_page($values)
    {
        $this->appendAuth($values);

        $serverIP = getHostByName(getHostName());
        $values['ip_merchant'] = PaytabsHelper::getNonEmpty($serverIP, $_SERVER['SERVER_ADDR'], 'NA');

        $values['ip_customer'] = PaytabsHelper::getNonEmpty($values['ip_customer'], $_SERVER['REMOTE_ADDR'], 'NA');

        $res = json_decode($this->runPost(self::PAYPAGE_URL, $values));
        $paypage = $this->enhance($res);

        return $paypage;
    }

    function verify_payment($payment_reference)
    {
        $this->appendAuth($values);

        $values['payment_reference'] = $payment_reference;

        $res = json_decode($this->runPost(self::VERIFY_URL, $values));
        $verify = $this->enhanceVerify($res);

        return $verify;
    }

    function tokenized_payment($values)
    {
        $this->appendAuth($values);

        $res = json_decode($this->runPost(self::TOKENIZATION_URL, $values));
        $tokenized = $this->enhanceVerify($res);

        return $tokenized;
    }

    function refund($values)
    {
        $this->appendAuth($values);

        $res = json_decode($this->runPost(self::REFUND_URL, $values));
        $refund = $this->enhanceRefund($res);

        return $refund;
    }

    /** end: API calls */


    /** start: Local calls */

    /**
     * paypage structure: null || stdClass->[result | details, response_code, payment_url, p_id]
     * @return paypage structure: stdClass->[success, result|message, response_code, payment_url, p_id]
     */
    private function enhance($paypage)
    {
        $_paypage = $paypage;

        if (!$paypage) {
            $_paypage = new stdClass();
            $_paypage->success = false;
            $_paypage->result = 'Create paytabs payment failed';
        } else {
            $_paypage->success = ($paypage->response_code == 4012 && !empty($paypage->payment_url));

            $msg = '';
            if (isset($paypage->result)) $msg .= $paypage->result;
            if (isset($paypage->details)) $msg .= $paypage->details;

            $_paypage->result = $msg;
        }

        $_paypage->message = $_paypage->result;

        return $_paypage;
    }

    private function enhanceVerify($verify)
    {
        $_verify = $verify;

        if (!$verify) {
            $_verify = new stdClass();
            $_verify->success = false;
            $_verify->result = 'Verifying paytabs payment failed';
        } else {

            $_verify->success = isset($verify->response_code) && $verify->response_code == 100;
        }

        $_verify->message = $_verify->result;

        return $_verify;
    }

    private function enhanceRefund($refund)
    {
        $_refund = $refund;

        if (!$refund) {
            $_refund = new stdClass();
            $_refund->success = false;
            $_refund->result = 'Verifying paytabs Refund failed';
        } else {
            if (isset($refund->response_code)) {
                $_refund->success = $refund->response_code == 814;
                $_refund->pending_success = $refund->response_code == 812;
            } else {
                $_refund->success = false;
            }
        }

        $_refund->message = $_refund->result;

        return $_refund;
    }

    /** end: Local calls */

    private function runPost($url, $fields)
    {
        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . urlencode($value) . '&';
        }
        $fields_string = rtrim($fields_string, '&');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
