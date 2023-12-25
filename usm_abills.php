<?php
$moduleRevision = "262";

// ========================================================================
// Custom configuration
// ========================================================================

$isDebugMode = 1; //Debug Mode [0/1]

// ========================================================================
$additionalDataMerge         = [];
$additionalCustomerDataMerge = [];
$markMerge                   = [];

$fileConfig = realpath(__DIR__) . '/usm_abills.conf.php';
require_once($fileConfig);
$billingCrcId = md5(fread(fopen($fileConfig, "r"), filesize($fileConfig)));

$billingName   = 'abills_db';
$moduleName    = "usm_abills";
$isAddOnModule = 1;

$billing = new Billing();

class Billing
{

    public function verifyBillingApi()
    {
        global $billingDBHost, $billingDBUser, $billingDBPassword, $billingDBName, $moduleHelper, $isDatabaseOpen;
        global $billingSqlData;
        //Billing custom conf
        global $confAdrDistrictMode, $confDateConnectSrc, $confUserImportExpr, $confAddFieldPhone, $confAddFieldCellPhone;
        global $confAddFieldEntrance, $confAddFieldFloor, $confImportCustomerTaxNumber, $confTaxNumberField, $confImportCustomerPassport;
        global $confPassportField, $confIsSavePasswordToComment, $confIsCidIp, $confUserStateSrc, $confWorkState2;
        global $confWorkState5, $confIsHideDvLogData, $confIsHideIpAdrFromDvCalls, $confIsHideCidFromDvMain;
        global $confIsIpAdrOnlyFromDhcpHosts, $confImportCustomerNasIp, $confImportCustomerNasPort, $confImportNasName, $confImportGroup;
        global $confIsIpAdrSkipFromDhcpHosts, $confUserAccountSrc, $confIsImportTags, $billingDBPort;
        global $confPasswordSecretKey, $confAddressSrc, $confIsImportOnlyUsedAddress, $confIsImportMsg;
        global $confIsImportPasswordToUsPassword, $confContactTypeIdPhone, $confContactTypeIdCellPhone;
        global $confIsPrimaryDvModule, $confImportNasVlan, $confImportCustomerNasName, $confImportCustomerNasVlan;
        global $confImportCustomerGroup, $confAddressListCityIds;

        if ('' == $confAddFieldPhone && 'phone' != $confAddFieldCellPhone) {
            $confAddFieldPhone = "phone";
        }

        if ('users_pi' != $confAddressSrc) {
            $confAddressSrc = 'builds';
        }

        if ($confContactTypeIdPhone < 1) {
            $confContactTypeIdPhone = 1;
        }
        if ($confContactTypeIdCellPhone < 1) {
            $confContactTypeIdCellPhone = 2;
        }

        //Фиксируем специфические параметры
        Log::w("confAdrDistrictMode                      : " . $confAdrDistrictMode);
        Log::w("confDateConnectSrc                       : " . $confDateConnectSrc);
        Log::w("confUserImportExpr                       : " . $confUserImportExpr);
        Log::w("confAddFieldPhone                        : " . $confAddFieldPhone);
        Log::w("confAddFieldCellPhone                    : " . $confAddFieldCellPhone);
        Log::w("confAddFieldEntrance                     : " . $confAddFieldEntrance);
        Log::w("confAddFieldFloor                        : " . $confAddFieldFloor);
        Log::w("confImportCustomerTaxNumber              : " . $confImportCustomerTaxNumber);
        Log::w("confTaxNumberField                       : " . $confTaxNumberField);
        Log::w("confImportCustomerPassport               : " . $confImportCustomerPassport);
        Log::w("confPassportField                        : " . $confPassportField);
        Log::w("confIsCidIp                              : " . $confIsCidIp);
        Log::w("confUserStateSrc                         : " . $confUserStateSrc);
        Log::w("confWorkState2                           : " . $confWorkState2);
        Log::w("confWorkState5                           : " . $confWorkState5);
        Log::w("confIsHideDvLogData                      : " . $confIsHideDvLogData);
        Log::w("confIsHideIpAdrFromDvCalls               : " . $confIsHideIpAdrFromDvCalls);
        Log::w("confIsHideCidFromDvMain                  : " . $confIsHideCidFromDvMain);
        Log::w("confIsIpAdrOnlyFromDhcpHosts             : " . $confIsIpAdrOnlyFromDhcpHosts);
        Log::w("confImportCustomerNasIp                  : " . $confImportCustomerNasIp);
        Log::w("confImportCustomerNasPort                : " . $confImportCustomerNasPort);
        Log::w("confImportNasName                        : " . $confImportNasName);
        Log::w("confImportCustomerNasName                : " . $confImportCustomerNasName);
        Log::w("confImportNasVlan                        : " . $confImportNasVlan);
        Log::w("confImportCustomerNasVlan                : " . $confImportCustomerNasVlan);
        Log::w("confImportGroup                          : " . $confImportGroup);
        Log::w("confImportCustomerGroup                  : " . $confImportCustomerGroup);
        Log::w("confIsIpAdrSkipFromDhcpHosts             : " . $confIsIpAdrSkipFromDhcpHosts);
        Log::w("confUserAccountSrc                       : " . $confUserAccountSrc);
        Log::w("confIsImportTags                         : " . $confIsImportTags);
        Log::w("confAddressSrc                           : " . $confAddressSrc);
        Log::w("billingDBPort                            : " . $billingDBPort);
        Log::w("confIsImportOnlyUsedAddress              : " . $confIsImportOnlyUsedAddress);
        Log::w("confIsImportMsg                          : " . $confIsImportMsg);
        Log::w("confContactTypeIdPhone                   : " . $confContactTypeIdPhone);
        Log::w("confContactTypeIdCellPhone               : " . $confContactTypeIdCellPhone);
        Log::w("confIsPrimaryDvModule                    : " . $confIsPrimaryDvModule);
        Log::w("confAddressListCityIds                   : " . $confAddressListCityIds);
        if ('' != $confPasswordSecretKey) {
            Log::w("confPasswordSecretKey                    : enabled");
        } else {
            Log::w("confPasswordSecretKey                    : not set");
        }

        //Открываем соединение с базой биллинга
        if ('' == $billingDBPort) {
            $billingDBPort = 3306;
        }
        Database::connect($billingDBHost, $billingDBUser, $billingDBPassword, $billingDBName, $billingDBPort);

        $data           = Database::query("SELECT NOW()");
        $billingSqlData = $data[0];

        $data      = Database::query("SHOW VARIABLES WHERE `Variable_name` LIKE 'version_compile%'", true);
        $billingOS = $data['version_compile_os']['Value'] . ' ' . $data['version_compile_machine']['Value'];

        $isDatabaseOpen = 1;

        $responce = array(
            'date' => $billingSqlData,
            'os' => $billingOS,
            'billing' => array(
                'name' => '-',
                'version' => '-'
            )
        );
        return $responce;
    }

    public function getTariffList()
    {
        global $isInternetPlus, $confIsPrimaryDvModule;

        if ($confIsPrimaryDvModule == 1) {
            $isInternetPlus = 0;
        } else {
            //Проверяем - есть ли таблица модуля Internet+
            $data = Database::query("SHOW TABLES LIKE 'internet_main'", true);
            if (0 < count($data)) {
                $isInternetPlus = 1;
            } else {
                $isInternetPlus = 0;
            }
        }

        $data = Database::query("SHOW TABLES LIKE 'iptv_main'", true);
        if (0 < count($data)) {
            $isIpTvMain = 1;
        } else {
            $isIpTvMain = 0;
        }

        //Данные по тарифам
        $query = "
            SELECT
                t.id AS 'id',
                t.name AS 'name',
                t.month_fee AS 'month_fee',
                t.day_fee AS 'day_fee',
                tt.prepaid AS 'traffic',
                tt.in_speed AS 'speed_up',
                tt.out_speed AS 'speed_down',
                d.speed AS 'speed_user',";
        if ($isIpTvMain === 1) {
            $query .= "
                iptv.id AS 'iptv_id'";
        } else {
            $query .= "
                0 AS 'iptv_id'";
        }
        $query .= "
            FROM
                tarif_plans AS t
            LEFT JOIN
                intervals AS i
            ON
                i.tp_id = t.tp_id
            LEFT JOIN
                trafic_tarifs AS tt
            ON
                tt.interval_id = i.id";
        if ($isIpTvMain === 1) {
            $query .= "
            LEFT JOIN
                iptv_main AS iptv
            ON
                iptv.tp_id = t.tp_id";
        }
        if ($isInternetPlus == 1) {
            $query .= "
            LEFT JOIN
                internet_main AS d
            ON
                d.tp_id = t.tp_id
            AND
                d.speed > 0";
        } else {
            $query .= "
            LEFT JOIN
                dv_main AS d
            ON
                d.tp_id = t.id
            AND
                d.speed > 0";
        }
        $query .= "
            GROUP BY
                t.id";
        $data  = Database::query($query, true, 'id');
        Log::rawLog('tariff', $data);
        $responce = array();
        foreach ($data as $i => $value) {
            $payment          = $value['month_fee'];
            $payment_interval = 30;
            if (0 == $payment) {
                $payment          = $value['day_fee'];
                $payment_interval = 1;
            }
            $speedUp   = $value['speed_up'];
            $speedDown = $value['speed_down'];
            if (0 == $speedUp) {
                $speedUp   = $value['speed_user'];
                $speedDown = $value['speed_user'];
            }
            $traffic = floor($value['traffic']);
            if (0 == $traffic) {
                $traffic = -1;
            }
            if ($value['iptv_id'] > 0) {
                $serviceType = 3; //ТВ
            } else {
                $serviceType = 0;
            }
            $responce[$value['id']] = array(
                'id' => $value['id'],
                'name' => $value['name'],
                'payment' => $payment,
                'payment_interval' => $payment_interval,
                'traffic' => $traffic,
                'service_type' => $serviceType,
                'speed' => array(
                    'up' => $speedUp,
                    'down' => $speedDown
                )
            );
        }
        Log::d(json_encode($responce));
        return $responce;
    }

    public function getBillingState()
    {
        $responce = array(
            2 => array(
                'id' => 2,
                'name' => 'Active',
                'functional' => 'work'
            ),
            1 => array(
                'id' => 1,
                'name' => 'Pause',
                'functional' => 'pause'
            ),
            0 => array(
                'id' => 0,
                'name' => 'Stop',
                'functional' => 'stop'
            )
        );
        return $responce;
    }

    public function getBillingTags()
    {
        global $confIsImportTags;
        $responce = array();
        if (1 == $confIsImportTags) {
            $query    = "
                SELECT
                    id AS 'id',
                    name AS 'name'
                FROM
                    tags";
            $responce = Database::query($query, true, 'id');
            Log::d(str_replace("\n", ' ', json_encode($responce)));
        }
        return $responce;
    }

    public function getBillingMsg()
    {
        global $confIsImportMsg, $lastMsgId;
        $responce = array();
        if (1 == $confIsImportMsg) {
            $query    = "
                SELECT
                    mm.id AS 'id',
                    mm.uid AS 'user_id',
                    mm.date AS 'msg_date',
                    mm.subject AS 'subject',
                    mm.message AS 'text'
                FROM
                    msgs_messages AS mm
                WHERE
                    mm.uid > 0
                AND
                    mm.id > " . floor($lastMsgId) . "
                AND
                    mm.date > '" . date('Y-m-d H:i:s', time() - 86400 * 180) . "'                    
                ORDER BY
                    mm.id";
            $responce = Database::query($query, true, 'id');

            $dateMsg          = date('Y-m-d', time() - 86400 * 90);
            $query            = "
                SELECT
                    `id`,
                    `state`
                FROM
                    `msgs_messages`
                WHERE
                    `date` > '" . $dateMsg . "'
                AND
                    `state` <> 0";
            $responce['edit'] = Database::query($query, true, 'id');
            foreach ($responce['edit'] as $i => $value) {
                $responce['edit'][$i]['answer'] = "Update Msg State ID: " . $value['id'] . " STATE: " . $value['state'];
            }
            Log::d(str_replace("\n", ' ', json_encode($responce)));
        }
        return $responce;
    }

    public function getBillingGroup()
    {
        $query    = "
            SELECT
                gid AS id,
                name
            FROM
                `groups`";
        $responce = Database::query($query, true, 'id');
        return $responce;
    }

    public function getServiceList()
    {
        $responce = array();
        return $responce;
    }

    public function getProvinceList()
    {
        return [];
    }

    public function getDistrictList()
    {
        return [];
    }

    public function getAreaList()
    {
        return [];
    }

    public function getHouseList()
    {
        global $arrayBillingAddress;
        Log::d(json_encode($arrayBillingAddress['house']));
        return $arrayBillingAddress['house'];
    }

    public function getStreetList()
    {
        global $arrayBillingAddress;
        Log::d(json_encode($arrayBillingAddress['street']));
        return $arrayBillingAddress['street'];
    }

    public function getCityList()
    {
        global $arrayBillingAddress, $confAddressSrc, $confIsImportOnlyUsedAddress, $confAddressListCityIds;
        //Billing custom conf
        global $confAdrDistrictMode;
        $arrayBillingAddress = array(
            'city' => array(),
            'street' => array(),
            'house' => array()
        );

        $cityAddressArray = [];
        if ($confAddressListCityIds !== '') {
            $query = "
                SELECT
                    id,
                    name
                FROM
                    districts
                WHERE
                    type_id IN ('" . $confAddressListCityIds . "')";
            $data  = Database::query($query, true, 'id');
            Log::rawLog('address', $data);
            foreach ($data as $value) {
                $cityAddressArray[$value['id']] = $value['name'];
            }
        }

        //Загружаем массив всех адресов
        if ('builds' == $confAddressSrc) {
            $query = "
                SELECT
                    b.id AS 'id',
                    UPPER(b.number) AS 'number',
                    s.id AS 'street_id',
                    s.name AS 'street',
                    d.city AS 'city',
                    d.name AS 'district',
                    d.id AS d_id";
            if (count($cityAddressArray) > 0) {
                $query .= ",
                    d2.id AS d2_id,
                    d2.name AS d2_name,
                    d3.id AS d3_id,
                    d3.name AS d3_name";
            }
            $query .= "
                FROM
                    builds AS b
                INNER JOIN
                    streets AS s
                ON
                    s.id = b.street_id
                LEFT JOIN
                    districts AS d
                ON
                    d.id = s.district_id";
            if (count($cityAddressArray) > 0) {
                $query .= "
                LEFT JOIN
                    districts2 AS d2
                ON
                    d2.id = d.parent_id
                LEFT JOIN
                    districts3 AS d3
                ON
                    d3.id = d2.parent_id";
            }
            if (1 == $confIsImportOnlyUsedAddress) {
                $query .= "
                INNER JOIN
                    users_pi AS up
                ON
                    up.location_id = b.id";
            }
            $data = Database::query($query, true, 'id');
            Log::rawLog('address', $data);
            foreach ($data as $i => $value) {
                $street   = $value['street'];
                $streetId = $value['street_id'];
                $houseId  = $value['id'];
                $number   = $value['number'];
                //if (strlen($value['number']) != strlen($number)) {
                //    $block = trim(substr($value['number'], strlen($number), strlen($value['number'])));
                //    $block = str_replace('/', '', $block);
                //} else {
                //    $block = '';
                // }
                $block = '';
                $city  = '';
                if ($confAdrDistrictMode == 1) {
                    $street = $value['district'] . ' ' . $street;
                }
                if ($confAdrDistrictMode == 2) {
                    $city = $value['district'];
                }
                if ($confAdrDistrictMode == 3) {
                    $city = $value['city'];
                }

                if (count($cityAddressArray) > 0) {
                    if (isset($cityAddressArray[$value['d_id']])) {
                        $city = $value['district'];
                    } elseif (isset($cityAddressArray[$value['d2_id']])) {
                        $city = $value['d2_name'];
                    } elseif (isset($cityAddressArray[$value['d3_id']])) {
                        $city = $value['d3_name'];
                    }
                }

                $cityId = md5($city);

                $arrayBillingAddress['city'][$cityId]     = array(
                    'id' => $cityId,
                    'name' => $city
                );
                $arrayBillingAddress['street'][$streetId] = array(
                    'id' => $streetId,
                    'city_id' => $cityId,
                    'name' => $street
                );
                $arrayBillingAddress['house'][$houseId]   = array(
                    'id' => $houseId,
                    'street_id' => $streetId,
                    'number' => $number,
                    'block' => $block
                );
            }
        } else {
            $query = "
                SELECT
                    uid,
                    city,
                    address_street AS 'street',
                    UPPER(address_build) AS 'number'
                FROM
                    users_pi
                GROUP BY
                    city, address_street, address_build";
            $data  = Database::query($query, true, 'uid');
            Log::rawLog('address', $data);
            foreach ($data as $i => $value) {
                $street   = $value['street'];
                $streetId = md5($value['city'] . '_' . $street);
                $number   = $value['number'];
                $houseId  = md5($value['city'] . '_' . $street . '_' . $number);
                $city     = $value['city'];
                $cityId   = md5($city);

                $arrayBillingAddress['city'][$cityId]     = array(
                    'id' => $cityId,
                    'name' => $city
                );
                $arrayBillingAddress['street'][$streetId] = array(
                    'id' => $streetId,
                    'city_id' => $cityId,
                    'name' => $street
                );
                $arrayBillingAddress['house'][$houseId]   = array(
                    'id' => $houseId,
                    'street_id' => $streetId,
                    'number' => $number,
                    'block' => ''
                );
            }
        }
        Log::d(json_encode($arrayBillingAddress['city']));
        return $arrayBillingAddress['city'];
    }

    public function getCustomerList()
    {
        global $billingSqlData, $additionalDataMerge, $lastPaidId, $lastMsgId, $isUpdateTraffic;
        //Billing custom conf
        global $confDateConnectSrc, $confUserImportExpr, $confAddFieldPhone, $confAddFieldCellPhone, $confWorkState2;
        global $confAddFieldEntrance, $confAddFieldFloor, $confImportCustomerTaxNumber, $confTaxNumberField, $confWorkState5;
        global $confImportCustomerPassport, $confPassportField, $confIsSavePasswordToComment, $confIsCidIp, $confUserStateSrc;
        global $confIsHideDvLogData, $confIsHideIpAdrFromDvCalls, $confIsHideCidFromDvMain, $confIsIpAdrOnlyFromDhcpHosts;
        global $confImportGroup, $confIsIpAdrSkipFromDhcpHosts, $confImportCustomerNasIp, $confImportCustomerNasPort, $confImportNasName;
        global $confUserAccountSrc, $confIsImportTags, $confPasswordSecretKey, $confAddressSrc, $confIsImportPasswordToUsPassword;
        global $isInternetPlus, $confContactTypeIdCellPhone, $confContactTypeIdPhone, $confIsPrimaryDvModule;
        global $isTableInternetOnline, $confImportNasVlan, $confImportCustomerNasVlan, $additionalCustomerDataMerge;
        global $confImportCustomerNasName, $confImportCustomerGroup;

        //Нужно ЕЩЕ раз (предварительно) загрузить данные по городам - а то адреса не потянутся (ТТ-18541)
        $this->getCityList();

        $responce = array();
        //Формируем массив доп.полей
        if (0 < $confImportCustomerNasIp) {
            $additionalCustomerDataMerge[$confImportCustomerNasIp] = $confImportCustomerNasIp;
        }
        if (0 < $confImportCustomerNasPort) {
            $additionalCustomerDataMerge[$confImportCustomerNasPort] = $confImportCustomerNasPort;
        }
        if (0 < $confImportNasVlan) {
            $additionalDataMerge[$confImportNasVlan] = $confImportNasVlan;
        }
        if (0 < $confImportCustomerNasVlan) {
            $additionalCustomerDataMerge[$confImportCustomerNasVlan] = $confImportCustomerNasVlan;
        }
        if (0 < $confImportNasName) {
            $additionalDataMerge[$confImportNasName] = $confImportNasName;
        }
        if (0 < $confImportCustomerNasName) {
            $additionalCustomerDataMerge[$confImportCustomerNasName] = $confImportCustomerNasName;
        }
        if (0 < $confImportCustomerTaxNumber) {
            $additionalCustomerDataMerge[$confImportCustomerTaxNumber] = $confImportCustomerTaxNumber;
        }
        if (0 < $confImportCustomerPassport) {
            $additionalCustomerDataMerge[$confImportCustomerPassport] = $confImportCustomerPassport;
        }
        if (0 < $confImportGroup) {
            $additionalDataMerge[$confImportGroup] = $confImportGroup;
        }
        if (0 < $confImportCustomerGroup) {
            $additionalCustomerDataMerge[$confImportCustomerGroup] = $confImportCustomerGroup;
        }

        //Глобальные данные по абонентам
        Log::d("Load Customer From Billing");

        //Проверяем - есть ли таблица контактов
        $data = Database::query("SHOW TABLES LIKE 'users_contacts'", true);
        if (0 < count($data)) {
            $isTableContact = 1;
        } else {
            $isTableContact = 0;
        }

        //Проверяем - есть ли поля
        //TT-23731: ABillS 0.91.5
        $data = Database::query("SHOW COLUMNS FROM users_pi WHERE Field = 'fio3'", true, 'Field');
        if (isset($data['fio3'])) {
            $isFieldFio3 = 1;
        } else {
            $isFieldFio3 = 0;
        }

        $data = Database::query("SHOW COLUMNS FROM users WHERE Field = 'deleted'", true, 'Field');
        if (isset($data['deleted'])) {
            $isFieldDeleted = 1;
        } else {
            $isFieldDeleted = 0;
        }
        if (1 == $confDateConnectSrc) {
            $fieldDateConnect = "u.registration";
        } else {
            $fieldDateConnect = "MIN(dl.start)";
        }
        if ('' != $confAddFieldCellPhone) {
            $fieldCellPhone = "up." . $confAddFieldCellPhone;
        } else {
            $fieldCellPhone = "''";
        }
        if ('' != $confAddFieldPhone) {
            $fieldPhone = "up." . $confAddFieldPhone;
        } else {
            $fieldPhone = "''";
        }

        if ('' != $confAddFieldEntrance) {
            $fieldEntrance = "up." . $confAddFieldEntrance;
        } else {
            $fieldEntrance = "''";
        }
        if ('' != $confAddFieldFloor) {
            $fieldFloor = "up." . $confAddFieldFloor;
        } else {
            $fieldFloor = "''";
        }
        if (0 < $confImportCustomerTaxNumber) {
            $fieldTaxNumber = "up." . $confTaxNumberField;
        } else {
            $fieldTaxNumber = "''";
        }
        if (0 < $confImportCustomerPassport) {
            $fieldPassportData = "up." . $confPassportField;
        } else {
            $fieldPassportData = "''";
        }
        if (1 == $confIsCidIp) {
            $fieldCid = "INET_ATON(dm.cid)";
        } else {
            $fieldCid = "REPLACE(REPLACE(UPPER(dm.cid), ':', ''), '-', '')";
        }
        if ($isInternetPlus == 1) {
            $dvMainIp = "''";
        } else {
            $dvMainIp = "dm.ip";
        }

        $ipOwner = array();


        //Центральная выборка по абонентам
        $query = "
            SELECT
                u.uid AS 'id',
                u.id AS 'login',
                up.fio AS 'full_name',";
        if (1 == $isFieldFio3) {
            $query .= "
                up.fio2 AS 'fio2',
                up.fio3 AS 'fio3',";
        }
        if ('' != $confPasswordSecretKey) {
            $query .= "
                decode(u.password, '" . $confPasswordSecretKey . "') AS 'password',";
        } else {
            $query .= "
                u.password AS 'password',";
        }
        $query .= "
                " . $fieldDateConnect . " AS 'date_connect',
                u.credit AS 'credit',
                u.reduction AS 'discount',
                t.id AS 'tariff',
                dm.disable AS 'state_id',
                u.disable AS 'state_id2',
                up.contract_id AS 'agreement_number',
                up.contract_date AS 'agreement_date',
                IFNULL(b.deposit, 0) AS 'balance',
                " . $fieldPhone . " AS 'phone',
                " . $fieldCellPhone . " AS 'phone_cell',
                up.email AS 'email',
                IFNULL(up.comments, '') AS 'comment',
                up.location_id AS 'house_id',
                " . $fieldEntrance . " AS 'entrance',
                " . $fieldFloor . " AS 'floor',";
        if ('users_pi' == $confAddressSrc) {
            $query .= "
                up.city AS 'city_pi',
                up.address_street AS 'street_pi',
                UPPER(up.address_build) AS 'house_number_pi',";
        }
        $query .= "
                up.address_flat AS 'apartment',
                u.company_id AS 'company_id',
                IFNULL(b2.deposit, 0) AS 'company_balance',
                c.bill_id AS 'c_bill_id',
                c.ext_bill_id AS 'c_ext_bill_id',
                u.ext_bill_id AS 'u_ext_bill_id',
                u.gid AS 'group',
                " . $dvMainIp . " AS dv_ip,
                " . $fieldCid . " AS 'cid_ip',
                " . $fieldTaxNumber . " AS 'tax_number',
                " . $fieldPassportData . " AS 'passport_data',
                ";
        if ('' != $confUserAccountSrc && 'uid' != $confUserAccountSrc && 'bill_id' != $confUserAccountSrc) {
            $query .= $confUserAccountSrc;
        } else {
            $query .= "u.bill_id";
        }
        $query .= " AS 'account_number'";
        if (1 == $isTableContact) {
            $query .= ",
                (SELECT value FROM users_contacts WHERE uid = u.uid AND type_id = " . $confContactTypeIdPhone . " LIMIT 1) AS phone_new,
                (SELECT value FROM users_contacts WHERE uid = u.uid AND type_id = " . $confContactTypeIdCellPhone . " LIMIT 1) AS phone_cell_new";
        }
        $query .= "
            FROM
                users AS u";
        if (2 == $confDateConnectSrc) {
            if ($isInternetPlus == 1) {
                $tableLogName = 'internet_log';
            } else {
                $tableLogName = 'dv_log';
            }
            $query .= "
            LEFT JOIN
                (SELECT uid, MIN(start) AS start FROM " . $tableLogName . " GROUP BY uid) AS dl
            ON
                u.uid = dl.uid";
        }
        $query .= "
            LEFT JOIN
                `groups` AS g
            ON
                g.gid = u.gid";
        if ($isInternetPlus == 1) {
            $query .= "
            LEFT JOIN
                internet_main AS dm
            ON
                dm.uid = u.uid
            LEFT JOIN
                tarif_plans AS t
            ON
                t.tp_id = dm.tp_id";
        } else {
            $query .= "
            LEFT JOIN
                dv_main AS dm
            ON
                dm.uid = u.uid
            LEFT JOIN
                tarif_plans AS t
            ON
                t.id = dm.tp_id";
        }
        $query .= "
            LEFT JOIN
                users_pi AS up
            ON
                up.uid = u.uid
            LEFT JOIN
                bills AS b
            ON
                b.uid = u.uid
            AND
                b.id = u.bill_id
            LEFT JOIN
                companies AS c
            ON
                c.id = u.company_id
            LEFT JOIN
                bills AS b2
            ON
                b2.id = c.bill_id";
        if (1 == $isFieldDeleted) {
            $query .= "
            WHERE
                IFNULL(u.deleted, 0) <> 1";
            if ('' != $confUserImportExpr) {
                $query .= "
            AND
                " . $confUserImportExpr;
            }
        } else {
            if ('' != $confUserImportExpr) {
                $query .= "
            WHERE
                " . $confUserImportExpr;
            }
        }
        $query .= "
            GROUP BY 
                u.uid"; //Если убираем - перестают выбираться абоненты (тикет 4233)
        $data  = Database::query($query, true, 'id');
        Log::rawLog('customer', $data, 1);
        foreach ($data as $i => $value) {
            $customerId  = $value['id'];
            $comment     = $value['comment'];
            $dateConnect = $value['date_connect'];
            if ('' == $dateConnect) {
                $dateConnect = '1970-01-01';
            }
            $phone     = $value['phone'];
            $phoneCell = $value['phone_cell'];
            if (1 == $isTableContact) {
                if ('' != $value['phone_new']) {
                    $phone = $value['phone_new'];
                }
                if ('' != $value['phone_cell_new']) {
                    $phoneCell = $value['phone_cell_new'];
                }
            }
            $stateId = 2; //Play
            if ('u.disable' == $confUserStateSrc) {
                /*
                users.disable
                0 активно
                1 отключено
                2 регистрация подтвердить (хз, нашел методом тыка)
                3 и больше , показывают статус "Отключено"
                */
                if (0 < $value['state_id2']) {
                    $stateId = 0; //stop
                }
            } else {
                /*
                dv_main.disable
                0 Активно
                1 Отключено
                2 Не активизирован
                3 Приостановление
                4 Отключено: Неуплата
                5 Cлишком маленький депозит
                6.Заблокирован из-за вирусов
                */
                switch ($value['state_id']) {
                    case '':
                    case 1:
                    case 4:
                    case 6:
                        $stateId = 0; //stop
                        break;
                    case 3:
                        $stateId = 1; //pause
                        break;
                    case 2:
                        $stateId = $confWorkState2;
                        break;
                    case 5:
                        $stateId = $confWorkState5;
                        break;
                }
            }
            if (0 < $value['company_id']) {
                $balance = $value['company_balance'];
                if ('bill_id' == $confUserAccountSrc) {
                    //$accountNumber = $value['account_number'];
                    $accountNumber = $value['c_bill_id']; #Тикет 6598
                } else {
                    $accountNumber = 0;
                }
                $isCorporate = 1;
            } else {
                $balance = $value['balance'];
                if ('' == $confUserAccountSrc || 'uid' == $confUserAccountSrc) {
                    $accountNumber = $value['id'];
                } else {
                    $accountNumber = $value['account_number'];
                }
                $isCorporate = 0;
            }
            if ('' != $confUserAccountSrc && 'uid' != $confUserAccountSrc && 'bill_id' != $confUserAccountSrc) {
                $accountNumber = $value['account_number'];
            }
            $floor = $value['floor'];
            if (1 > $floor) {
                $floor = 1;
            }

            if ('builds' == $confAddressSrc) {
                $houseId = $value['house_id'];
            } else {
                $houseId = md5($value['city_pi'] . '_' . $value['street_pi'] . '_' . $value['house_number_pi']);
            }

            if (1 == $isFieldFio3) {
                if ('' != $value['fio2']) {
                    $value['full_name'] .= ' ' . $value['fio2'];
                }
                if ('' != $value['fio3']) {
                    $value['full_name'] .= ' ' . $value['fio3'];
                }
                $value['full_name'] = trim($value['full_name']);
            }

            $responce[$customerId] = [
                'id' => $customerId,
                'flag_corporate' => $isCorporate,
                'login' => $value['login'],
                'full_name' => $value['full_name'],
                'comment' => $comment,
                'date_connect' => $dateConnect,
                'state_id' => $stateId,
                'account_number' => $accountNumber,
                'balance' => $balance,
                'address' => array(
                    0 => array(
                        'type' => 'connect',
                        'house_id' => $houseId,
                        'entrance' => $value['entrance'],
                        'floor' => $floor,
                        'apartment' => array(
                            'number' => $value['apartment']
                        )
                    )
                ),
                'email' => array(
                    0 => array(
                        'address' => $value['email']
                    )
                ),
                'agreement' => array(
                    0 => array(
                        'number' => $value['agreement_number'],
                        'date' => $value['agreement_date']
                    )
                ),
                'credit' => $value['credit'],
                'discount' => floor($value['discount']),
                'tariff' => array(
                    'current' => array(
                        $value['tariff'] => array(
                            'id' => $value['tariff']
                        )
                    )
                ),
                'phone' => array(
                    0 => array(
                        'flag_main' => 1,
                        'number' => $phoneCell
                    ),
                    1 => array(
                        'number' => $phone
                    )
                )
            ];
            if ($value['group'] > 0) {
                $responce[$customerId]['group'][$value['group']] = [
                    'id' => $value['group']
                ];
            }
            if (
                1 == $confIsImportPasswordToUsPassword
                ||
                1 == $confIsSavePasswordToComment
            ) {
                $responce[$customerId]['password'] = $value['password'];
            }
            if (0 < $confImportCustomerTaxNumber) {
                $responce[$customerId]['additional_customer_data'][$confImportCustomerTaxNumber] = array(
                    'id' => $confImportCustomerTaxNumber,
                    'value' => $value['tax_number']
                );
            }
            if (0 < $confImportCustomerPassport) {
                $responce[$customerId]['additional_customer_data'][$confImportCustomerPassport] = array(
                    'id' => $confImportCustomerPassport,
                    'value' => $value['passport_data']
                );
            }
            if (0 < $confImportGroup) {
                $responce[$customerId]['additional_data'][$confImportGroup] = array(
                    'id' => $confImportGroup,
                    'value' => $value['group']
                );
            }
            if (0 < $confImportCustomerGroup) {
                $responce[$customerId]['additional_customer_data'][$confImportCustomerGroup] = array(
                    'id' => $confImportCustomerGroup,
                    'value' => $value['group']
                );
            }

            $ip = $value['dv_ip'];
            if ($ip > 0) {
                $responce[$customerId]['ip_mac'][$ip]['ip'] = $ip;
            }

            if (1 != $confIsHideCidFromDvMain && 1 != $confIsIpAdrOnlyFromDhcpHosts) {
                $cid     = $value['cid_ip'];
                $array   = explode(';', $cid);
                $cidMac1 = isset($array[0]) ? $array[0] : '';
                $cidMac2 = isset($array[1]) ? $array[1] : '';
                $cidMac3 = isset($array[2]) ? $array[2] : '';
                $mac     = '';
                if (1 == $confIsCidIp) {
                    $ip = trim(sprintf("%u\n", ip2long($cid)));
                } else {
                    $array = explode('/', $cid);
                    $cidIp = isset($array[0]) ? $array[0] : '';
                    $mac   = isset($array[1]) ? $array[1] : '';
                    $ip    = trim(sprintf("%u\n", ip2long($cidIp)));
                    if (1 > $ip && 12 == strlen($cidIp)) {
                        $mac = $cidIp;
                    }
                    if ('' == $cidIp) {
                        $mac = $cidMac2;
                    }
                }
                if ('' == $mac) {
                    //001D.60C3.AF9FVLAN1235325
                    //90F6.5277.ACD5VLAN1875297
                    $array = explode('VLAN', $cid);
                    $mac   = isset($array[0]) ? $array[0] : '';
                    $mac   = str_replace('.', '', $mac);
                }
                if (12 < strlen($mac)) {
                    $array = explode('VLAN', $mac);
                    $mac   = isset($array[0]) ? $array[0] : '';
                    $mac   = str_replace('.', '', $mac);
                }

                if ('000000000000' == $mac) {
                    $mac = '';
                }

                if (0 < $ip) {
                    if ('' == $mac) {
                        $mac = $cidMac2;
                    }

                    if ('000000000000' == $mac) {
                        $mac = '';
                    }

                    //Убираем у прошлого владельца
                    if (isset($ipOwner[$ip])) {
                        $lastCustomerId = $ipOwner[$ip];
                        unset($responce[$lastCustomerId]['ip_mac'][$ip]);
                        if (0 == count($responce[$lastCustomerId]['ip_mac'])) {
                            unset($responce[$lastCustomerId]['ip_mac']);
                        }
                    }
                    $responce[$customerId]['ip_mac'][$ip]['ip'] = $ip;
                    $ipOwner[$ip]                               = $customerId;
                    if ('' != $mac || !isset($responce[$customerId]['ip_mac'][$ip]['mac'])) {
                        $mac                                         = str_replace('.', '', $mac);
                        $responce[$customerId]['ip_mac'][$ip]['mac'] = $mac;
                    }
                }
            }
        }

        //Информация по активности
        if ($confIsPrimaryDvModule == 1) {
            $isTableInternetOnline = 0;
        } else {
            $data = Database::query("SHOW TABLES LIKE 'internet_online'", false);
            if (isset($data[0])) {
                $isTableInternetOnline = 1;
            } else {
                $isTableInternetOnline = 0;
            }
        }

//Информация по траффику
        $customerAccountData = array();
        if (1 == $isUpdateTraffic) {
            Log::d("Load Traffic");
            $dateStart  = date('Y-m-01 00:00:00', strtotime($billingSqlData));
            $dateFinish = date('Y-m-t 23:59:59', strtotime($billingSqlData));
            if ($isInternetPlus == 1) {
                $tableLogName = 'internet_log';
            } else {
                $tableLogName = 'dv_log';
            }
            $query = "
                SELECT
                    uid,
                    SUM(recv) AS 'up',
                    SUM(sent) AS 'down'
                FROM
                    " . $tableLogName . "
                WHERE
                    start BETWEEN '" . $dateStart . "' AND '" . $dateFinish . "'
                GROUP BY
                    uid";
            $data  = Database::query($query, true, 'uid');
            foreach ($data as $i => $value) {
                $customerId = $value['uid'];
                if (isset($responce[$customerId])) {
                    $responce[$customerId]['traffic']['month'] = array(
                        'up' => $value['up'],
                        'down' => $value['down']
                    );
                }
            }
            if (1 != $isTableInternetOnline) {
                Log::d("Load Traffic. Finish Stage 1");
                $query = "
                SELECT
                    uid,
                    SUM(acct_input_octets + (acct_input_gigawords * 4294967295)) AS 'up',
                    SUM(acct_output_octets + (acct_output_gigawords * 4294967295)) AS 'down'
                FROM
                    dv_calls
                WHERE
                    status = 3 
                AND
                    started >= '" . $dateStart . "'
                GROUP BY
                    uid";
                $data  = Database::query($query, true, 'uid');
                foreach ($data as $i => $value) {
                    $customerId = $value['uid'];
                    if (isset($responce[$customerId])) {
                        if (!isset($responce[$customerId]['traffic']['month']['up'])) {
                            $responce[$customerId]['traffic']['month']['up'] = 0;
                        }
                        $responce[$customerId]['traffic']['month']['up'] += $value['up'];

                        if (!isset($responce[$customerId]['traffic']['month']['down'])) {
                            $responce[$customerId]['traffic']['month']['down'] = 0;
                        }
                        $responce[$customerId]['traffic']['month']['down'] += $value['down'];
                    }
                }
            } else {
                Log::d("Load Traffic. Finish Stage 2");
                $query = "
                    SELECT
                        uid AS uid,
                        SUM(acct_input_octets + (acct_input_gigawords * 4294967295)) AS 'up',
                        SUM(acct_output_octets + (acct_output_gigawords * 4294967295)) AS 'down'
                    FROM
                        internet_online
                    GROUP BY
                        uid";
                $data  = Database::query($query, true, 'uid');
                foreach ($data as $i => $value) {
                    $customerId = $value['uid'];
                    if (isset($responce[$customerId])) {
                        if (!isset($responce[$customerId]['traffic']['month']['up'])) {
                            $responce[$customerId]['traffic']['month']['up'] = 0;
                        }
                        $responce[$customerId]['traffic']['month']['up'] += $value['up'];

                        if (!isset($responce[$customerId]['traffic']['month']['down'])) {
                            $responce[$customerId]['traffic']['month']['down'] = 0;
                        }
                        $responce[$customerId]['traffic']['month']['down'] += $value['down'];
                    }
                }
            }
            Log::d("Load Traffic. Finish");
        }

        if (1 != $confIsHideDvLogData) {
            Log::d("Load Activity From **_log. Start");
            if ($isInternetPlus == 1) {
                $tableLogName = 'internet_log';
            } else {
                $tableLogName = 'dv_log';
            }
            $data   = Database::query("SELECT MAX(uid) FROM " . $tableLogName, false);
            $uidMax = $data[0];
            Log::d("uidMax: " . $uidMax);
            $data     = Database::query("SELECT COUNT(*) FROM " . $tableLogName, false);
            $uidCount = $data[0];
            Log::d("uidCount: " . $uidCount);
            $uidCount -= $uidMax;
            if (0 > $uidCount) {
                $uidCount = 0;
            }
            if (1 == $confIsCidIp) {
                $fieldCid = "INET_ATON(CID)";
            } else {
                $fieldCid = "REPLACE(REPLACE(REPLACE(UPPER(CID), ':', ''), '-', ''), '.', '')";
            }
            $query = "
                SELECT
                    uid,
                    start AS 'date_activity',
                    ip,
                    " . $fieldCid . " AS 'cid'
                FROM
                    " . $tableLogName . "
                LIMIT
                    " . $uidCount . ", " . floor($uidMax);
            $data  = Database::query($query, true, 'uid');
            Log::rawLog('customer_ip', $data);
            foreach ($data as $i => $value) {
                $customerId = $value['uid'];
                if (isset($responce[$customerId])) {
                    if (1 != $isTableInternetOnline) {
                        $responce[$customerId]['date_activity'] = $value['date_activity'];
                    }
                    if (1 != $confIsIpAdrOnlyFromDhcpHosts) {
                        if (1 == $confIsCidIp) {
                            $ip  = $value['cid'];
                            $mac = '';
                        } else {
                            $ip  = $value['ip'];
                            $mac = $value['cid'];
                        }
                        $pos = strpos($mac, '/');
                        if (0 < $pos) {
                            $mac = trim(substr($mac, ($pos + 1)));
                            $pos = strpos($mac, '/');
                            if (0 < $pos) {
                                $tempArray = explode(' ', $mac);
                                $mac       = trim($tempArray[0]);
                            }
                        }

                        if ('000000000000' == $mac) {
                            $mac = '';
                        }

                        if (0 < $ip) {
                            //Убираем у прошлого владельца
                            if (isset($ipOwner[$ip])) {
                                $lastCustomerId = $ipOwner[$ip];
                                unset($responce[$lastCustomerId]['ip_mac'][$ip]);
                                if (0 == count($responce[$lastCustomerId]['ip_mac'])) {
                                    unset($responce[$lastCustomerId]['ip_mac']);
                                }
                            }
                            $responce[$customerId]['ip_mac'][$ip]['ip'] = $ip;
                            $ipOwner[$ip]                               = $customerId;
                            if ('' != $mac || !isset($responce[$customerId]['ip_mac'][$ip]['mac'])) {
                                $mac                                         = str_replace('.', '', $mac);
                                $responce[$customerId]['ip_mac'][$ip]['mac'] = $mac;
                            }
                        }
                    }
                }
            }
            Log::d("Load Activity From **_log. Finish");
        }
        //Опрос из ipn_log исключён по Ticket 11366
        if (1 == 0) {
            $data = Database::query("SHOW TABLES LIKE 'ipn_log'", false);
            if (isset($data[0])) {
                Log::d("Load Activity From ipn_log. Start");
                $data     = Database::query("SELECT MAX(uid) FROM ipn_log", false);
                $uidMax   = $data[0] * 40;
                $data     = Database::query("SELECT COUNT(uid) FROM ipn_log", false);
                $uidCount = $data[0];
                $uidCount -= $uidMax;
                if (0 > $uidCount) {
                    $uidCount = 0;
                }
                $query = "
                SELECT
                    uid,
                    start AS 'date_activity',
                    ip
                FROM
                    ipn_log
                WHERE
                    traffic_in <> 0
                AND
                    traffic_out <> 0
                LIMIT
                    " . $uidCount . ", " . $uidMax;
                $data  = Database::query($query, true, 'uid');
                Log::rawLog('customer_ip', $data);
                foreach ($data as $i => $value) {
                    $customerId = $value['uid'];
                    if (isset($responce[$customerId])) {
                        if (1 != $isTableInternetOnline) {
                            $responce[$customerId]['date_activity'] = $value['date_activity'];
                        }
                        $ip = $value['ip'];
                        if (0 < $ip && 1 != $confIsIpAdrOnlyFromDhcpHosts) {
                            //Убираем у прошлого владельца
                            if (isset($ipOwner[$ip])) {
                                $lastCustomerId = $ipOwner[$ip];
                                unset($responce[$lastCustomerId]['ip_mac'][$ip]);
                                if (0 == count($responce[$lastCustomerId]['ip_mac'])) {
                                    unset($responce[$lastCustomerId]['ip_mac']);
                                }
                            }
                            $responce[$customerId]['ip_mac'][$ip]['ip'] = $ip;
                            $ipOwner[$ip]                               = $customerId;
                        }
                    }
                }
                Log::d("Load Activity From ipn_log. Finish");
            }
        }

        if (1 != $isTableInternetOnline) {
            Log::d("Load Activity From dv_calls. Start");
            $nasArray = array();
            $query    = "
                SELECT
                    dv.uid AS 'uid',
                    NOW() AS 'date_activity',
                    dv.framed_ip_address AS 'ip',
                    REPLACE(REPLACE(REPLACE(UPPER(dv.CID), ':', ''), '-', ''), '.', '') AS 'cid',
                    INET_NTOA(dv.nas_ip_address) AS 'nas_ip',
                    dv.nas_port_id AS 'nas_port',
                    n.name AS 'nas_name',
                    n.id,
                    n2.name AS 'nas_name2',
                    n2.id
                FROM
                    dv_calls AS dv
                LEFT JOIN
                    nas AS n
                ON
                    INET_ATON(n.ip) = dv.nas_ip_address
                LEFT JOIN
                    nas AS n2
                ON
                    n2.ip = dv.nas_ip_address
                WHERE
                    dv.status IN (0 ,1, 3)
                ORDER BY
                    dv.started";
            //TT-18473 - dv_calls.status - Добавлено 0 и 1
            $data = Database::query($query, true, 'uid');
            Log::rawLog('customer_ip', $data);
            foreach ($data as $i => $value) {
                $customerId = $value['uid'];
                if (isset($responce[$customerId])) {
                    $responce[$customerId]['date_activity'] = $value['date_activity'];
                    if (1 != $confIsHideIpAdrFromDvCalls && 1 != $confIsIpAdrOnlyFromDhcpHosts) {
                        $ip = $value['ip'];
                        if (0 < $ip) {
                            $mac = $value['cid'];

                            $pos = strpos($mac, '/');
                            if (0 < $pos) {
                                $mac = trim(substr($mac, ($pos + 1)));
                                $pos = strpos($mac, '/');
                                if (0 < $pos) {
                                    $tempArray = explode(' ', $mac);
                                    $mac       = trim($tempArray[0]);
                                }
                            }

                            $pos = strpos($mac, 'VLAN');
                            if (0 < $pos) {
                                $tempArray = explode('VLAN', $mac);
                                $mac       = trim($tempArray[0]);
                            }

                            if ('000000000000' == $mac) {
                                $mac = '';
                            }

                            //Убираем у прошлого владельца
                            if (isset($ipOwner[$ip])) {
                                $lastCustomerId = $ipOwner[$ip];
                                unset($responce[$lastCustomerId]['ip_mac'][$ip]);
                                if (0 == count($responce[$lastCustomerId]['ip_mac'])) {
                                    unset($responce[$lastCustomerId]['ip_mac']);
                                }
                            }
                            $responce[$customerId]['ip_mac'][$ip]['ip'] = $ip;
                            $ipOwner[$ip]                               = $customerId;
                            if ('' != $mac || !isset($responce[$customerId]['ip_mac'][$ip]['mac'])) {
                                $mac                                         = str_replace('.', '', $mac);
                                $responce[$customerId]['ip_mac'][$ip]['mac'] = $mac;
                            }
                        }
                    }
                    if (0 < $confImportCustomerNasIp || 0 < $confImportCustomerNasPort || 0 < $confImportNasName || 0 < $confImportCustomerNasName) {
                        $nasIp   = $value['nas_ip'];
                        $nasPort = $value['nas_port'];
                        $nasName = $value['nas_name'];
                        if ('' == $nasName) {
                            $nasName = $value['nas_name2'];
                        }
                        if (0 < $confImportCustomerNasIp) {
                            $responce[$customerId]['additional_customer_data'][$confImportCustomerNasIp] = array(
                                'id' => $confImportCustomerNasIp,
                                'value' => $nasIp
                            );
                        }
                        if (0 < $confImportCustomerNasPort) {
                            $responce[$customerId]['additional_customer_data'][$confImportCustomerNasPort] = array(
                                'id' => $confImportCustomerNasPort,
                                'value' => $nasPort
                            );
                        }
                        if (0 < $confImportNasName) {
                            $responce[$customerId]['additional_data'][$confImportNasName] = array(
                                'id' => $confImportNasName,
                                'value' => $nasName
                            );
                        }
                        if (0 < $confImportCustomerNasName) {
                            $responce[$customerId]['additional_customer_data'][$confImportCustomerNasName] = array(
                                'id' => $confImportCustomerNasName,
                                'value' => $nasName
                            );
                        }
                    }
                }
            }
            Log::d("Load Activity From dv_calls. Finish");
        } else {
            $nasArray = array();
            /*
            Log::d("Load NAS Data From internet_log. Start");
            $query    = "
                SELECT
                    dv.uid AS uid,
                    dv.port_id AS nas_port,
                    n.name AS nas_name,
                    INET_NTOA(n.ip) AS nas_ip
                FROM
                    internet_log AS dv
                LEFT JOIN
                    nas AS n
                ON
                    n.id = dv.nas_id
                ORDER BY
                    dv.start";
            $data     = Database::query($query, true, 'uid');
            Log::rawLog('customer_ip', $data);
            foreach ($data as $i => $value) {
                $customerId = $value['uid'];
                if (isset($responce[$customerId])) {
                    if (0 < $confImportCustomerNasIp || 0 < $confImportCustomerNasPort || 0 < $confImportNasName) {
                        $nasIp   = $value['nas_ip'];
                        $nasPort = $value['nas_port'];
                        $nasName = $value['nas_name'];
                        if (0 < $confImportCustomerNasIp) {
                            $responce[$customerId]['additional_data'][$confImportCustomerNasIp] = array(
                                'id' => $confImportCustomerNasIp,
                                'value' => $nasIp
                            );
                        }
                        if (0 < $confImportCustomerNasPort) {
                            $responce[$customerId]['additional_data'][$confImportCustomerNasPort] = array(
                                'id' => $confImportCustomerNasPort,
                                'value' => $nasPort
                            );
                        }
                        if (0 < $confImportNasName) {
                            $responce[$customerId]['additional_data'][$confImportNasName] = array(
                                'id' => $confImportNasName,
                                'value' => $nasName
                            );
                        }
                    }
                }
            }
            Log::d("Load NAS Data From internet_log. Finish");
            */
            if (0 < $confImportCustomerNasIp || 0 < $confImportCustomerNasPort || 0 < $confImportNasName || 0 < $confImportCustomerNasName) {
                Log::d("Load NAS Data From internet_main. Start");
                $query = "
                    SELECT
                        dv.uid AS uid,
                        0 AS nas_port,
                        n.name AS nas_name,
                        INET_NTOA(n.ip) AS nas_ip
                    FROM
                        internet_main AS dv
                    LEFT JOIN
                        nas AS n
                    ON
                        n.id = dv.nas_id
                    WHERE
                        dv.nas_id > 0";
                $data  = Database::query($query, true, 'uid');
                Log::rawLog('customer_ip', $data);
                foreach ($data as $i => $value) {
                    $customerId = $value['uid'];
                    if (isset($responce[$customerId])) {
                        $nasIp   = $value['nas_ip'];
                        $nasPort = $value['nas_port'];
                        $nasName = $value['nas_name'];
                        if (0 < $confImportCustomerNasIp) {
                            if (isset($responce[$customerId]['additional_customer_data'][$confImportCustomerNasIp])) {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasIp]['value'] .= '&#047;' . $nasIp;
                            } else {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasIp] = [
                                    'id' => $confImportCustomerNasIp,
                                    'value' => $nasIp
                                ];
                            }
                        }
                        if (0 < $confImportCustomerNasPort) {
                            $responce[$customerId]['additional_customer_data'][$confImportCustomerNasPort] = [
                                'id' => $confImportCustomerNasPort,
                                'value' => $nasPort
                            ];
                        }
                        if (0 < $confImportNasName) {
                            if (isset($responce[$customerId]['additional_data'][$confImportNasName])) {
                                $responce[$customerId]['additional_data'][$confImportNasName]['value'] .= '&#047;' . $nasName;
                            } else {
                                $responce[$customerId]['additional_data'][$confImportNasName] = array(
                                    'id' => $confImportNasName,
                                    'value' => $nasName
                                );
                            }
                        }
                        if (0 < $confImportCustomerNasName) {
                            if (isset($responce[$customerId]['additional_customer_data'][$confImportCustomerNasName])) {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasName]['value'] .= '&#047;' . $nasName;
                            } else {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasName] = array(
                                    'id' => $confImportCustomerNasName,
                                    'value' => $nasName
                                );
                            }
                        }
                    }
                }
                Log::d("Load NAS Data From internet_main. Finish");
                Log::d("Load NAS Data From internet_online. Start");
                $query = "
                    SELECT
                        dv.uid AS uid,
                        dv.nas_port_id AS nas_port,
                        dv.vlan AS nas_vlan,
                        n.name AS nas_name,
                        INET_NTOA(n.ip) AS nas_ip
                    FROM
                        internet_online AS dv
                    LEFT JOIN
                        nas AS n
                    ON
                        n.id = dv.nas_id
                    WHERE
                        dv.nas_id > 0";
                $data  = Database::query($query, true, 'uid');
                Log::rawLog('customer_ip', $data);
                foreach ($data as $i => $value) {
                    $customerId = $value['uid'];
                    if (isset($responce[$customerId])) {
                        $nasIp   = $value['nas_ip'];
                        $nasPort = $value['nas_port'];
                        $nasName = $value['nas_name'];
                        if (0 < $confImportCustomerNasIp) {
                            if (isset($responce[$customerId]['additional_customer_data'][$confImportCustomerNasIp])) {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasIp]['value'] .= '&#047;' . $nasIp;
                            } else {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasIp] = [
                                    'id' => $confImportCustomerNasIp,
                                    'value' => $nasIp
                                ];
                            }
                        }
                        if (0 < $confImportCustomerNasPort) {
                            if (isset($responce[$customerId]['additional_customer_data'][$confImportCustomerNasPort])) {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasPort]['value'] .= '&#047;' . $nasPort;
                            } else {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasPort] = array(
                                    'id' => $confImportCustomerNasPort,
                                    'value' => $nasPort
                                );
                            }
                        }
                        if (0 < $confImportNasVlan) {
                            $responce[$customerId]['additional_data'][$confImportNasVlan] = array(
                                'id' => $confImportNasVlan,
                                'value' => $value['nas_vlan']
                            );
                        }
                        if (0 < $confImportCustomerNasVlan) {
                            $responce[$customerId]['additional_customer_data'][$confImportCustomerNasVlan] = array(
                                'id' => $confImportCustomerNasVlan,
                                'value' => $value['nas_vlan']
                            );
                        }
                        if (0 < $confImportNasName) {
                            if (isset($responce[$customerId]['additional_data'][$confImportNasName])) {
                                $responce[$customerId]['additional_data'][$confImportNasName]['value'] .= '&#047;' . $nasName;
                            } else {
                                $responce[$customerId]['additional_data'][$confImportNasName] = array(
                                    'id' => $confImportNasName,
                                    'value' => $nasName
                                );
                            }
                        }
                        if (0 < $confImportCustomerNasName) {
                            if (isset($responce[$customerId]['additional_customer_data'][$confImportCustomerNasName])) {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasName]['value'] .= '&#047;' . $nasName;
                            } else {
                                $responce[$customerId]['additional_customer_data'][$confImportCustomerNasName] = array(
                                    'id' => $confImportCustomerNasName,
                                    'value' => $nasName
                                );
                            }
                        }

                    }
                }
                Log::d("Load NAS Data From internet_online. Finish");
            }
        }

        if (1 == $isTableInternetOnline) {
            Log::d("Load Activity/IP From internet_online. Start");
            $query = "
                SELECT
                    concat(uid, '_', framed_ip_address) as id,
                    uid, 
                    started,
                    NOW() AS 'date_activity',
                    framed_ip_address,
                    cid
                FROM
                    internet_online
                WHERE
                    status = 3";
            $data  = Database::query($query, true, 'id');
            Log::rawLog('customer_ip', $data);
            foreach ($data as $i => $value) {
                $customerId = $value['uid'];
                if (isset($responce[$customerId])) {
                    $responce[$customerId]['date_activity'] = $value['date_activity'];

                    $ip = $value['framed_ip_address'];
                    if (0 < $ip) {
                        $mac = str_replace(':', '', $value['cid']);

                        $pos = strpos($mac, '/');
                        if (0 < $pos) {
                            $mac = trim(substr($mac, ($pos + 1)));
                            $pos = strpos($mac, '/');
                            if (0 < $pos) {
                                $tempArray = explode(' ', $mac);
                                $mac       = trim($tempArray[0]);
                            }
                        }

                        if ('000000000000' == $mac) {
                            $mac = '';
                        }

                        //Убираем у прошлого владельца
                        if (isset($ipOwner[$ip])) {
                            $lastCustomerId = $ipOwner[$ip];
                            unset($responce[$lastCustomerId]['ip_mac'][$ip]);
                            if (0 == count($responce[$lastCustomerId]['ip_mac'])) {
                                unset($responce[$lastCustomerId]['ip_mac']);
                            }
                        }
                        $responce[$customerId]['ip_mac'][$ip]['ip'] = $ip;
                        $ipOwner[$ip]                               = $customerId;
                        if ('' != $mac || !isset($responce[$customerId]['ip_mac'][$ip]['mac'])) {
                            $mac                                         = str_replace('.', '', $mac);
                            $responce[$customerId]['ip_mac'][$ip]['mac'] = $mac;
                        }
                    }
                }
            }
            Log::d("Load Activity/IP From internet_online. Finish");
        }

        if (1 != $confIsIpAdrSkipFromDhcpHosts) {
            $data = Database::query("SHOW TABLES LIKE 'dhcphosts_hosts'", false);
            if (isset($data[0])) {
                Log::d("Load Data From 'dhcphosts_hosts'");
                //167837703 - 10.1.0.7
                //167837704 - 10.1.0.8
                //167837705 - 10.1.0.9
                $query = "
                    SELECT
                        uid,
                        ip,
                        UCASE(REPLACE(mac, ':', '')) AS 'mac'
                    FROM
                        dhcphosts_hosts
                    WHERE
                        `ip` NOT IN (167837703, 167837704, 167837705)";
                $data  = Database::query($query, true, 'uid');
                Log::rawLog('customer_ip', $data);
                foreach ($data as $i => $value) {
                    $customerId = $value['uid'];
                    if (isset($responce[$customerId])) {
                        $ip  = trim($value['ip']);
                        $mac = '';
                        if (0 < $ip) {
                            $mac = $value['mac'];
                            if ('000000000000' == $mac) {
                                $mac = '';
                            }
                        }
                        //Убираем у прошлого владельца
                        if (isset($ipOwner[$ip]) && $ipOwner[$ip] != $customerId) {
                            $lastCustomerId = $ipOwner[$ip];
                            unset($responce[$lastCustomerId]['ip_mac'][$ip]);
                            if (0 == count($responce[$lastCustomerId]['ip_mac'])) {
                                unset($responce[$lastCustomerId]['ip_mac']);
                            }
                        }
                        $responce[$customerId]['ip_mac'][$ip]['ip'] = $ip;
                        $ipOwner[$ip]                               = $customerId;
                        if ('' != $mac || !isset($responce[$customerId]['ip_mac'][$ip]['mac'])) {
                            $mac                                         = str_replace('.', '', $mac);
                            $responce[$customerId]['ip_mac'][$ip]['mac'] = $mac;
                        }
                    }
                }
            }
        }

        if (1 == $isTableInternetOnline) {
            Log::d("Load IP/MAC Data From 'internet_main'");
            $query = "
                SELECT
                    uid,
                    ip,
                    UCASE(REPLACE(cid, ':', '')) AS mac
                FROM
                    internet_main";
            $data  = Database::query($query, true, 'uid');
            Log::rawLog('customer_ip', $data);
            foreach ($data as $i => $value) {
                $customerId = $value['uid'];
                if (isset($responce[$customerId])) {
                    $ip  = $value['ip'];
                    $mac = '';
                    if (0 < $ip) {
                        $mac = trim($value['mac']);
                        if ('000000000000' == $mac) {
                            $mac = '';
                        }
                    }
                    //Убираем у прошлого владельца
                    if (isset($ipOwner[$ip]) && $ipOwner[$ip] != $customerId) {
                        $lastCustomerId = $ipOwner[$ip];
                        unset($responce[$lastCustomerId]['ip_mac'][$ip]);
                        if (0 == count($responce[$lastCustomerId]['ip_mac'])) {
                            unset($responce[$lastCustomerId]['ip_mac']);
                        }
                    }
                    $responce[$customerId]['ip_mac'][$ip]['ip'] = $ip;
                    $ipOwner[$ip]                               = $customerId;
                    if ('' != $mac || !isset($responce[$customerId]['ip_mac'][$ip]['mac'])) {
                        $mac                                         = str_replace('.', '', $mac);
                        $responce[$customerId]['ip_mac'][$ip]['mac'] = $mac;
                    }
                }
            }
        }

        //Заявки на смену тарифа
        Log::d("Load Tariff Change Requests. Start");
        $query = "
            SELECT
                uid,
                action AS 'tariff'
            FROM
                shedule
            WHERE
                type = 'tp'";
        $data  = Database::query($query, true, 'uid');
        foreach ($data as $i => $value) {
            $customerId = $value['uid'];
            if (isset($responce[$customerId])) {
                $responce[$customerId]['tariff']['new'][$value['tariff']] = array(
                    'id' => $value['tariff']
                );
            }
        }
        Log::d("Load Tariff Change Requests. Finish");

//dv_main
        if ($isInternetPlus == 1) {
            Log::d("Load internet_main. Start");
            $query = "
                SELECT
                    dm.uid AS uid,
                    t.id AS tariff,
                    dm.disable AS state_id
                FROM
                    internet_main as dm
                LEFT JOIN
                    tarif_plans AS t
                ON
                    t.tp_id = dm.tp_id";
        } else {
            Log::d("Load dv_main. Start");
            $query = "
                SELECT
                    dm.uid AS uid,
                    dm.tp_id AS 'tariff',
                    dm.disable AS 'state_id'
                FROM
                    dv_main as dm";
        }
        $data = Database::query($query, true, 'uid');
        Log::rawLog('customer', $data, 1);
        foreach ($data as $i => $value) {
            $customerId = $value['uid'];
            if (isset($responce[$customerId])) {
                $responce[$customerId]['tariff']['current'][$value['tariff']] = [
                    'id' => $value['tariff']
                ];
                if ('u.disable' != $confUserStateSrc) {
                    $stateId = 2; //Play
                    /*
                    dv_main.disable
                    0 Активно
                    1 Отключено
                    2 Не активизирован
                    3 Приостановление
                    4 Отключено: Неуплата
                    5 Cлишком маленький депозит
                    6.Заблокирован из-за вирусов
                    */
                    switch ($value['state_id']) {
                        case '':
                        case 1:
                        case 4:
                        case 6:
                            $stateId = 0; //stop
                            break;
                        case 3:
                            $stateId = 1; //pause
                            break;
                        case 2:
                            $stateId = $confWorkState2;
                            break;
                        case 5:
                            $stateId = $confWorkState5;
                            break;
                    }
                    $responce[$customerId]['state_id'] = $stateId;
                }
            }
        }
        Log::d("Load *_main. Finish");

        //Догружаем ТВ-тарифы
        $data = Database::query("SHOW TABLES LIKE 'iptv_main'", true);
        if (0 < count($data)) {
            $isIpTvMain = 1;

            Log::d("Load iptv_main. Start");
            $query = "
                SELECT
                    dm.uid AS uid,
                    t.id AS 'iptv_tariff'
                FROM
                    iptv_main as dm
                INNER JOIN
                    tarif_plans AS t
                ON
                    t.tp_id = dm.tp_id";
            $data  = Database::query($query, true, 'uid');
            Log::rawLog('customer', $data, 1);
            foreach ($data as $i => $value) {
                $customerId = $value['uid'];
                if (isset($responce[$customerId])) {
                    $responce[$customerId]['tariff']['current'][$value['iptv_tariff']] = [
                        'id' => $value['iptv_tariff']
                    ];
                }
            }
            Log::d("Load iptv_main. Finish");

        } else {
            $isIpTvMain = 0;
        }

        if (1 == $confIsImportTags) {
            Log::d("Load Tags. Start");
            $query = "
                SELECT
                    CONCAT(uid, '_', tag_id) AS id,
                    uid,
                    tag_id,
                    `date` AS 'date_add'
                FROM
                    tags_users";
            $data  = Database::query($query, true, 'id');
            Log::rawLog('tags', $data);
            foreach ($data as $i => $value) {
                $customerId = $value['uid'];
                if (isset($responce[$customerId])) {
                    $responce[$customerId]['tag'][$value['tag_id']] = array(
                        'id' => $value['tag_id'],
                        'date_add' => $value['date_add']
                    );
                }
            }
            Log::d("Load Tags. Finish");
        }

        Log::d(json_encode($responce));
        Log::d(serialize($responce));
        return $responce;
    }

    public function getPotentialCustomerList()
    {
        Log::d("Load Potential Customer");
        $responce = array();
        $data     = Database::query("SHOW TABLES LIKE 'msgs_unreg_requests'", true);
        if (0 < count($data)) {
            $isTablePotential = 1;
        } else {
            $isTablePotential = 0;
        }
        if (1 == $isTablePotential) {
            $query = "
            SELECT
                mur.id AS 'id',
                mur.fio AS 'full_name',
                mur.comments AS 'comment',
                mur.phone AS 'phone',
                mur.email AS 'email',
                mur.login AS 'login',
                mur.datetime AS 'date_create',
                mur.address_flat AS 'apartment'
            FROM
                msgs_unreg_requests AS mur";
            $data  = Database::query($query, true, 'id');
            Log::rawLog('customer', $data, 1);
            foreach ($data as $i => $value) {
                $customerId            = $value['id'];
                $responce[$customerId] = array(
                    'id' => $customerId,
                    'login' => $value['login'],
                    'full_name' => trim($value['full_name']),
                    'comment' => $value['comment'],
                    'date_create' => $value['date_create'],
                    'email' => array(
                        0 => array(
                            'address' => $value['email']
                        )
                    ),
                    'address' => array(
                        0 => array(
                            'type' => 'connect',
                            'house_id' => 0,
                            'apartment' => array(
                                'number' => $value['apartment']
                            )
                        )
                    ),
                    'phone' => array(
                        0 => array(
                            'flag_main' => 1,
                            'number' => $value['phone']
                        ),
                        1 => array(
                            'number' => ''
                        )
                    )
                );
            }
            Log::d(json_encode($responce));
        } else {
            Log::d("Table not found");
        }
        return $responce;
    }

    function getDataFromBilling($additionalUrl)
    {
        global $billingUrl, $moduleHelper;
        $api = new Api();
        ++$this->counterApiBilling;
        $this->billingApiPath = $billingUrl;
        $url                  = $this->billingApiPath . $additionalUrl;
        $result               = $api->readFromUrl($url);
        //echo '|'.$result.'|';
        if ('' == $result) {
            Log::c('Error: Empty Responce From Billing API');
            $moduleHelper->finishModule();
        }
        return json_decode($result, true);
    }

}

// ---- 1.11.268

$moduleCoreRevision = '268';

$moduleHelper = new ModuleHelper();
$api          = new Api();

$customer = new Customer();

if (!isset($moduleVersion)) {
    $moduleVersion = '3';
}

$moduleVersion .= '.' . $moduleCoreRevision;

//Запуск модуля. Начальные операции
$moduleHelper->initializeModule();

//Обработка тарифных планов
Log::w("Tariff. Start");
$tariff        = new Tariff();
$tarifBilling  = $tariff->loadFromBilling();
$tarifUserside = $tariff->loadFromUserside();
$tariff->compareTariff($tarifBilling, $tarifUserside);
Log::w("Tariff. Finish");

//Обработка услуг
Log::w("Service. Start");
$service         = new Service();
$serviceBilling  = $service->loadFromBilling();
$serviceUserside = $service->loadFromUserside();
$service->compareService();
Log::w("Service. Finish");

//Обработка абонентов
Log::w("Customer. Start");
$userBilling = $customer->loadFromBilling();
[$userUserside, $userUsersideCrc] = $customer->loadFromUserside();
$customer->compareCustomer($userBilling, $userUserside, $userUsersideCrc);
//Потенциальные абоненты - пока отключено
//$userBilling = $customer->loadPotentialFromBilling();
Log::w("Customer. Finish");

Log::w("System Operation");
$moduleHelper->systemOperation();

// Завершение работы модуля
$moduleHelper->finishModule();

class Address
{

    static function compareHouseInUserside($bundle)
    {
        global $api, $confIsDisableCreateAddress, $billingCrcId;
        Log::d("Compare buildings in UserSide. Count: " . count($bundle));

        $json = $api->sendDataToUserside("&cat=address&action=compare_house&is_disable_create=" . $confIsDisableCreateAddress . "&billing_crc_id=" . $billingCrcId . "&data=" . urlencode(json_encode($bundle,
                JSON_UNESCAPED_UNICODE)));
        if (
            (
                isset($json['Result'])
                &&
                'OK' == $json['Result']
            )
            ||
            (
                isset($json['result'])
                &&
                'OK' == $json['result']
            )
        ) {
            Log::d("Compare OK");
        } else {
            Log::d("Compare ERROR");
            $json['Data'] = [];
            $json['data'] = [];
        }
        return $json;
    }

    static function addCityToUserside($array)
    {
        global $api;
        $name = $array['name'];
        Log::d("Add City To UserSide. Name: '" . $name . "'");
        $json = $api->sendDataToUserside("&cat=address&action=add_city&name=" . $name);
        if (isset($json['Id'])) {
            Log::d("Adding OK. Id: " . $json['Id']);
            return $json['Id'];
        }

        if (isset($json['id'])) {
            Log::d("Adding OK. id: " . $json['id']);
            return $json['id'];
        }

        Log::d("Adding ERROR");
        return 0;
    }

    static function addStreetToUserside($array)
    {
        global $api;
        $name   = $array['name'];
        $cityId = $array['city_id'];
        Log::d("Add Street To UserSide. CityId: '" . $cityId . "' Name: '" . $name . "'");
        $bundle = [
            'cat' => 'address',
            'action' => 'add_street',
            'city_id' => $cityId,
            'name' => $name
        ];
        $json   = $api->sendDataToUserside("&cat=address&action=add_street&city_id=" . $cityId . "&name=" . $name,
            $bundle);
        if (isset($json['Id'])) {
            Log::d("Adding OK. Id: " . $json['Id']);
            return $json['Id'];
        } elseif (isset($json['id'])) {
            Log::d("Adding OK. id: " . $json['id']);
            return $json['id'];
        } else {
            Log::d("Adding ERROR");
            return 0;
        }
    }

    static function addHouseToUserside($array)
    {
        global $api;
        $cityId   = $array['city_id'];
        $streetId = $array['street_id'];
        $number   = $array['number'];
        $block    = $array['block'];
        $entrance = $array['entrance'] ?? 0;
        $floor    = $array['floor'] ?? 0;
        Log::d("Add building to UserSide. CityId: '" . $cityId . "' StreetId: '" . $streetId . "' Number: '" . $number . "' Block: '" . $block . "'");

        $url = "&cat=address&action=add_house&city_id=" . $cityId . "&street_id=" . $streetId . "&number=" . $number . "&floor=" . $floor . "&entrance=" . $entrance . "&block=" . $block;

        //echo $url . "\n";

        $json = $api->sendDataToUserside($url);
        if (isset($json['Id'])) {
            Log::d("Adding OK. Id: " . $json['Id']);
            return $json['Id'];
        }

        if (isset($json['id'])) {
            Log::d("Adding OK. id: " . $json['id']);
            return $json['id'];
        }

        Log::d("Adding ERROR");
        return 0;
    }

    static function editHouseInUserside($houseId, $array)
    {
        global $api;
        Log::d("Edit building in UserSide. BuildingId: " . $houseId);
        $url  = "&cat=address&action=edit_house&id=" . $houseId . "&data=" . json_encode($array,
                JSON_UNESCAPED_UNICODE);
        $json = $api->sendDataToUserside($url);
        if (isset($json['Id'])) {
            Log::d("Editing OK");
        } elseif (isset($json['id'])) {
            Log::d("Editing OK");
        } else {
            Log::d("Editing ERROR");
        }
    }

}

class Customer
{
    private $arrayCustomerToAdd        = [];
    private $arrayCustomerToEdit       = [];
    private $currentUserSideData       = [];
    private $currentBillingData        = [];
    private $arrayBillingState         = [];
    private $arrayBillingHouse         = [];
    private $arrayBillingTags          = [];
    private $arrayBillingGroup         = [];
    private $arrayBillingMsg           = [];
    public  $isLoadCustomerCommutation = 0;

    private function houseProcessing($customerInputArray)
    {
        global $api, $moduleHelper, $billingName, $billing, $confIsDisableCreateAddress, $isNewHouseBillingCompare;
        global $confIsSkipUnusedAddress, $addressCompareFormat, $apiGroupHouseCounter;
        global $confIsUseStreetFullName, $customerArray;

        $customerArray = $customerInputArray;

        //Обработка домов из биллинга и сравнение с UserSide
        Log::w("Building processing");

        Log::w("Load Province From Billing");
        if ('standart' == $billingName) {
            $billingProvince = [];
        } elseif ('carbon5' == $billingName) {
            $billingProvince = [];
        } elseif ('abills' == $billingName) {
            $billingProvince = [];
        } else {
            $billingProvince = $billing->getProvinceList();
        }
        Log::w("Count: " . count($billingProvince));

        Log::w("Load District From Billing");
        if ('standart' == $billingName) {
            $billingDistrict = [];
        } elseif ('carbon5' == $billingName) {
            $billingDistrict = [];
        } elseif ('abills' == $billingName) {
            $billingDistrict = [];
        } else {
            $billingDistrict = $billing->getDistrictList();
        }
        Log::w("Count: " . count($billingProvince));

        Log::w("Load City From Billing");

        if ('standart' == $billingName) {
            $billingCity = $api->getDataFromBilling("&request=get_city_list", 1);
        } elseif ('carbon5' == $billingName) {
            $billingCity = $api->getDataFromBilling("&method1=userside_manager.get_city_list", 1);
        } elseif ('abills' == $billingName) {
            $billingCity = [];
        } else {
            $billingCity = $billing->getCityList();
        }

        Log::w("Count: " . count($billingCity));

        Log::w("Load Area From Billing");
        if ('standart' == $billingName) {
            $billingArea = [];
        } elseif ('carbon5' == $billingName) {
            $billingArea = [];
        } elseif ('abills' == $billingName) {
            $billingArea = [];
        } else {
            $billingArea = $billing->getAreaList();
        }
        Log::w("Count: " . count($billingArea));

        Log::w("Load Street From Billing");
        if ('standart' == $billingName) {
            $billingStreet = $api->getDataFromBilling("&request=get_street_list", 1);
        } elseif ('carbon5' == $billingName) {
            $billingStreet = $api->getDataFromBilling("&method1=userside_manager.get_street_list", 1);
        } elseif ('abills' == $billingName) {
            $billingStreet = [];
        } else {
            $billingStreet = $billing->getStreetList();
        }
        Log::w("Count: " . count($billingStreet));

        Log::w("Load buildings from billing");
        if ('standart' == $billingName) {
            $billingHouse = $api->getDataFromBilling("&request=get_house_list", 1);
        } elseif ('carbon5' == $billingName) {
            $billingHouse = $api->getDataFromBilling("&method1=userside_manager.get_house_list", 1);
        } elseif ('abills' == $billingName) {
            $billingHouse = [];
        } else {
            $billingHouse = $billing->getHouseList();
        }
        Log::w("Count: " . count($billingHouse));

        //Откидываем неиспользуемые адреса
        if ($confIsSkipUnusedAddress == 1) {
            foreach ($customerArray as $i => $value) {
                if (isset($value['address'][0]['house_id'])) {
                    $houseId = $value['address'][0]['house_id'];
                    if (isset($billingHouse[$houseId])) {
                        $billingHouse[$houseId]['_is_use'] = true;
                    }
                }
            }
            foreach ($billingHouse as $id => $value) {
                if (!isset($value['_is_use'])) {
                    unset($billingHouse[$id]);
                } else {
                    $streetId = $value['street_id'];
                    if (isset($billingStreet[$streetId])) {
                        $billingStreet[$streetId]['_is_use'] = true;
                    }
                }
            }
            foreach ($billingStreet as $id => $value) {
                if (!isset($value['_is_use'])) {
                    unset($billingStreet[$id]);
                } else {
                    $areaId = $value['area_id'] ?? '';
                    if (isset($billingArea[$areaId])) {
                        $billingArea[$areaId]['_is_use'] = true;
                    }
                    $cityId = $value['city_id'];
                    if (isset($billingCity[$cityId])) {
                        $billingCity[$cityId]['_is_use'] = true;
                    }
                }
            }
            foreach ($billingArea as $id => $value) {
                if (!isset($value['_is_use'])) {
                    unset($billingArea[$id]);
                } else {
                    $cityId = $value['city_id'] ?? '';
                    if (isset($billingCity[$cityId])) {
                        $billingCity[$cityId]['_is_use'] = true;
                    }
                }
            }
            foreach ($billingCity as $id => $value) {
                if (!isset($value['_is_use'])) {
                    unset($billingCity[$id]);
                } else {
                    $districtId = $value['district_id'] ?? '';
                    if (isset($billingDistrict[$districtId])) {
                        $billingDistrict[$districtId]['_is_use'] = true;
                    }
                    $provinceId = $value['province_id'] ?? '';
                    if (isset($billingProvince[$provinceId])) {
                        $billingProvince[$provinceId]['_is_use'] = true;
                    }
                }
            }
            foreach ($billingDistrict as $id => $value) {
                if (!isset($value['_is_use'])) {
                    unset($billingDistrict[$id]);
                } else {
                    $provinceId = $value['province_id'] ?? '';
                    if (isset($billingProvince[$provinceId])) {
                        $billingProvince[$provinceId]['_is_use'] = true;
                    }
                }
            }
            foreach ($billingProvince as $id => $value) {
                if (!isset($value['_is_use'])) {
                    unset($billingProvince[$id]);
                }
            }
        }

        //Пробуем в UserSide загрузить дома по новому алгоритму
        if (1 == $isNewHouseBillingCompare) {
            Log::w("Try new buildings compare by UserSide");
            //Обобщаем массив адресов
            $arrayCompare = $billingHouse;
            foreach ($arrayCompare as $i => $value) {
                if (!isset($value['_userside_id'])) {
                    $street     = $billingStreet[$value['street_id']];
                    $cityId     = $street['city_id'];
                    $cityName   = $billingCity[$cityId]['name'] ?? '';
                    $streetName = isset($street['name']) ? trim($street['name']) : '';
                    if ('' == $streetName) {
                        $streetName = isset($street['full_name']) ? trim($street['full_name']) : '';
                    }

                    $provinceName = '';
                    $districtName = '';
                    $areaName     = '';
                    if (isset($billingCity[$cityId])) {
                        $areaId     = $billingStreet[$value['street_id']]['area_id'] ?? '';
                        $provinceId = $billingCity[$cityId]['province_id'] ?? '';
                        $districtId = $billingCity[$cityId]['district_id'] ?? '';
                        if (
                            $provinceId == ''
                            &&
                            $districtId != ''
                        ) {
                            if (isset($billingDistrict[$districtId])) {
                                $provinceId = isset($billingDistrict[$districtId]['province_id']) ? $billingDistrict[$districtId]['province_id'] : '';
                            }
                        }
                        $districtName = $billingDistrict[$districtId]['name'] ?? '';
                        $provinceName = $billingProvince[$provinceId]['name'] ?? '';
                        $areaName     = $billingArea[$areaId]['name'] ?? '';

                        $districtName = str_replace(
                            [
                                '&',
                                '#'
                            ], '', $districtName);

                        $provinceName = str_replace(
                            [
                                '&',
                                '#'
                            ], '', $provinceName);

                        $areaName = str_replace('&', '', $areaName);
                        $areaName = str_replace('#', '', $areaName);
                    }

                    $streetName = str_replace('&', '', $streetName);
                    $streetName = str_replace('#', '', $streetName);
                    $cityName   = str_replace('&', '', $cityName);
                    $cityName   = str_replace('#', '', $cityName);

                    if ($addressCompareFormat == 1) {
                        if ($provinceName != '') {
                            $arrayCompare[$i]['p'] = $provinceName;
                        }
                        if ($districtName != '') {
                            $arrayCompare[$i]['d'] = $districtName;
                        }
                        if ($areaName != '') {
                            $arrayCompare[$i]['a'] = $areaName;
                        }
                        $arrayCompare[$i]['c'] = $cityName;
                        $arrayCompare[$i]['s'] = $streetName;
                    } else {
                        $arrayCompare[$i]['province'] = $provinceName;
                        $arrayCompare[$i]['district'] = $districtName;
                        $arrayCompare[$i]['city']     = $cityName;
                        $arrayCompare[$i]['area']     = $areaName;
                        $arrayCompare[$i]['street']   = $streetName;
                    }
                    if (isset($arrayCompare[$i]['block'])) {
                        $block = $arrayCompare[$i]['block'];
                        $block = str_replace('&', '', $block);
                        $block = str_replace('#', '', $block);
                        if ($addressCompareFormat == 1) {
                            if ('' != $block) {
                                $arrayCompare[$i]['b'] = $block;
                            }
                            unset($arrayCompare[$i]['block']);
                        } else {
                            $arrayCompare[$i]['block'] = $block;
                        }
                    }
                    if (isset($arrayCompare[$i]['number'])) {
                        $arrayCompare[$i]['number'] = trim($arrayCompare[$i]['number']);
                        if ($addressCompareFormat == 1) {
                            if ('' != $arrayCompare[$i]['number']) {
                                $arrayCompare[$i]['n'] = $arrayCompare[$i]['number'];
                            }
                            unset($arrayCompare[$i]['number']);
                        }
                    }
                    if (
                        isset($arrayCompare[$i]['postcode'])
                        &&
                        $addressCompareFormat == 1
                    ) {
                        if ('' != $arrayCompare[$i]['postcode']) {
                            $arrayCompare[$i]['o'] = $arrayCompare[$i]['postcode'];
                        }
                        unset($arrayCompare[$i]['postcode']);
                    }
                    if (isset($arrayCompare[$i]['street_id'])) {
                        unset($arrayCompare[$i]['street_id']);
                    }
                    if (isset($arrayCompare[$i]['full_name'])) {
                        unset($arrayCompare[$i]['full_name']);
                    }
                }
            }

            //Проверяем дома по 10000
            $tempCount     = 0;
            $arrayCompare2 = [];
            if ($apiGroupHouseCounter < 10) {
                $apiGroupHouseCounter = 10000;
            }
            foreach ($arrayCompare as $i => $value) {
                if (!isset($value['_userside_id'])) {
                    ++$tempCount;
                    $arrayCompare2[$i] = $value;
                    if (0 == fmod($tempCount, $apiGroupHouseCounter)) {
                        $usResponceRaw = Address::compareHouseInUserside($arrayCompare2);
                        $usResponce    = isset($usResponceRaw['Data']) ? $usResponceRaw['Data'] : $usResponceRaw['data'];
                        foreach ($usResponce as $j => $value2) {
                            if (isset($billingHouse[$j])) {
                                $billingHouse[$j]['_userside_id'] = $value2;
                                if ($value2 < 1) {
                                    Log::d("Can't add building  : " . json_encode($billingHouse[$j]));
                                }
                            }
                        }
                        if (isset($usResponce[-1])) {
                            if (isset($usResponce[-1]['province'])) {
                                Log::d("Add Province : " . $usResponce[-1]['province']);
                            }
                            if (isset($usResponce[-1]['district'])) {
                                Log::d("Add District : " . $usResponce[-1]['district']);
                            }
                            if (isset($usResponce[-1]['city'])) {
                                Log::d("Add City     : " . $usResponce[-1]['city']);
                            }
                            if (isset($usResponce[-1]['area'])) {
                                Log::d("Add Area     : " . $usResponce[-1]['area']);
                            }
                            if (isset($usResponce[-1]['street'])) {
                                Log::d("Add Street   : " . $usResponce[-1]['street']);
                            }
                            if (isset($usResponce[-1]['house'])) {
                                Log::d("Add building    : " . $usResponce[-1]['house']);
                            }
                        }
                        $tempCount     = 0;
                        $arrayCompare2 = [];
                    }
                }
            }
            if ($tempCount > 0) {
                $usResponceRaw = Address::compareHouseInUserside($arrayCompare2);
                $usResponce    = isset($usResponceRaw['Data']) ? $usResponceRaw['Data'] : $usResponceRaw['data'];
                foreach ($usResponce as $j => $value2) {
                    if (isset($billingHouse[$j])) {
                        $billingHouse[$j]['_userside_id'] = $value2;
                        if ($value2 < 1) {
                            Log::d("Can't add building  : " . json_encode($billingHouse[$j]));
                        }
                    }
                }
                if (isset($usResponce[-1])) {
                    if (isset($usResponce[-1]['province'])) {
                        Log::d("Add Province : " . $usResponce[-1]['province']);
                    }
                    if (isset($usResponce[-1]['district'])) {
                        Log::d("Add District : " . $usResponce[-1]['district']);
                    }
                    if (isset($usResponce[-1]['city'])) {
                        Log::d("Add City     : " . $usResponce[-1]['city']);
                    }
                    if (isset($usResponce[-1]['area'])) {
                        Log::d("Add Area     : " . $usResponce[-1]['area']);
                    }
                    if (isset($usResponce[-1]['street'])) {
                        Log::d("Add Street   : " . $usResponce[-1]['street']);
                    }
                    if (isset($usResponce[-1]['house'])) {
                        Log::d("Add building    : " . $usResponce[-1]['house']);
                    }
                }
            }

        }

        //Старый метод добавления адресов
        if (1 != $isNewHouseBillingCompare) {
            Log::w("Load buildings from UserSide");
            $usersideHouse = $api->getDataFromUserside("&request=get_house_list&is_id_address=3");
            if (!is_array($usersideHouse) || 1 > count($usersideHouse)) {
                Log::i("Error load buildings from UserSide");
                $moduleHelper->finishModule();
            } else {
                Log::w("Count: " . count($usersideHouse));
            }

            $isLoadAddressDataFromUserside = 0;
            $emptyCityArray                = [];
            if (is_array($billingHouse) && 0 < count($billingHouse)) {
                Log::w("Compare House");
                foreach ($billingHouse as $id => $value) {
                    if (!isset($value['_userside_id'])) {
                        $streetId   = $value['street_id'];
                        $street     = $billingStreet[$value['street_id']];
                        $cityId     = $street['city_id'];
                        $cityName   = isset($billingCity[$cityId]['name']) ? $billingCity[$cityId]['name'] : '';
                        $postCode   = isset($value['postcode']) ? floor($value['postcode']) : '';
                        $floor      = isset($value['floor']) ? floor($value['floor']) : 0;
                        $entrance   = isset($value['entrance']) ? floor($value['entrance']) : 0;
                        $streetName = isset($street['name']) ? trim($street['name']) : '';
                        if (
                            '' == $streetName
                            ||
                            (
                                1 == $confIsUseStreetFullName
                                &&
                                isset($street['full_name'])
                                &&
                                '' != $street['full_name']
                            )
                        ) {
                            $streetName = isset($street['full_name']) ? trim($street['full_name']) : '';
                        }
                        $houseNumber = isset($value['number']) ? trim($value['number']) : '';
                        $houseBlock  = isset($value['block']) ? trim($value['block']) : '';
                        if ('' != $houseBlock) {
                            if (0 < floor($houseBlock)) {
                                $houseNumber .= '-';
                            } else {
                                $houseNumber .= ' ';
                            }
                            /*
                            if ('0' == $houseNumber && '0' == substr($houseNumber, 0, 1)) {
                                $houseNumber = '';
                            }
                            */
                            $houseNumber .= $houseBlock;
                            if (mb_substr($houseNumber, 0, 2) == '0-' || mb_substr($houseNumber, 0, 2) == '0 ') {
                                $houseNumber = trim(mb_substr($houseNumber, 2, 100));
                            }
                        }
                        $usHouseId = 0;
                        //Если пустой город - то пробуем найти хоть в каком-то городе эту улицу
                        if ('' == $cityName && isset($emptyCityArray[$streetName])) {
                            $cityName = $emptyCityArray[$streetName];
                        }
                        if ('' == $cityName) {

                            //Загружаем массивы с адресами из UserSide
                            if (1 != $isLoadAddressDataFromUserside) {
                                Log::w("Load City From UserSide");
                                $usersideCity = $api->getDataFromUserside("&request=get_city_list&is_id_address=1");
                                if (!is_array($usersideCity) || 1 > count($usersideCity)) {
                                    Log::i("Error Load City From UserSide");
                                    $moduleHelper->finishModule();
                                } else {
                                    Log::w("Count: " . count($usersideCity));
                                    foreach ($usersideCity as $i => $tempValue) {
                                        if (extension_loaded('mbstring')) {
                                            $upperCityName = mb_strtoupper(ModuleHelper::replaceNonUtfSymbol($i));
                                        } else {
                                            $upperCityName = strtoupper(ModuleHelper::replaceNonUtfSymbol($i));
                                        }
                                        if (!isset($usersideCity[$upperCityName])) {
                                            $usersideCity[$upperCityName] = $tempValue;
                                        }
                                    }
                                }
                                Log::w("Load Street From UserSide");
                                $usersideStreet = $api->getDataFromUserside("&request=get_street_list&is_id_address=2");
                                if (!is_array($usersideStreet) || 1 > count($usersideStreet)) {
                                    Log::i("Error Load Street From UserSide");
                                    $moduleHelper->finishModule();
                                } else {
                                    Log::w("Count: " . count($usersideStreet));
                                    foreach ($usersideStreet as $i => $tempValue) {
                                        if (extension_loaded('mbstring')) {
                                            $upperStreetName = mb_strtoupper(ModuleHelper::replaceNonUtfSymbol($i));
                                        } else {
                                            $upperStreetName = strtoupper(ModuleHelper::replaceNonUtfSymbol($i));
                                        }
                                        if (!isset($usersideStreet[$upperStreetName])) {
                                            $usersideStreet[$upperStreetName] = $tempValue;
                                        }
                                    }
                                }
                                $isLoadAddressDataFromUserside = 1;
                            }

                            //Ищем - есть ли улица нужная
                            foreach ($usersideStreet as $i => $value3) {
                                if (isset($value3['name']) && $value3['name'] == $streetName) {
                                    $usCityId = $value3['city_id'];
                                    foreach ($usersideCity as $j => $value2) {
                                        if ($value2['id'] == $usCityId) {
                                            $cityName                    = $value2['name'];
                                            $emptyCityArray[$streetName] = $cityName;
                                            break;
                                        }
                                    }
                                    Log::d("Find City '" . $usCityId . "' - '" . $cityName . "' For Street '" . $streetName . "'");
                                    break;
                                }
                            }
                        }
                        if ('' == $cityName) {
                            $cityName = '-';
                        } else {
                            $cityName = str_replace("'", '&#039;', $cityName);
                        }

                        $address = $cityName;
                        if ('' != $streetName) {
                            $streetName = str_replace("'", '&#039;', $streetName);
                            if ('' != $address) {
                                $address .= ', ' . $streetName;
                            }
                        }
                        if ('' != $houseNumber && (1 != mb_strlen($houseNumber) || '0' != $houseNumber)) {
                            if ('' != $address) {
                                $address .= ', ' . $houseNumber;
                            }
                        }
                        $billingHouse[$id]['_address'] = $address;

                        $streetNameRaw = ModuleHelper::replaceNonUtfSymbol($streetName);
                        if (extension_loaded('mbstring')) {
                            $streetNameRaw2 = mb_strtoupper(ModuleHelper::replaceNonUtfSymbol($streetNameRaw));
                        } else {
                            $streetNameRaw2 = $streetNameRaw;
                        }

                        if ('' != $cityName) {
                            //Проверяем - есть ли такой дом в UserSide
                            $address = str_replace("'", '&#039;', $address);
                            $address = str_replace("/", '&#047;', $address);
                            $address = str_replace("&#047;", '-', $address);
                            //$address             = str_replace(", 0-", ', 0 ', $address); //Olivenet - "MARBELLA, Urb. Mirasierra, 0-8"
                            //$address             = str_replace(", 0 ", ', ', $address); //Olivenet - "MARBELLA, Urb. Mirasierra, 0 8"
                            $addressUpperConvert = strtoupper(ModuleHelper::replaceNonUtfSymbol($address));
                            if (extension_loaded('mbstring')) {
                                $addressUpperConvert2 = mb_strtoupper(ModuleHelper::replaceNonUtfSymbol($address));
                            } else {
                                $addressUpperConvert2 = $addressUpperConvert;
                            }
                            $addressRaw  = md5($addressUpperConvert);
                            $addressRaw2 = md5($addressUpperConvert . ', ');
                            $addressRaw3 = md5($addressUpperConvert2);
                            $addressRaw4 = md5($addressUpperConvert2 . ', ');

                            $houseUSIndex = '';
                            if (isset($usersideHouse[$addressRaw])) {
                                $usHouseId    = $usersideHouse[$addressRaw]['id'];
                                $houseUSIndex = $addressRaw;
                            } elseif (isset($usersideHouse[$addressRaw2])) {
                                $usHouseId    = $usersideHouse[$addressRaw2]['id'];
                                $houseUSIndex = $addressRaw2;
                            } elseif (isset($usersideHouse[$addressRaw3])) {
                                $usHouseId    = $usersideHouse[$addressRaw3]['id'];
                                $houseUSIndex = $addressRaw3;
                            } elseif (isset($usersideHouse[$addressRaw4])) {
                                $usHouseId    = $usersideHouse[$addressRaw4]['id'];
                                $houseUSIndex = $addressRaw4;
                            } else {
                                //Нет дома
                                Log::d("Not find building '" . $address . "|" . $addressUpperConvert . "|" . $addressUpperConvert2 . "' - SearchId: '" . $addressRaw . "|" . $addressRaw2 . "|" . $addressRaw3 . "|" . $addressRaw4 . "' Billing id: '" . $id . "' street_id: '" . $streetId . "' - '" . $streetName . "' city_id: '" . $cityId . "' - '" . $cityName . "'");
                                //Загружаем массивы с адресами из UserSide
                                if (1 != $isLoadAddressDataFromUserside) {
                                    Log::w("Load City From UserSide");
                                    $usersideCity = $api->getDataFromUserside("&request=get_city_list&is_id_address=1");
                                    if (!is_array($usersideCity) || 1 > count($usersideCity)) {
                                        Log::i("Error Load City From UserSide");
                                        $moduleHelper->finishModule();
                                    } else {
                                        Log::w("Count: " . count($usersideCity));
                                        foreach ($usersideCity as $i => $tempValue) {
                                            if (extension_loaded('mbstring')) {
                                                $upperCityName = mb_strtoupper(ModuleHelper::replaceNonUtfSymbol($i));
                                            } else {
                                                $upperCityName = strtoupper(ModuleHelper::replaceNonUtfSymbol($i));
                                            }
                                            if (!isset($usersideCity[$upperCityName])) {
                                                $usersideCity[$upperCityName] = $tempValue;
                                            }
                                        }
                                    }
                                    Log::w("Load Street From UserSide");
                                    $usersideStreet = $api->getDataFromUserside("&request=get_street_list&is_id_address=2");
                                    if (!is_array($usersideStreet) || 1 > count($usersideStreet)) {
                                        Log::i("Error Load Street From UserSide");
                                        $moduleHelper->finishModule();
                                    } else {
                                        Log::w("Count: " . count($usersideStreet));
                                        foreach ($usersideStreet as $i => $tempValue) {
                                            if (extension_loaded('mbstring')) {
                                                $upperStreetName = mb_strtoupper(ModuleHelper::replaceNonUtfSymbol($i));
                                            } else {
                                                $upperStreetName = strtoupper(ModuleHelper::replaceNonUtfSymbol($i));
                                            }
                                            if (!isset($usersideStreet[$upperStreetName])) {
                                                $usersideStreet[$upperStreetName] = $tempValue;
                                            }
                                        }
                                    }
                                    $isLoadAddressDataFromUserside = 1;
                                }

                                //Ищем - есть ли город нужный
                                if (isset($usersideCity[$cityName])) {
                                    $usCityId = $usersideCity[$cityName]['id'];
                                } elseif (isset($usersideCity[ModuleHelper::replaceNonUtfSymbol($cityName)])) {
                                    $usCityId = $usersideCity[ModuleHelper::replaceNonUtfSymbol($cityName)]['id'];
                                } else {
                                    //Создаём новый населённый пункт
                                    if ($confIsDisableCreateAddress != 1) {
                                        $usCityId = Address::addCityToUserside([
                                            'name' => $cityName
                                        ]);
                                        if (0 < $usCityId) {
                                            $usersideCity[$cityName] = [
                                                'id' => $usCityId
                                            ];
                                        }
                                    } else {
                                        $usCityId = 0;
                                    }
                                }

                                //Ищем - есть ли улица нужная
                                if (isset($usersideStreet[$usCityId . '_' . $streetNameRaw])) {
                                    $usStreetId = $usersideStreet[$usCityId . '_' . $streetNameRaw]['id'];
                                } elseif (isset($usersideStreet[$usCityId . '_' . $streetNameRaw2])) {
                                    $usStreetId = $usersideStreet[$usCityId . '_' . $streetNameRaw2]['id'];
                                } else {
                                    //Создаём новую улицу
                                    Log::d("Not find Street '" . $streetName . "' - us_city_id: '" . $usCityId . "' - '" . $cityName . "'");
                                    if ($confIsDisableCreateAddress != 1) {
                                        $usStreetId = Address::addStreetToUserside(
                                            [
                                                'city_id' => $usCityId,
                                                'name' => $streetName
                                            ]
                                        );
                                    } else {
                                        $usStreetId = 0;
                                    }
                                    if (0 < $usStreetId) {
                                        $usersideStreet[$usCityId . '_' . $streetName] = [
                                            'id' => $usStreetId
                                        ];
                                    }
                                }
                                //Добавляем дом
                                if (0 < $usStreetId && $confIsDisableCreateAddress != 1) {
                                    $houseBlock  = str_replace("'", '&#039;', $houseBlock);
                                    $houseNumber = isset($value['number']) ? $value['number'] : '';
                                    $usHouseId   = Address::addHouseToUserside(
                                        [
                                            'city_id' => $usCityId,
                                            'street_id' => $usStreetId,
                                            'number' => $houseNumber,
                                            'block' => $houseBlock,
                                            'postcode' => $postCode
                                        ]
                                    );
                                }

                                if (0 < $usHouseId) {
                                    $usersideHouse[md5($address)] = [
                                        'id' => $usHouseId,
                                        'postcode' => $postCode
                                    ];
                                    $houseUSIndex                 = md5($address);
                                }
                            }

                            if (0 < $usHouseId) {
                                $houseUsArray = $usersideHouse[$houseUSIndex];
                                //Проверяем правильность данных
                                $houseUsArray['postcode'] = $houseUsArray['postcode'] ?? '';
                                $houseUsArray['entrance'] = $houseUsArray['entrance'] ?? 0;
                                $houseUsArray['floor']    = $houseUsArray['floor'] ?? 0;
                                $isChange                 = 0;
                                $array                    = [];
                                if ($postCode != $houseUsArray['postcode'] && '' != $postCode) {
                                    $isChange                                 = 1;
                                    $array['postcode']                        = $postCode;
                                    $usersideHouse[$houseUSIndex]['postcode'] = $postCode;
                                }
                                if ($entrance != $houseUsArray['entrance'] && 0 < $entrance) {
                                    $isChange                                 = 1;
                                    $array['entrance']                        = $entrance;
                                    $usersideHouse[$houseUSIndex]['entrance'] = $entrance;
                                }
                                if ($floor != $houseUsArray['floor'] && 0 < $floor) {
                                    $isChange                              = 1;
                                    $array['floor']                        = $floor;
                                    $usersideHouse[$houseUSIndex]['floor'] = $floor;
                                }
                                if (1 == $isChange) {
                                    //Изменяем
                                    Address::editHouseInUserside($usHouseId, $array);
                                }
                            }
                        }
                        $billingHouse[$id]['_userside_id'] = $usHouseId;
                    }
                }
            }
        }

        $this->arrayBillingHouse = $billingHouse;

        return $customerArray;
    }

    public function loadFromBilling()
    {
        global $api, $isUpdateAddress, $billingName, $billing, $moduleHelper, $confIsImportMessage;
        Log::w("Load User State From Billing");
        if ('standart' == $billingName) {
            $this->arrayBillingState = $api->getDataFromBilling("&request=get_user_state_list", 1);
        } elseif ('carbon5' == $billingName) {
            $this->arrayBillingState = $api->getDataFromBilling("&method1=userside_manager.get_user_state_list", 1);
        } elseif ('abills' == $billingName) {
            $this->arrayBillingState = [];
        } else {
            $this->arrayBillingState = $billing->getBillingState();
        }

        Log::w("Load User From Billing");
        if ('standart' == $billingName) {
            $json = $api->getDataFromBilling("&request=get_user_list");
        } elseif ('carbon5' == $billingName) {
            $json = $api->getDataFromBilling("&method1=userside_manager.get_user_list");
        } elseif ('abills' == $billingName) {
            $json = [];
        } else {
            $json = $billing->getCustomerList();
        }
        if (!is_array($json)) {
            $json = [];
        }
        if (2 > count($json)) {
            $json = [];
        }
        Log::w("Count: " . count($json));

        //Если нет данных про абонентов - выходим. Либо делать нечего либо проблемы с биллингом
        if (count($json) < 2) {
            Log::c('Error Load Billing Customer');
            $moduleHelper->terminateModule();
        }

        Log::w("Load User Tags From Billing");
        if ('standart' == $billingName) {
            $this->arrayBillingTags = $api->getDataFromBilling("&request=get_user_tags", 1);
        } elseif ('carbon5' == $billingName) {
            $this->arrayBillingTags = [];
        } elseif ('abills' == $billingName) {
            $this->arrayBillingTags = [];
        } else {
            $this->arrayBillingTags = $billing->getBillingTags();
        }
        if (!is_array($this->arrayBillingTags)) {
            $this->arrayBillingTags = [];
        }

        Log::w("Load User Groups From Billing");
        if ('standart' == $billingName) {
            $this->arrayBillingGroup = $api->getDataFromBilling("&request=get_user_group_list", 1);
        } elseif ('carbon5' == $billingName) {
            $this->arrayBillingGroup = $api->getDataFromBilling("&method1=userside_manager.get_user_group_list&context=userside",
                1);
        } elseif ('abills' == $billingName) {
            $this->arrayBillingGroup = [];
        } else {
            $this->arrayBillingGroup = $billing->getBillingGroup();
            Log::d(json_encode($this->arrayBillingGroup, JSON_UNESCAPED_UNICODE));
        }
        if (!is_array($this->arrayBillingGroup)) {
            $this->arrayBillingGroup = [];
        }

        if ($confIsImportMessage == 1) {
            Log::w("Load User Messages From Billing");
            if ('standart' == $billingName) {
                $this->arrayBillingMsg = $api->getDataFromBilling("&request=get_user_messages");
            } elseif ('carbon5' == $billingName) {
                $this->arrayBillingMsg = [];
            } elseif ('abills' == $billingName) {
                $this->arrayBillingMsg = [];
            } else {
                $this->arrayBillingMsg = $billing->getBillingMsg();
            }
        }
        if (!is_array($this->arrayBillingMsg)) {
            $this->arrayBillingMsg = [];
        }

        if (1 == $isUpdateAddress) {
            $json = $this->houseProcessing($json);
            Log::d("arrayBillingHouse: " . json_encode($this->arrayBillingHouse, JSON_UNESCAPED_UNICODE), 1);
        }

        return $json;
    }

    public function loadPotentialFromBilling()
    {
        global $api, $isUpdateAddress, $billingName, $billing;
        Log::w("Load Potential User From Billing");
        if ('standart' == $billingName) {
            $json = $api->getDataFromBilling("&request=get_potential_user_list");
        } elseif ('carbon5' == $billingName) {
            $json = [];
        } elseif ('abills' == $billingName) {
            $json = [];
        } else {
            $json = $billing->getPotentialCustomerList();
        }
        if (2 > count($json)) {
            $json = [];
        }
        Log::w("Count: " . count($json));
        return $json;
    }

    public function loadFromUserside()
    {
        global $api, $billingId, $moduleHelper, $confIsImportPasswordToUsPassword;

        //New Style
        Log::w("Load Customer CRC From UserSide");
        $url     = "&request=get_user_crc_billing_list&billing_id=" . $billingId;
        $json    = $api->getDataFromUserside($url, 2);
        $dataCrc = [];
        if (
            !isset($json['result'])
            ||
            'OK' != $json['result']
        ) {
            Log::i("Error Load Customer CRC From UserSide");
            //$moduleHelper->finishModule();
        } else {
            $dataCrc = $json['data'];
            Log::w("Count: " . count($dataCrc));
        }

        //Old Style
        Log::w("Load User From UserSide");
        $url = "&request=get_user_list&is_id_billing_user_id=1&is_parent_id=1&timestamp_ready=1&billing_id=" . $billingId;
        if (1 === (int)$this->isLoadCustomerCommutation) {
            $url .= "&is_load_commutation=1";
        }
        if (1 === (int)$confIsImportPasswordToUsPassword) {
            $url .= "&is_load_password=1";
        }
        $json = $api->getDataFromUserside($url, 2);
        if (!is_array($json) || 1 > count($json)) {
            Log::i("Error Load User From UserSide");
            $moduleHelper->finishModule();
        }
        Log::w("Count: " . count($json));

        $userUserside = $json;
        foreach ($userUserside as $i => $value) {
            if (
                !isset($value['userside_id'])
                &&
                isset($value['erp_id'])
            ) {
                $userUserside[$i]['userside_id'] = $value['erp_id'];
            }
        }

        return [
            $userUserside,
            $dataCrc
        ];
    }

    public function loadTagsFromUserside()
    {
        global $api;
        Log::w("Load User Tags From UserSide");
        $json = $api->getDataFromUserside("&request=get_user_tags");
        Log::w("Count: " . count($json));
        return $json;
    }

    public function loadGroupFromUserside()
    {
        global $api;
        Log::w("Load User Groups From UserSide");
        $json = $api->getDataFromUserside("&request=get_user_group_list");
        Log::w("Count: " . count($json));
        return $json;
    }

    public function loadSwitchFromUserside()
    {
        global $api;
        Log::w("Load Switch From UserSide");
        $json = $api->getDataFromUserside("&request=get_switch_list&is_id_ip=1");
        Log::w("Count: " . count($json));
        return $json;
    }

    private function addTagToUserside($array)
    {
        global $api;
        $name = $array['name'];
        Log::d("Add Tag To UserSide. Name: '" . $name . "'");
        $json = $api->sendDataToUserside("&cat=customer&action=add_tag&name=" . urlencode($name));
        if (isset($json['id'])) {
            Log::d("Adding OK. Id: " . $json['id']);
            return $json['id'];
        } else {
            Log::d("Adding ERROR");
            return 0;
        }
    }

    private function addGroupToUserside($array)
    {
        global $api;
        $name = $array['name'];
        Log::d("Add Group To UserSide. Name: '" . $name . "'");
        $post = [
            'cat' => 'customer',
            'action' => 'add_group',
            'name' => $name
        ];
        $json = $api->sendDataToUserside("&cat=customer&action=add_group&name=" . $name, $post);
        if (isset($json['id'])) {
            Log::d("Adding OK. Id: " . $json['id']);
            return $json['id'];
        } else {
            Log::d("Adding ERROR");
            return 0;
        }
    }

    private function addMsgToUserside($array)
    {
        global $api;
        Log::d("Add Msg To UserSide. Id: " . $array['id']);
        if ('' != $array['subject']) {
            $array['text'] = $array['subject'] . '<br>' . $array['text'];
        }
        $json = $api->sendDataToUserside("&cat=customer&action=msg_add&billing_msg_id=" . $array['id'] . "&customer_id=" . $array['customer_userside_id'] . "&billing_msg_date=" . strtotime($array['msg_date']) . "&text=" . $array['text']);
        if (isset($json['id'])) {
            Log::d("Adding OK. Id: " . $json['id']);
            return $json['id'];
        } else {
            Log::d("Adding ERROR");
            return false;
        }
    }

    private function editMsgInUserside($array)
    {
        global $api;
        Log::d("Change Msg State In UserSide. Id: " . $array['id']);
        $json = $api->sendDataToUserside("&cat=customer_msg&action=edit&billing_msg_id=" . $array['id'] . "&answer=" . $array['answer']);
        if (isset($json['id'])) {
            Log::d("Edit OK. Id: " . $json['id']);
            return $json['id'];
        } else {
            Log::d("Editing ERROR");
            return 0;
        }
    }

    private function prepareToAddCustomerToUserside($array)
    {
        $this->arrayCustomerToAdd[] = [
            'billing_customer_id' => $array['id'],
            //'name' => urlencode($array['name'])
            'name' => '_ run billing module again _'
        ];
    }

    private function addCustomerToUserside()
    {
        global $api, $billingId;
        Log::w("Add Customer To UserSide. Count: " . count($this->arrayCustomerToAdd));
        $count    = 0;
        $bufer    = [];
        $addCount = 0;
        foreach ($this->arrayCustomerToAdd as $i => $value) {
            ++$count;
            $bufer[] = $value;
            if (0 == fmod($count, 100)) {
                $json = $api->sendDataToUserside("&cat=customer&action=group_add&billing_id=" . $billingId . "&data=" . str_replace('#',
                        '_', str_replace('&', '_', json_encode($bufer, JSON_UNESCAPED_UNICODE))));
                if (isset($json['Count'])) {
                    $addCount += $json['Count'];
                } elseif (isset($json['count'])) {
                    $addCount += $json['count'];
                } else {
                    //Ошибка и подаём по одному
                    Log::d("Adding ERROR. Try by one");
                    foreach ($bufer as $value2) {
                        $buferTemp = [
                            $value2
                        ];
                        $json      = $api->sendDataToUserside("&cat=customer&action=group_add&billing_id=" . $billingId . "&data=" . str_replace('#',
                                '_', str_replace('&', '_', json_encode($buferTemp, JSON_UNESCAPED_UNICODE))));
                        if (isset($json['Count'])) {
                            $addCount += $json['Count'];
                        } elseif (isset($json['count'])) {
                            $addCount += $json['count'];
                        }
                    }
                }
                $bufer = [];
            }
        }
        if (0 < count($bufer)) {
            $json = $api->sendDataToUserside("&cat=customer&action=group_add&billing_id=" . $billingId . "&data=" . json_encode($bufer,
                    JSON_UNESCAPED_UNICODE));
            if (isset($json['Count'])) {
                $addCount += $json['Count'];
            } elseif (isset($json['Count'])) {
                $addCount += $json['count'];
            } else {
                //Ошибка и подаём по одному
                Log::d("Adding ERROR. Try by one");
                foreach ($bufer as $value2) {
                    $buferTemp = [
                        $value2
                    ];
                    $json      = $api->sendDataToUserside("&cat=customer&action=group_add&billing_id=" . $billingId . "&data=" . str_replace('#',
                            '_', str_replace('&', '_', json_encode($buferTemp, JSON_UNESCAPED_UNICODE))));
                    if (isset($json['Count'])) {
                        $addCount += $json['Count'];
                    } elseif (isset($json['count'])) {
                        $addCount += $json['count'];
                    }
                }
            }
        }
        if (isset($json['Count'])) {
            Log::d("Adding OK. Count: " . $addCount);
        } elseif (isset($json['count'])) {
            Log::d("Adding OK. Count: " . $addCount);
        } else {
            Log::d("Adding ERROR");
        }
        return $json;
    }

    private function editCustomerInUserside()
    {
        global $api, $apiGroupCounter, $apiGroupCounter2, $isCustomerActivityTimestamp;
        Log::w("Edit Customer In UserSide. Count: " . count($this->arrayCustomerToEdit));

        $n = 200; //В запросах подавать по n-абонентов
        if (0 < $apiGroupCounter2) {
            $n = $apiGroupCounter2;
        }
        $nCustom = 4000; //Для типовых данных - подавать блоками по $nCustom
        if (0 < $apiGroupCounter) {
            $nCustom = $apiGroupCounter;
        }

        //Прогоняем типовые данные
        $arrayGroupList = [
            'balance' => 1,
            'traffic_up' => 1,
            'traffic_down' => 1
        ];

        if ($isCustomerActivityTimestamp == 1) {
            $arrayGroupList['date_activity_inet_unix'] = 1;
        } else {
            $arrayGroupList['date_activity_inet'] = 1;
        }

        $arrayGroupData = [];
        foreach ($this->arrayCustomerToEdit as $customerId => $value) {
            foreach ($arrayGroupList as $j => $value2) {
                if (isset($value[$j])) {
                    $arrayGroupData[$j][$customerId] = $value[$j];
                    unset($this->arrayCustomerToEdit[$customerId][$j]);
                }
            }
            if (2 > count($this->arrayCustomerToEdit[$customerId])) { //2 - т.к. crc_billing - это 1
                unset($this->arrayCustomerToEdit[$customerId]);
            }
        }

        //Посылаем групповые данные
        foreach ($arrayGroupList as $i => $value) {
            if (isset($arrayGroupData[$i])) {
                $j         = 0;
                $jAll      = 0;
                $tempArray = [];
                foreach ($arrayGroupData[$i] as $customerId => $value2) {
                    ++$j;
                    ++$jAll;
                    if ($nCustom >= $j) {
                        $tempArray[$customerId][$i] = $value2;
                    } else {
                        Log::d("Edit '" . $i . "' - " . ($jAll - $nCustom) . '-' . $jAll . " / " . count($arrayGroupData[$i]));
                        $post = [
                            'cat' => 'customer',
                            'action' => 'group_edit',
                            'src' => 'billing',
                            'data' => json_encode($tempArray, JSON_UNESCAPED_UNICODE)
                        ];
                        $json = $api->sendDataToUserside(implode('&', $post), $post);
                        if (isset($json['Count'])) {
                            Log::d("Editing OK. Count: " . $json['Count']);
                        } elseif (isset($json['count'])) {
                            Log::d("Editing OK. count: " . $json['count']);
                        } else {
                            Log::d("Editing ERROR");
                        }
                        $j         = 0;
                        $tempArray = [];
                    }
                }
                //Если ещё что осталось
                if (0 < $j) {
                    $post = [
                        'cat' => 'customer',
                        'action' => 'group_edit',
                        'src' => 'billing',
                        'data' => json_encode($tempArray, JSON_UNESCAPED_UNICODE)
                    ];
                    $json = $api->sendDataToUserside(implode('&', $post), $post);
                    if (isset($json['Count'])) {
                        Log::d("Editing OK. Count: " . $json['Count']);
                    } elseif (isset($json['count'])) {
                        Log::d("Editing OK. count: " . $json['count']);
                    } else {
                        Log::d("Editing ERROR");
                    }
                }
            }
        }

        //Обрабатываем оставшиеся данные
        //Разбиваем по $n абонентов
        $j         = 0;
        $jAll      = 0;
        $tempArray = [];
        foreach ($this->arrayCustomerToEdit as $customerId => $value) {
            ++$j;
            ++$jAll;
            if ($n >= $j) {
                $tempArray[$customerId] = $value;
            } else {
                Log::d("Edit " . ($jAll - $n) . '-' . $jAll . " / " . count($this->arrayCustomerToEdit));
                $post = [
                    'cat' => 'customer',
                    'action' => 'group_edit',
                    'data' => json_encode($tempArray, JSON_UNESCAPED_UNICODE)
                ];
                $json = $api->sendDataToUserside(implode('&', $post), $post);
                if (isset($json['Count'])) {
                    Log::d("Editing OK. Count: " . $json['Count']);
                } elseif (isset($json['count'])) {
                    Log::d("Editing OK. count: " . $json['count']);
                } else {
                    Log::d("Editing ERROR");
                    Log::d("Error Data: " . serialize($tempArray));

                    //Пытаемся подавать индивидуально данные
                    foreach ($tempArray as $customerId2 => $value2) {
                        $sendData = [
                            $customerId2 => $value2
                        ];
                        $post     = [
                            'cat' => 'customer',
                            'action' => 'group_edit',
                            'data' => json_encode($sendData, JSON_UNESCAPED_UNICODE)
                        ];
                        $json     = $api->sendDataToUserside(implode('&', $post), $post);
                        if (isset($json['Count'])) {
                            Log::d("Customer editing OK");
                        } elseif (isset($json['count'])) {
                            Log::d("Customer editing OK");
                        } else {
                            Log::d("Customer editing ERROR");
                            Log::d("Error Data: " . serialize($sendData));
                        }
                    }
                }
                $j         = 0;
                $tempArray = [];
            }
        }

        //Если ещё что осталось
        if (0 < $j) {
            $post = [
                'cat' => 'customer',
                'action' => 'group_edit',
                'data' => json_encode($tempArray, JSON_UNESCAPED_UNICODE)
            ];
            $json = $api->sendDataToUserside(implode('&', $post), $post);
            if (isset($json['Count'])) {
                Log::d("Editing OK. Count: " . $json['Count']);
            } elseif (isset($json['count'])) {
                Log::d("Editing OK. Count: " . $json['count']);
            } else {
                Log::d("Editing ERROR");
                Log::d("Error Data: " . serialize($tempArray));

                //Пытаемся подавать индивидуально данные
                foreach ($tempArray as $customerId => $value) {
                    $sendData = [
                        $customerId => $value
                    ];
                    $post     = [
                        'cat' => 'customer',
                        'action' => 'group_edit',
                        'data' => json_encode($sendData, JSON_UNESCAPED_UNICODE)
                    ];
                    $json     = $api->sendDataToUserside(implode('&', $post), $post);
                    if (isset($json['Count'])) {
                        Log::d("Custom editing OK");
                    } elseif (isset($json['count'])) {
                        Log::d("Custom editing OK");
                    } else {
                        Log::d("Custom editing ERROR");
                        Log::d("Error Data: " . serialize($sendData));
                    }
                }
            }
        }
    }

    private function importPaidHistory($customerPaidHistory)
    {
        global $api, $billingId;
        Log::w("Import Paid History In UserSide. Count: " . count($customerPaidHistory));
        $post = [
            'cat' => 'customer',
            'action' => 'import_paid',
            'billing_id' => $billingId,
            'data' => json_encode($customerPaidHistory, JSON_UNESCAPED_UNICODE)
        ];
        $json = $api->sendDataToUserside(implode('&', $post), $post);
        if (isset($json['Count'])) {
            Log::d("Import OK. Count: " . $json['Count']);
        } elseif (isset($json['count'])) {
            Log::d("Import OK. Count: " . $json['count']);
        } else {
            Log::d("Import ERROR");
        }
    }

    private function compareData($field, $isSpecialValue = 0)
    {
        global $isEncodeCustomerPassword;
        $usersideId = $this->currentUserSideData['userside_id'];
        if (isset($this->currentBillingData[$field])) {
            $billingValue = str_replace("'", '&#039;', $this->currentBillingData[$field]);
            if ('password' == $field && $isEncodeCustomerPassword == 1) {
                $billingValue = md5(md5($billingValue) . '_us1234567890US_' . $usersideId);
            }
        } else {
            if ($isSpecialValue == 4) {
                //Если такого поля нет в биллинге - то пропускаем
                return;
            }
            if (1 == $isSpecialValue) {
                $billingValue = 0;
            } elseif (2 == $isSpecialValue || 3 == $isSpecialValue) {
                $billingValue = '1970-01-01';
            } else {
                $billingValue = '';
            }
        }
        if (3 == $isSpecialValue) {
            $billingValue = date('Y-m-d', strtotime($billingValue));
        }
        if (isset($this->currentUserSideData[$field])) {
            $usersideValue = $this->currentUserSideData[$field];
        } else {
            if (1 == $isSpecialValue) {
                $usersideValue = 0;
            } elseif (2 == $isSpecialValue || 3 == $isSpecialValue) {
                $usersideValue = '1970-01-01 00:00:00';
            } else {
                $usersideValue = '';
            }
        }
        if (
            3 == $isSpecialValue
            &&
            '1970-01-01 00:00:00' != $usersideValue
            &&
            '1970-01-01' != $usersideValue
            &&
            '0000-00-00' != $usersideValue
            &&
            '' != $usersideValue
        ) {
            $billingValue = $usersideValue;
        }
        if ($usersideValue != $billingValue) {
            if (
                2 != $isSpecialValue
                ||
                ('1970-01-01 00:00:00' != $billingValue && '1970-01-01' != $billingValue && '' != $billingValue)
            ) {
                $this->arrayCustomerToEdit[$usersideId][$field] = str_replace("'", '&#039;', $billingValue);
            }
        }
    }

    public function compareCustomer($billing, $userside, $userUsersideCrc)
    {
        global $additionalDataMerge, $additionalCustomerDataMerge, $isUpdateAddress, $customerPaidHistory, $customer, $confIpGrayNet, $confIpWhiteNet;
        global $serviceBilling, $confIsImportLessPhone, $confIsImportPasswordToUsPassword, $confIsSavePasswordToComment;
        global $confIsSkipUpdateAgreementDate, $isUpdateTraffic, $rowLimit, $confIsDoNotUpdateAddPhone;
        global $isDontChangeChildId, $markMerge, $confIsSkipParentLink, $confIsSkipDeleteEmptyIp, $confSkipDeleteIp;
        global $confIsImportLessAddress, $confIsUpdateEmptyLevel, $confIsUpdateEmptyEntrance, $confIsDontUpdateDateConnect;
        global $isUpdateMac, $confIsSkipUpdateDateActivity, $confAlwaysSetCustomerGroupId;
        global $isCustomerActivityTimestamp, $confIsForceDeleteEmptyIp, $confIsSkipSyncCustomerIsCorporate;

        /*
         * $userUsersideCrc
         *
         * $data[$value['uuid']] = [
                $value['crc'],
                $value['date_activity'],
                $value['balance'],
                floor($value['traffic_in']),
                floor($value['traffic_out'])
            ];
         *
         */

        if (0 !== (int)$confIsDontUpdateDateConnect) {
            $confIsDontUpdateDateConnect = 1;
        } else {
            $confIsDontUpdateDateConnect = 0;
        }

        if ('' != $confIpGrayNet && '0' != $confIpGrayNet) {
            $isAddWhiteIp = 1;
            $grayNetIpMin = trim(sprintf("%u\n", ip2long($confIpGrayNet))) + 1;
            $grayNetIpMax = $grayNetIpMin + 65025;
            $whiteNetIp   = trim(sprintf("%u\n", ip2long($confIpWhiteNet)));
            $whiteNetIp   = $grayNetIpMin - $whiteNetIp - 1;
        } else {
            $isAddWhiteIp = 0;
        }

        //Сверяем метки (только наименования меток)
        if (0 < count($this->arrayBillingTags)) {
            Log::w("Compare Tag");
            $tagsUserside = $customer->loadTagsFromUserside();
            foreach ($this->arrayBillingTags as $i => $value) {
                $isFind = 0;
                if (isset($value['name'])) {
                    $tagBillingName                      = $value['name'];
                    $this->arrayBillingTags[$i]['us_id'] = 0;
                    foreach ($tagsUserside as $j => $value2) {
                        if ($tagBillingName == $value2['name']) {
                            $isFind                              = 1;
                            $this->arrayBillingTags[$i]['us_id'] = $value2['id'];
                            $tagsUserside[$j]['billing_id']      = $i;
                            break;
                        }
                    }
                    if (1 != $isFind) {
                        $bundle     = [
                            'name' => $tagBillingName
                        ];
                        $usersideId = $this->addTagToUserside($bundle);
                        if (0 < $usersideId) {
                            $this->arrayBillingTags[$i]['us_id'] = $usersideId;
                        }
                    }
                }
            }
        }

        //Сверяем группы (только наименования групп)
        if (0 < count($this->arrayBillingGroup)) {
            Log::w("Compare Group");
            $groupUserside = $customer->loadGroupFromUserside();
            foreach ($this->arrayBillingGroup as $i => $value) {
                $isFind = 0;
                if (isset($value['name'])) {
                    $groupBillingName = trim($value['name']);
                    $groupBillingName = str_replace("+", '&#43;', $groupBillingName);

                    $groupBillingNameVerify               = str_replace(
                        [
                            "/",
                            "'"
                        ],
                        [
                            '&#047;',
                            '&#039;'
                        ],
                        $groupBillingName);
                    $this->arrayBillingGroup[$i]['us_id'] = 0;
                    foreach ($groupUserside as $j => $value2) {
                        $usersideNameVerify = trim($value2['name']);
                        $usersideNameVerify = str_replace(
                            [
                                "/",
                                "'"
                            ],
                            [
                                '&#047;',
                                '&#039;'
                            ],
                            $usersideNameVerify);
                        if ($groupBillingNameVerify == $usersideNameVerify) {
                            $isFind                               = 1;
                            $this->arrayBillingGroup[$i]['us_id'] = $value2['id'];
                            $groupUserside[$j]['billing_id']      = $i;
                            break;
                        }
                    }
                    if (1 != $isFind) {
                        $bundle     = [
                            'name' => $groupBillingName
                        ];
                        $usersideId = $this->addGroupToUserside($bundle);
                        if (0 < $usersideId) {
                            $this->arrayBillingGroup[$i]['us_id'] = $usersideId;
                        }
                    }
                }
            }
            Log::d("arrayBillingGroup: " . json_encode($this->arrayBillingGroup, JSON_UNESCAPED_UNICODE));
        }

        //Загружаем список коммутаторов
        if (1 == $this->isLoadCustomerCommutation) {
            $switch = $customer->loadSwitchFromUserside();
        }

        Log::w("Compare Customer");

        if ($rowLimit > 150) {
            $countInBilling = 0;
            foreach ($billing as $id => $value) {
                if (!isset($value['is_disable'])) {
                    ++$countInBilling;
                }
            }
            if ($countInBilling > $rowLimit) {
                Log::w("OverLimit: " . ($countInBilling - $rowLimit));
                $count = 0;
                foreach ($billing as $id => $value) {
                    if (!isset($value['is_disable'])) {
                        ++$count;
                        if ($count > $rowLimit) {
                            unset($billing[$id]);
                        }
                    }
                }
            }
        }

        foreach ($billing as $id => $value) {
            $id2 = str_replace('&', '_', $id);
            $id2 = str_replace('+', '_', $id2);
            if ($id != $id2) {
                $billing[$id2] = $value;
                unset($billing[$id]);
            }
        }

        //Проверяем всех дочерних абонентов
        foreach ($billing as $id => $value) {
            if (isset($value['account'])) {
                foreach ($value['account'] as $childId => $value2) {
                    if (is_array($value2)) {
                        $parentId = isset($userside[$id]['userside_id']) ? $userside[$id]['userside_id'] : 0;
                        if (1 == $isDontChangeChildId) {
                            $billing[$childId]              = $value2;
                            $billing[$childId]['parent_id'] = $parentId;
                        } else {
                            $billing[$id . '_' . $childId]              = $value2;
                            $billing[$id . '_' . $childId]['parent_id'] = $parentId;
                        }
                    }
                }
            }
        }

        //Проходим всех абонентов из биллинга
        foreach ($billing as $id => $value) {
            if (!isset($value['is_disable'])) {
                $value['is_disable'] = 0;
            }
            $name = isset($value['full_name']) ? $value['full_name'] : '';
            $name = trim(str_replace("  ", ' ', $name));
            $name = str_replace("  ", ' ', $name);
            $name = str_replace("  ", ' ', $name);
            $name = str_replace("'", '&#039;', $name);
            $name = str_replace("\\", '', $name);
            $name = str_replace("\n", ' ', $name);
            $name = str_replace(chr(13), ' ', $name);

            $name = trim($name);

            if (isset($value['balance'])) {
                $value['balance'] = round($value['balance'], 2);
            }
            if (isset($value['credit'])) {
                $value['credit'] = round($value['credit'], 2);
            }

            $this->currentBillingData = $value;

            //Есть ли такой абонент в UserSide
            if (!isset($userside[$id])) {
                //Нужно добавить абонента в UserSide
                Log::w("Customer '" . $id . "' Not Found In UserSide. Adding...");
                $bundle = [
                    'id' => $id,
                    'name' => $name
                ];
                $this->prepareToAddCustomerToUserside($bundle);
            }

            if (isset($userside[$id])) {
                //Этот абонент есть. Нужно сверить данные

                $dataForCrc = $value;
                if (isset($dataForCrc['balance'])) {
                    unset($dataForCrc['balance']);
                }
                if (isset($dataForCrc['date_activity'])) {
                    unset($dataForCrc['date_activity']);
                }
                if (isset($dataForCrc['traffic'])) {
                    unset($dataForCrc['traffic']);
                }
                $customerCrc = md5(serialize($dataForCrc));

                if (
                    isset($userUsersideCrc[$id][0])
                    &&
                    $customerCrc == $userUsersideCrc[$id][0]
                ) {
                    //Log::d("CRC is correct '" . $id . "': " . $customerCrc);
                } else {
                    //Log::d("CRC is incorrect '" . $id . "': " . $customerCrc . ' | ' . $userUsersideCrc[$id][0]);
                }

                $userside[$id]['isFindInBilling'] = 1;

                $customerCurrent = $userside[$id];
                $usersideId      = $customerCurrent['userside_id'];

                if (isset($customerCurrent['balance'])) {
                    $customerCurrent['balance'] = round($customerCurrent['balance'], 2);
                }
                if (isset($customerCurrent['credit'])) {
                    $customerCurrent['credit'] = round($customerCurrent['credit'], 2);
                }

                $this->currentUserSideData = $customerCurrent;

                //Название
                if ($customerCurrent['full_name'] != $name) {
                    $this->arrayCustomerToEdit[$usersideId]['name'] = str_replace("'", '&#039;', $name);
                }

                if (isset($value['comment'])) {
                    $value['comment']         = str_replace('\\', '&#092;', $value['comment']);
                    $value['comment']         = str_replace("/", '&#047;', $value['comment']);
                    $value['comment']         = str_replace("\n", ' ', $value['comment']);
                    $value['comment']         = trim(str_replace("'", '&#039;', $value['comment']));
                    $this->currentBillingData = $value;
                }

                if (isset($value['date_connect'])) {
                    if ('0000-00-00' == $value['date_connect']) {
                        $value['date_connect']    = "1970-01-01";
                        $this->currentBillingData = $value;
                    }
                }

                if (1 == $confIsSavePasswordToComment && isset($value['password'])) {
                    if ('' != $value['comment']) {
                        $value['comment'] .= '<br>';
                    }
                    $value['comment']         .= '<b>pass: ' . $value['password'] . '</b>';
                    $this->currentBillingData = $value;
                }

                $this->compareData('account_number'); //Лицевой счёт
                $this->compareData('login'); //Учетная запись
                $this->compareData('comment'); //Заметки
                $this->compareData('balance', 1); //Баланс
                $this->compareData('credit', 1); //Кредит
                $this->compareData('discount', 1); //Скидка
                if (1 !== (int)$confIsSkipSyncCustomerIsCorporate) {
                    $this->compareData('flag_corporate', 4); //Флаг - юр.лицо
                }
                $this->compareData('is_disable', 1); //Статус уч.записи (нормальная, бывшая)
                if (1 === (int)$confIsDontUpdateDateConnect) {
                    $this->compareData('date_connect', 3); //Дата подключения
                } else {
                    $this->compareData('date_connect'); //Дата подключения
                }
                if (1 != $confIsSkipParentLink) {
                    $this->compareData('parent_id', 1); //Родительская запись
                }
                if (1 == $confIsImportPasswordToUsPassword) {
                    $this->compareData('password'); //Пароль
                }

                //Статус отключения
                if (isset($value['disconnect_state'])) {
                    $this->compareData('disconnect_state', 1);
                    if (isset($value['disconnect_date'])) {
                        $this->compareData('disconnect_date');
                    }
                }

                //Тарифы
                if (!isset($value['tariff']['current'][0]['id'])) {
                    $value['tariff']['current'][0]['id'] = '';
                }

                $arrayAddTariff = [];
                if (
                    isset($value['tariff']['current'])
                    &&
                    is_array($value['tariff']['current'])
                ) {
                    foreach ($value['tariff']['current'] as $array) {
                        if (isset($array['id'])) {
                            $tariffId = $array['id'];
                            //Проверяем наличие такого тарифа (одинаковых тарифов может быть несколько)
                            $isFindTariff = 0;
                            foreach ($customerCurrent['tariff']['current'] as $j => $array2) {
                                $tariffInUsersideId = $array2['id'];
                                if ($tariffInUsersideId == $tariffId) {
                                    $isFindTariff                                       = 1;
                                    $customerCurrent['tariff']['current'][$j]['id']     = 'alreadyUse';
                                    $customerCurrent['tariff']['current'][$j]['isUsed'] = 1;
                                    break;
                                }
                            }
                            //if (1 != $isFindTariff && '' != $tariffId && $tariffId > 0) { //TT-23253 - тарифы могут быть и не цифровые
                            if (1 != $isFindTariff && '' != $tariffId) {
                                $arrayAddTariff[] = $tariffId;
                            }
                        }
                    }
                }

                //Откидываем неиспользованые
                foreach ($customerCurrent['tariff']['current'] as $i => $array) {
                    if ('' != $array['id'] && !isset($array['isUsed'])) {
                        $this->arrayCustomerToEdit[$usersideId]['delete_tariff_id'][] = $array['id'];
                    }
                }
                if (0 < count($arrayAddTariff)) {
                    $this->arrayCustomerToEdit[$usersideId]['add_tariff_id'] = $arrayAddTariff;
                }

                //Следующий тариф
                $billingValue  = $value['tariff']['new'][0]['id'] ?? '';
                $usersideValue = $customerCurrent['tariff']['new'][0]['id'] ?? '';
                if (strtolower($billingValue) != strtolower($usersideValue)) {
                    $billingDate                                             = $value['tariff']['new'][0]['date_start'] ?? '1970-01-01';
                    $this->arrayCustomerToEdit[$usersideId]['tariff_new_id'] = $billingValue . '|' . $billingDate;
                }

                //Номер договора
                $billingValue  = $value['agreement'][0]['number'] ?? '';
                $usersideValue = $customerCurrent['agreement'][0]['number'] ?? '';
                if ($billingValue != $usersideValue && '' != $billingValue) {
                    $this->arrayCustomerToEdit[$usersideId]['agreement_number'] = str_replace("'", '&#039;',
                        $billingValue);
                }
                //Дата договора
                $billingValue  = $value['agreement'][0]['date'] ?? '';
                $usersideValue = isset($customerCurrent['agreement'][0]['date']) ? $customerCurrent['agreement'][0]['date'] : '';
                if (
                    $billingValue != $usersideValue
                    &&
                    '' != $billingValue
                    &&
                    (
                        1 != $confIsSkipUpdateAgreementDate
                        ||
                        '' == $usersideValue
                    )
                ) {
                    $this->arrayCustomerToEdit[$usersideId]['agreement_date'] = str_replace("'", '&#039;',
                        $billingValue);
                }

                //Трафик
                if (1 == $isUpdateTraffic) {
                    $trafficUp = $value['traffic']['month']['up'] ?? 0;
                    if ($customerCurrent['traffic']['month']['up'] != $trafficUp) {
                        $this->arrayCustomerToEdit[$usersideId]['traffic_up'] = $trafficUp;
                    }
                    $trafficDown = $value['traffic']['month']['down'] ?? 0;
                    if ($customerCurrent['traffic']['month']['down'] != $trafficDown) {
                        $this->arrayCustomerToEdit[$usersideId]['traffic_down'] = $trafficDown;
                    }
                }

                //Телефоны
                if (1 == $confIsImportLessPhone) {
                    $phone0 = '';
                    $phone1 = '';
                    $phone2 = '';
                    $phone3 = '';
                    $phone4 = '';
                } else {
                    $phone0 = '-1';
                    $phone1 = '-1';
                    $phone2 = '-1';
                    $phone3 = '-1';
                    $phone4 = '-1';
                }

                if (
                    isset($value['phone'])
                    &&
                    is_array($value['phone'])
                ) {
                    foreach ($value['phone'] as $i => $value2) {
                        if (strpos($value2['number'], ',') > 0) {
                            unset($value['phone'][$i]);
                            $tempPhoneArray = explode(',', $value2['number']);
                            foreach ($tempPhoneArray as $value3) {
                                $value3 = trim($value3);
                                if ($value3 != '') {
                                    $value['phone'][] = [
                                        'number' => $value3
                                    ];
                                }
                            }
                        }
                    }
                    foreach ($value['phone'] as $i => $value2) {
                        if (strpos($value2['number'], ';') > 0) {
                            unset($value['phone'][$i]);
                            $tempPhoneArray = explode(';', $value2['number']);
                            foreach ($tempPhoneArray as $value3) {
                                $value3 = trim($value3);
                                if ($value3 != '') {
                                    $value['phone'][] = [
                                        'number' => $value3
                                    ];
                                }
                            }
                        }
                    }

                    $isFindMain = 0;
                    foreach ($value['phone'] as $i => $value2) {
                        if (
                            isset($value2['flag_main'])
                            &&
                            1 == $value2['flag_main']
                        ) {
                            $isFindMain = $i;
                        }
                    }
                    if ($isFindMain > 0) {
                        $temp                        = $value['phone'][0];
                        $value['phone'][0]           = $value['phone'][$isFindMain];
                        $value['phone'][$isFindMain] = $temp;
                    }

                    $count = 0;
                    foreach ($value['phone'] as $value2) {
                        $value2['number'] = str_replace("'", '&#039;', $value2['number']);
                        $value2['number'] = str_replace('+', '', $value2['number']);
                        $value2['number'] = preg_replace('/[^\d.-]/', '', $value2['number']);
                        $value2['number'] = preg_replace('/[ -.;]/', '', $value2['number']);
                        if ('' !== $value2['number']) {
                            if ($count == 0) {
                                $phone0 = $value2['number'];
                            }
                            if ($count == 1) {
                                $phone1 = $value2['number'];
                            }
                            if ($count == 2) {
                                $phone2 = $value2['number'];
                            }
                            if ($count == 3) {
                                $phone3 = $value2['number'];
                            }
                            if ($count == 4) {
                                $phone4 = $value2['number'];
                            }
                            ++$count;
                        }
                    }
                }

                $phoneInUserSide = [];
                if (isset($customerCurrent['phone'])) {
                    foreach ($customerCurrent['phone'] as $valuePhone) {
                        $tempPhone = $valuePhone['number'] ?? '';
                        if ($tempPhone !== '') {
                            $phoneInUserSide[$tempPhone] = true;
                        }
                    }
                }

                $usersideValue = $customerCurrent['phone'][0]['number'] ?? '';
                if (
                    $usersideValue != $phone0
                    &&
                    '-1' != $phone0
                    &&
                    !isset($phoneInUserSide[$phone0])
                ) {
                    $this->arrayCustomerToEdit[$usersideId]['phone0'] = $phone0;
                }
                $usersideValue = $customerCurrent['phone'][1]['number'] ?? '';
                if (
                    $usersideValue != $phone1
                    &&
                    '-1' != $phone1
                    &&
                    !isset($phoneInUserSide[$phone1])
                ) {
                    $this->arrayCustomerToEdit[$usersideId]['phone1'] = $phone1;
                }
                if (1 != $confIsDoNotUpdateAddPhone) {
                    $usersideValue = $customerCurrent['phone'][2]['number'] ?? '';
                    if (
                        $usersideValue != $phone2
                        &&
                        '-1' != $phone2
                        &&
                        !isset($phoneInUserSide[$phone2])
                    ) {
                        $this->arrayCustomerToEdit[$usersideId]['phone2'] = $phone2;
                    }
                    $usersideValue = $customerCurrent['phone'][3]['number'] ?? '';
                    if (
                        $usersideValue != $phone3
                        &&
                        '-1' != $phone3
                        &&
                        !isset($phoneInUserSide[$phone3])
                    ) {
                        $this->arrayCustomerToEdit[$usersideId]['phone3'] = $phone3;
                    }
                    $usersideValue = $customerCurrent['phone'][4]['number'] ?? '';
                    if (
                        $usersideValue != $phone4
                        &&
                        '-1' != $phone4
                        &&
                        !isset($phoneInUserSide[$phone4])
                    ) {
                        $this->arrayCustomerToEdit[$usersideId]['phone4'] = $phone4;
                    }
                }

                //E-mail
                $billingValue = $value['email'][0]['address'] ?? '';
                if ('' != $billingValue) {
                    $billingValue = str_replace(
                        [
                            "'",
                            "\n"
                        ],
                        [
                            '&#039;',
                            ' '
                        ], $billingValue);
                    $billingValue = str_replace("  ", ' ', $billingValue);
                    $billingValue = str_replace("  ", ' ', $billingValue);
                    $billingValue = str_replace("  ", ' ', $billingValue);
                    $arrayEmail   = explode(' ', $billingValue);
                    $billingValue = '';
                    foreach ($arrayEmail as $i => $value4) {
                        $value4 = trim($value4);
                        if ('' != $value4) {
                            $array2 = explode('@', $value4);
                            if (!isset($array2[1])) {
                                $value4 = '';
                            }
                        }
                        if ('' != $value4) {
                            if ('' != $billingValue) {
                                $billingValue .= ', ';
                            }
                            $billingValue .= $value4;
                        }
                    }
                    $billingValue = str_replace(",,", ',', $billingValue);

                    //$billingValue = str_replace(",", ' ', $billingValue);
                    /*
                    $arrayEmail   = explode('@', $billingValue);
                    $billingValue = '';
                    if (isset($arrayEmail[1])) {
                        $email1       = explode(' ', $arrayEmail[0]);
                        $email1       = $email1[count($email1) - 1];
                        $email2       = explode(' ', $arrayEmail[1]);
                        $email2       = $email2[0];
                        $billingValue = $email1 . '@' . $email2;
                    }
                    */
                }
                $usersideValue = isset($customerCurrent['email'][0]['address']) ? $customerCurrent['email'][0]['address'] : '';
                if ($billingValue != $usersideValue && '' != $billingValue) {
                    $this->arrayCustomerToEdit[$usersideId]['email'] = str_replace("'", '&#039;', $billingValue);
                }

                //Адрес
                if (1 == $isUpdateAddress) {
                    //Здание абонента
                    $billingValue = $value['address'][0]['house_id'] ?? 0;
                    if (
                        $confIsImportLessAddress == 1
                        &&
                        $billingValue == 0
                        &&
                        strlen($billingValue) < 2
                    ) {
                        $billingValue                               = -100;
                        $value['address'][0]['apartment']['number'] = '';
                        $value['address'][0]['floor']               = '';
                        $value['address'][0]['entrance']            = '';
                    }
                    if ($billingValue == -100) {
                        $usersideValue = $customerCurrent['address'][0]['house_id'] ?? 0;
                        if ($usersideValue != 0) {
                            $this->arrayCustomerToEdit[$usersideId]['house_id'] = 0;
                        }
                    } else {
                        if (
                            0 < $billingValue
                            ||
                            1 < strlen($billingValue)
                        ) {
                            $billingValue  = $this->arrayBillingHouse[$billingValue]['_userside_id'] ?? 0;
                            $usersideValue = $customerCurrent['address'][0]['house_id'] ?? 0;
                            if (
                                $billingValue != $usersideValue
                                &&
                                (
                                    0 < $billingValue
                                    ||
                                    strlen($billingValue) > 5
                                )
                            ) {
                                $this->arrayCustomerToEdit[$usersideId]['house_id'] = $billingValue;
                            }
                        }
                    }

                    //Квартира
                    $billingValue  = $value['address'][0]['apartment']['number'] ?? '';
                    $billingValue  = str_replace("'", '&#039;', $billingValue);
                    $usersideValue = $customerCurrent['address'][0]['apartment']['number'] ?? '';
                    if ($billingValue != $usersideValue) {
                        $this->arrayCustomerToEdit[$usersideId]['apartment_number'] = $billingValue;
                    }

                    //Этаж
                    $billingValue  = $value['address'][0]['floor'] ?? '';
                    $billingValue  = trim($billingValue);
                    $usersideValue = $customerCurrent['address'][0]['floor'] ?? '';
                    if ('' == $billingValue && $confIsUpdateEmptyLevel == 1) {
                        $billingValue = '-1';
                    }
                    if ($billingValue != $usersideValue && '' != $billingValue) {
                        if (strlen($billingValue) == 1 && substr($billingValue, 0, 1) == '0') {

                        } else {
                            if ('-1' == $billingValue) {
                                $billingValue = '';
                            }
                            if ('' != $usersideValue || ($billingValue != '' || $confIsUpdateEmptyLevel == 1)) {
                                $this->arrayCustomerToEdit[$usersideId]['floor'] = $billingValue;
                            }
                        }
                    }

                    //Подъезд
                    $billingValue  = $value['address'][0]['entrance'] ?? 0;
                    $usersideValue = $customerCurrent['address'][0]['entrance'] ?? '';
                    if ($billingValue != $usersideValue) {
                        if (
                            0 < $billingValue
                            ||
                            ($billingValue == -1 && 0 != $usersideValue)
                            ||
                            $confIsUpdateEmptyEntrance == 1
                        ) {
                            if ($billingValue == -1) {
                                $billingValue = 0;
                            }
                            $this->arrayCustomerToEdit[$usersideId]['entrance'] = $billingValue;
                        }
                    }

                }

                //IP-MAC
                $usersideIp  = isset($customerCurrent['ip_mac']) ? $customerCurrent['ip_mac'] : [];
                $ipToAdd     = 0;
                $ipChangeMac = 0;
                $ipToDelete  = 0;
                if (
                    isset($value['ip_mac'])
                    &&
                    is_array($value['ip_mac'])
                ) {
                    //Добавляем белые IP-адреса для серых
                    if (1 == $isAddWhiteIp) {
                        foreach ($value['ip_mac'] as $i => $value2) {
                            $ip = trim($value2['ip']);
                            if ('' != $ip && $ip == 0) {
                                $ip = -1;
                            }
                            if ('' != $ip && 0 < $ip) {
                                if ($ip >= $grayNetIpMin && $ip <= $grayNetIpMax) {
                                    $mac                     = isset($value2['mac']) ? strtolower($value2['mac']) : '';
                                    $ipNew                   = $ip - $whiteNetIp;
                                    $value['ip_mac'][$ipNew] = [
                                        'ip' => $ipNew,
                                        'mac' => $mac
                                    ];
                                    Log::d("Add White Ip " . $ipNew . " For Gray Ip " . $ip . " (customer: " . $id . ")");
                                }
                            }
                        }
                    }

                    foreach ($value['ip_mac'] as $value2) {
                        $ip = isset($value2['ip']) ? trim($value2['ip']) : -1;
                        if (
                            '' != $ip
                            &&
                            $ip == 0
                        ) {
                            $ip = -1;
                        }
                        $mac = isset($value2['mac']) ? strtolower($value2['mac']) : '';
                        if (12 != strlen($mac)) {
                            $mac = '';
                        }
                        if (
                            '' != $ip
                            &&
                            (
                                0 < $ip
                                ||
                                -1 == $ip
                            )
                        ) {
                            if (!isset($usersideIp[$ip])) {
                                //Нет такого IP
                                ++$ipToAdd;
                                $this->arrayCustomerToEdit[$usersideId]['add_ip' . $ipToAdd] = $ip . ',' . $mac;
                            } else {
                                $usersideIp[$ip]['_is_find'] = 1;
                                //Проверяем - такой ли MAC
                                if (1 == $isUpdateMac && $mac != $usersideIp[$ip]['mac'] && '' != $mac) {
                                    ++$ipChangeMac;
                                    //Меняем MAC
                                    $this->arrayCustomerToEdit[$usersideId]['ip_change_mac' . $ipChangeMac] = $ip . ',' . $mac;
                                }
                            }
                        }

                        if (isset($value2['local_ip'])) {
                            $ip = trim($value2['local_ip']);
                            if ('' != $ip && 0 < $ip && $ip != trim($value2['ip'])) {
                                if (!isset($usersideIp[$ip])) {
                                    //Нет такого IP
                                    ++$ipToAdd;
                                    $this->arrayCustomerToEdit[$usersideId]['add_ip' . $ipToAdd] = $ip . ',' . $mac;
                                } else {
                                    $usersideIp[$ip]['_is_find'] = 1;
                                    //Проверяем - такой ли MAC
                                    if (1 == $isUpdateMac && $mac != $usersideIp[$ip]['mac'] && '' != $mac) {
                                        ++$ipChangeMac;
                                        //Меняем MAC
                                        $this->arrayCustomerToEdit[$usersideId]['ip_change_mac' . $ipChangeMac] = $ip . ',' . $mac;
                                    }
                                }
                            }
                        }

                    }
                    //Убираем ненайдённые IP (только при условии, что у этого абонента есть иные IP-адреса либо включена опция)
                    foreach ($usersideIp as $ip => $value2) {
                        if (
                            !isset($value2['_is_find'])
                            &&
                            (
                                1 == $confIsForceDeleteEmptyIp
                                ||
                                1 != $confIsSkipDeleteEmptyIp
                                ||
                                $ip > 0
                            )
                        ) {
                            if (is_array($confSkipDeleteIp)) {
                                $isDelete = 1;
                                foreach ($confSkipDeleteIp as $y => $value3) {
                                    $minIp = $value3[0];
                                    $maxIp = $value3[1];
                                    if ($ip >= $minIp && $ip <= $maxIp) {
                                        $isDelete = 0;
                                        break;
                                    }
                                }
                            } else {
                                $isDelete = 1;
                            }
                            if (1 == $isDelete) {
                                ++$ipToDelete;
                                $this->arrayCustomerToEdit[$usersideId]['delete_ip' . $ipToDelete] = $ip;
                            }
                        }
                    }
                } else {
                    //Если из биллинга нет ИП - то удаляем все ИП в UserSide
                    if (
                        1 != $confIsSkipDeleteEmptyIp
                        &&
                        count($usersideIp) > 0
                    ) {
                        foreach ($usersideIp as $ip => $value2) {
                            ++$ipToDelete;
                            $this->arrayCustomerToEdit[$usersideId]['delete_ip' . $ipToDelete] = $ip;
                        }
                    }
                }

                //Статус
                $billingValue      = isset($value['state_id']) ? $value['state_id'] : 0;
                $stateFunctionalId = isset($this->arrayBillingState[$billingValue]['functional']) ? $this->arrayBillingState[$billingValue]['functional'] : 'notfound';
                switch ($stateFunctionalId) {
                    case 'disable':
                    case 'nomoney':
                    case 'stop':
                        $billingValue = 0; //Stop
                        break;
                    case 'pause':
                        $billingValue = 1; //Pause
                        break;
                    case 'work':
                        $billingValue = 2; //Play
                        break;
                    case 'new':
                        $billingValue = 0; //Stop - TT-24066 - Не убирать т.к. теряются статусы
                        break;
                    default:
                        //Не мяенем - чтобы использовать доп.статусы
                        break;
                }
                if (strlen($billingValue) > 5) {
                    $billingValue = 0;
                }
                $usersideValue = isset($customerCurrent['state_id']) ? $customerCurrent['state_id'] : 0;
                if ($billingValue != $usersideValue) {
                    $this->arrayCustomerToEdit[$usersideId]['state_id'] = $billingValue;
                }

                //Дата активности
                if ($confIsSkipUpdateDateActivity != 1) {
                    $billingValue = $value['date_activity'] ?? '1970-01-01';
                    if ($isCustomerActivityTimestamp == 1) {
                        $billingValue  = strtotime($billingValue);
                        $usersideValue = isset($customerCurrent['date_activity_inet_unix']) ? floor($customerCurrent['date_activity_inet_unix']) : 0;
                        if (
                            $billingValue > 1000000
                            &&
                            $billingValue != $usersideValue
                        ) {
                            $this->arrayCustomerToEdit[$usersideId]['date_activity_inet_unix'] = $billingValue;
                        }
                    } else {
                        $usersideValue = isset($customerCurrent['date_activity_inet']) ? $customerCurrent['date_activity_inet'] : '1970-01-01';
                        if ($billingValue != $usersideValue && '' != $billingValue && '1970-01-01' != date('Y-m-d',
                                strtotime($billingValue))) {
                            $this->arrayCustomerToEdit[$usersideId]['date_activity_inet'] = $billingValue;
                        }
                    }
                }

                //Доп.данные
                if (0 < count($additionalDataMerge)) {
                    $additionalData = isset($value['additional_data']) ? $value['additional_data'] : [];
                    foreach ($additionalData as $dataId => $array) {
                        if (isset($additionalDataMerge[$dataId])) {
                            $billingValue = $array['value'];
                            //Меняем ID поля биллинга на ID поля userside
                            $dataUsId      = $additionalDataMerge[$dataId];
                            $usersideValue = isset($customerCurrent['additional_data'][$dataUsId]['value']) ? $customerCurrent['additional_data'][$dataUsId]['value'] : '';
                            if ($billingValue != $usersideValue) {
                                //Log::w($usersideId . "|Billing: " . $billingValue. " (" . $dataId . ")| US: " . $usersideValue . " (" . $dataUsId . ")");
                                $this->arrayCustomerToEdit[$usersideId]['additional_data'][$dataUsId] = $billingValue;
                            }
                        }
                    }
                }
                if (0 < count($additionalCustomerDataMerge)) {
                    if (isset($value['additional_customer_data'])) {
                        $additionalData = $value['additional_customer_data'];
                    } elseif (isset($value['additional_data'])) {
                        $additionalData = $value['additional_data'];
                    } else {
                        $additionalData = [];
                    }
                    foreach ($additionalData as $dataId => $array) {
                        if (isset($additionalCustomerDataMerge[$dataId])) {
                            $billingValue = $array['value'];
                            //Меняем ID поля биллинга на ID поля userside
                            $dataUsId      = $additionalCustomerDataMerge[$dataId];
                            $usersideValue = isset($customerCurrent['additional_customer_data'][$dataUsId]['value']) ? $customerCurrent['additional_customer_data'][$dataUsId]['value'] : '';
                            if ($billingValue != $usersideValue) {
                                //Log::w($usersideId . "|Billing: " . $billingValue. " (" . $dataId . ")| US: " . $usersideValue . " (" . $dataUsId . ")");
                                $this->arrayCustomerToEdit[$usersideId]['additional_customer_data'][$dataUsId] = $billingValue;
                            }
                        }
                    }
                }

                //Отметки (булевые)
                if (is_array($markMerge) && 0 < count($markMerge)) {
                    $mark = isset($value['mark']) ? $value['mark'] : [];
                    foreach ($mark as $markBillingId => $markUsArray) {
                        if (isset($markMerge[$markBillingId])) {
                            foreach ($markUsArray as $j => $markUsId) {
                                if (!isset($customerCurrent['mark'][$markUsId]['id'])) {
                                    //Если нет такой отметки - нужно добавить
                                    $this->arrayCustomerToEdit[$usersideId]['mark'][$markUsId] = 1;
                                } else {
                                    //Отметка уже есть
                                }
                            }
                        }
                    }
                }

                //Метки
                if (0 < count($this->arrayBillingTags)) {
                    $tag = $value['tag'] ?? [];
                    foreach ($tag as $i => $array) {
                        $tagId = $array['id'];
                        //Получаем ID метки в UserSide
                        if (0 < $tagId && isset($this->arrayBillingTags[$tagId])) {
                            $tagUsId = $this->arrayBillingTags[$tagId]['us_id'];
                            if (0 < $tagUsId) {
                                if (!isset($customerCurrent['tag'][$tagUsId])) {
                                    //echo 'add tag: ' . $usersideId . "|" . $tagId . "=" . $tagUsId . "|\n";
                                    $date_add                                                = isset($array['date_add']) ? $array['date_add'] : 1;
                                    $this->arrayCustomerToEdit[$usersideId]['tag'][$tagUsId] = $date_add;
                                } else {
                                    $customerCurrent['tag'][$tagUsId]['is_find'] = 1;
                                }
                            }
                        }
                    }
                    //Проверяем метки, что нужно удалить
                    if (isset($customerCurrent['tag'])) {
                        foreach ($customerCurrent['tag'] as $tagUsId => $array) {
                            if (!isset($array['is_find'])) {
                                $billingTagId = isset($tagsUserside[$tagUsId]['billing_id']) ? $tagsUserside[$tagUsId]['billing_id'] : 0;
                                if (0 < $billingTagId) {
                                    //Значит метки в биллинге уже нет - надо снимать и в userside
                                    //echo 'delete tag: ' . $usersideId . "|" . $billingTagId . "=" . $tagUsId . "|\n";
                                    $this->arrayCustomerToEdit[$usersideId]['tag'][$tagUsId] = -1;
                                }
                            }
                        }
                    }
                }

                //Группы
                if (0 < count($this->arrayBillingGroup)) {
                    if ($confAlwaysSetCustomerGroupId != '') {
                        $value['group'][$confAlwaysSetCustomerGroupId] = true;
                    }
                    $groupInBillingArray = $value['group'] ?? [];
                    foreach ($groupInBillingArray as $groupId => $array) {
                        //Получаем ID группы в UserSide
                        if ('' != $groupId) {
                            $groupUsId = isset($this->arrayBillingGroup[$groupId]['us_id']) ? $this->arrayBillingGroup[$groupId]['us_id'] : 0;
                            if (0 < $groupUsId) {
                                if (!isset($customerCurrent['group'][$groupUsId])) {
                                    $this->arrayCustomerToEdit[$usersideId]['group']                    = [//Старый формат (без мультигрупности)
                                                                                                           $groupUsId => 1
                                    ];
                                    $this->arrayCustomerToEdit[$usersideId]['add_group_id'][$groupUsId] = $groupUsId; //Новый формат (с мультигруппами)
                                } else {
                                    $customerCurrent['group'][$groupUsId]['is_find'] = 1;
                                }
                            }
                        }
                    }
                    //Проверяем группы, что нужно удалить
                    if (isset($customerCurrent['group'])) {
                        foreach ($customerCurrent['group'] as $groupUsId => $array) {
                            if (!isset($array['is_find'])) {
                                $billingGroupId = isset($groupUserside[$groupUsId]['billing_id']) ? $groupUserside[$groupUsId]['billing_id'] : 0;
                                //if (0 < $billingGroupId) { - TT-25523 - если удалить группу из биллинга - то она так и останется висеть на абонентах
                                //Значит группы в биллинге уже нет - надо снимать и в userside
                                $this->arrayCustomerToEdit[$usersideId]['group'][$groupUsId]           = -1; //Старый формат (без мультигрупности)
                                $this->arrayCustomerToEdit[$usersideId]['delete_group_id'][$groupUsId] = $groupUsId; //Новый формат (с мультигруппами)
                                //}
                            }
                        }
                    }
                }

                //Услуги
                if (0 < count($serviceBilling)) {
                    $service = $value['service'] ?? [];
                    foreach ($service as $serviceId => $array) {
                        if (isset($serviceBilling[$serviceId]['userside_id'])) {
                            $serviceUsId = $serviceBilling[$serviceId]['userside_id'];
                            if ($serviceUsId > 0) {
                                foreach ($array as $i => $array2) {
                                    if (isset($customerCurrent['service'][$serviceUsId][$i])) {
                                        //Есть такая услуга у абонента
                                        $customerCurrent['service'][$serviceUsId][$i]['is_find'] = 1;
                                        //Проверяем стоимость
                                        $usCost = $customerCurrent['service'][$serviceUsId][$i]['cost'];
                                        if (isset($array2['cost']) && $array2['cost'] != $usCost) {
                                            $this->arrayCustomerToEdit[$usersideId]['service_cost'][$serviceUsId] = $array2['cost'];
                                        }
                                        //Проверяем заметки
                                        $usComment = $customerCurrent['service'][$serviceUsId][$i]['comment'];
                                        if (isset($array2['comment']) && $array2['comment'] != $usComment) {
                                            $this->arrayCustomerToEdit[$usersideId]['service_comment'][$serviceUsId] = $array2['comment'];
                                        }
                                    } else {
                                        //Нет услуги - надо добавить
                                        $this->arrayCustomerToEdit[$usersideId]['service'][$serviceUsId] = 1;
                                    }
                                }
                            }
                        }
                    }
                    //Удалить неиспользуемые услуги
                    if (isset($customerCurrent['service'])) {
                        foreach ($customerCurrent['service'] as $serviceUsId => $array) {
                            foreach ($array as $i => $array2) {
                                if (!isset($customerCurrent['service'][$serviceUsId][$i]['is_find'])) {
                                    $this->arrayCustomerToEdit[$usersideId]['service'][$serviceUsId] = -1;
                                }
                            }
                        }
                    }
                }

                //Коммутация
                if (1 == $this->isLoadCustomerCommutation) {
                    if (isset($value['commutation'])) {
                        $billingSwitchIp   = $value['commutation']['device_ip'];
                        $billingSwitchPort = $value['commutation']['port'];
                        $usSwitchIp        = $customerCurrent['commutation']['device_ip'] ?? 0;
                        $usSwitchPort      = $customerCurrent['commutation']['port'] ?? -1;
                        if ($billingSwitchIp != $usSwitchIp || $billingSwitchPort != $usSwitchPort) {
                            //Проверяем - есть ли нужный коммутатор
                            if (isset($switch[$billingSwitchIp])) {
                                //Есть коммутатор. Коммутируем
                                $usSwitchId                                                                = $switch[$billingSwitchIp]['id'];
                                $this->arrayCustomerToEdit[$usersideId]['commutation_switch'][$usSwitchId] = $billingSwitchPort;
                            }
                        }
                    }
                }

                //Если стоит отметка, что нет в биллинге - убираем
                if (1 != $customerCurrent['is_in_billing']) {
                    $this->arrayCustomerToEdit[$usersideId]['is_in_billing'] = 1;
                }

                if (isset($this->arrayCustomerToEdit[$usersideId])) {
                    $this->arrayCustomerToEdit[$usersideId]['crc_billing'] = $customerCrc;
                }
            }
        }

        //Если каких-то абонентов нет в UserSide - то нужно добавить
        if (0 < count($this->arrayCustomerToAdd)) {
            $this->addCustomerToUserside();
        }

        //Абонентов, что не найдены в биллинге - помечаем
        if (is_array($userside)) {
            foreach ($userside as $id => $value) {
                if (!isset($value['isFindInBilling']) && 1 == $value['is_in_billing']) {
                    //Не найден в биллинге
                    $usersideId                                              = $value['userside_id'];
                    $this->arrayCustomerToEdit[$usersideId]['is_in_billing'] = 0;
                    $this->arrayCustomerToEdit[$usersideId]['crc_billing']   = 'n-a';

                    Log::w("Customer usersideId: '" . $usersideId . "' Not Found In Billing.");

                }
            }
        }

        //Если есть изменения по абонентам - обновляем
        if (0 < count($this->arrayCustomerToEdit)) {
            $this->editCustomerInUserside();
        }

        //Импорт истории платежей
        if (is_array($customerPaidHistory) && 0 < count($customerPaidHistory)) {
            foreach ($customerPaidHistory as $i => $value) {
                $customerCurrentId = $value['user_id'];
                if (isset($userside[$customerCurrentId])) {
                    $usersideId                             = $userside[$customerCurrentId]['userside_id'];
                    $customerPaidHistory[$i]['userside_id'] = $usersideId;
                    $customerPaidHistory[$i]['erp_id']      = $usersideId;
                    unset($customerPaidHistory[$i]['user_id']);
                } else {
                    unset($customerPaidHistory[$i]);
                }
            }
            $this->importPaidHistory($customerPaidHistory);
        }

        //Импортируем сообщения из биллинга
        if (is_array($this->arrayBillingMsg) && 0 < count($this->arrayBillingMsg)) {
            Log::w("Import Msg");
            foreach ($this->arrayBillingMsg as $i => $bundle) {
                if (
                    'edit' != $i
                    &&
                    isset($bundle['user_id'])
                ) {
                    $customerId = $bundle['user_id'];
                    if (isset($userside[$customerId])) {
                        $usersideId                     = $userside[$customerId]['userside_id'];
                        $bundle['customer_userside_id'] = $usersideId;
                        $this->addMsgToUserside($bundle);
                    }
                } else {
                    if (is_array($bundle)) {
                        foreach ($bundle as $j => $bundle2) {
                            //$this->editMsgInUserside($bundle2);
                        }
                    }
                }
            }
        }

    }

}

class Tariff
{

    public function loadFromBilling()
    {
        global $api, $billing, $billingName;
        Log::w("Load Tariff From Billing");
        if ('standart' == $billingName) {
            $json = $api->getDataFromBilling("&request=get_tariff_list", 1);
        } elseif ('carbon5' == $billingName) {
            $json = $api->getDataFromBilling("&method1=userside_manager.get_tariff_list", 1);
        } elseif ('abills' == $billingName) {
            $json = $api->getDataFromBilling("&method1=userside_manager.get_tariff_list", 1);
        } elseif ('nodenyplus' == $billingName) {
            $array = $api->getDataFromBilling('&a=api_services', 1);
            $json  = NodenyPlus::tariffDataDecode($array);
        } else {
            $json = $billing->getTariffList();
        }
        Log::w("Count: " . count($json));
        return $json;
    }

    public function loadFromUserside()
    {
        global $api, $billingId, $moduleHelper;
        Log::w("Load Tariff From UserSide");
        $json = $api->getDataFromUserside("&request=get_tariff_list&billing_id=" . $billingId);
        if (!is_array($json) || 1 > count($json)) {
            Log::i("Error Load Tarif From UserSide");
            $moduleHelper->finishModule();
        }
        foreach ($json as $i => $value) {
            if (
                !isset($value['userside_id'])
                &&
                isset($value['erp_id'])
            ) {
                $json[$i]['userside_id'] = $value['erp_id'];
            }
        }
        Log::w("Count: " . count($json));
        return $json;
    }

    private function addTariffToUserside($array)
    {
        global $api, $billingId;
        $id   = $array['id'];
        $name = $array['name'];
        Log::d("Add Tariff To UserSide. Id: '" . $id . "' Name: '" . $name . "'");
        $json = $api->sendDataToUserside("&cat=tariff&action=add&billing_id=" . $billingId . "&billing_tariff_id=" . $id . "&name=" . $name);
        if (isset($json['Id'])) {
            Log::d("Adding OK. Id: " . $json['Id']);
        } elseif (isset($json['id'])) {
            Log::d("Adding OK. id: " . $json['id']);
        } else {
            Log::d("Adding ERROR");
        }
        return $json;
    }

    private function editGroupTariffInUserside($bundle)
    {
        global $api;
        Log::d("Group Edit Tariff In UserSide");
        $post             = [
            'cat' => 'tariff',
            'action' => 'group_edit',
            'data' => json_encode($bundle)
        ];
        $additionalString = '';
        foreach ($post as $i => $value) {
            $additionalString .= '&' . $i . '=' . $value;
        };
        $json = $api->sendDataToUserside($additionalString, $post);
        if (
            (
                isset($json['Result'])
                &&
                'OK' == $json['Result']
            )
            ||
            (
                isset($json['result'])
                &&
                'OK' == $json['result']
            )
        ) {
            Log::d("Editing OK");
            return true;
        }

        Log::d("Editing ERROR");
        return false;
    }

    private function editTariffInUserside($id, $bundle)
    {
        global $api;
        Log::d("Edit Tariff In UserSide. UserSide Id: '" . $id . "'");
        $post = [
            'cat' => 'tariff',
            'action' => 'edit',
            'id' => $id
        ];
        foreach ($bundle as $i => $value) {
            $post[$i] = $value;
        };
        $additionalString = '';
        foreach ($post as $i => $value) {
            $additionalString .= '&' . $i . '=' . $value;
        };
        $json = $api->sendDataToUserside($additionalString, $post);
        if (
            (
                isset($json['Result'])
                &&
                'OK' == $json['Result']
            )
            ||
            (
                isset($json['result'])
                &&
                'OK' == $json['result']
            )
        ) {
            Log::d("Editing OK");
        } else {
            Log::d("Editing ERROR");
        }
    }

    public function compareTariff($billing, $userside)
    {
        global $logPath, $moduleName;
        Log::w("Compare Tariff");

        $arrayEditTariffInUs = [];

        //Проходим все тарифы из биллинга
        foreach ($billing as $id => $value) {
            $name = $value['name'];
            $name = str_replace('/', '&#047;', $name);
            $name = mb_substr($name, 0, 255);

            //Есть ли такой тариф в UserSide
            //$id = strtoupper($id);
            if (!isset($userside[$id])) {
                //Нужно добавить тариф в UserSide
                Log::w("Tariff '" . $id . "' Not Found In UserSide. Adding...");
                $bundle = [
                    'id' => $id,
                    'name' => $name
                ];
                $this->addTariffToUserside($bundle);
            } else {
                //Этот тариф есть. Нужно сверить данные
                $userside[$id]['isFindInBilling'] = 1;
                $arrayChange                      = [];

                $tariff = $userside[$id];
                if (!isset($tariff['service_type'])) {
                    $tariff['service_type'] = 0;
                }
                if (!isset($tariff['payment'])) {
                    $tariff['payment'] = 0;
                }

                //Название
                if ($tariff['name'] != $name) {
                    $arrayChange['name'] = $name;
                    Log::d("Tariff Data. Not Right 'name' US: '" . $tariff['name'] . "' - Billing: '" . $name . "' | UserSideId: " . $tariff['userside_id']);
                }

                //Абонплата
                //$tariff['payment'] = ceil(round($tariff['payment'], 2) * 100) * 0.01; - нужна точность (ТТ-20548)
                $tariff['payment'] = ceil(round($tariff['payment'], 5) * 1000000) * 0.000001;
                if (isset($value['payment'])) {
                    //$value['payment'] = ceil(round($value['payment'], 2) * 100) * 0.01;
                    $value['payment'] = ceil(round($value['payment'], 5) * 1000000) * 0.000001;
                    if (0 == $value['payment'] && isset($value['payment_interval'])) {
                        $value['payment_interval'] = 30;
                    }
                    if (!isset($value['payment_interval'])) {
                        $value['payment_interval'] = 30;
                    }
                    if ($value['payment'] != $tariff['payment']) {
                        $arrayChange['payment']          = $value['payment'];
                        $arrayChange['payment_interval'] = $value['payment_interval'];
                        Log::d("Tariff Data. Not Right 'payment' ('" . $name . "') US: '" . $tariff['payment'] . "' (payment: " . $tariff['payment_interval'] . ") - Billing: '" . $value['payment'] . "' (payment: " . $value['payment_interval'] . ") | UserSideId: " . $tariff['userside_id']);
                    }
                }

                //Интервал оплаты
                if (isset($value['payment_interval'])) {
                    if (1 > $value['payment_interval']) {
                        $value['payment_interval'] = 30;
                    }
                    if (29 == $value['payment_interval'] || 31 == $value['payment_interval']) {
                        $value['payment_interval'] = 30;
                    }
                    if (92 == $value['payment_interval']) {
                        $value['payment_interval'] = 90;
                    }
                    if (183 == $value['payment_interval']) {
                        $value['payment_interval'] = 180;
                    }
                    if (366 == $value['payment_interval']) {
                        $value['payment_interval'] = 360;
                    }
                    if ($value['payment_interval'] != $tariff['payment_interval']) {
                        $arrayChange['payment_interval'] = $value['payment_interval'];
                        $arrayChange['payment']          = $value['payment'];
                        Log::d("Tariff Data. Not Right 'payment_interval' ('" . $name . "') US: '" . $tariff['payment_interval'] . "' (payment: " . $tariff['payment'] . ") - Billing: '" . $value['payment_interval'] . "' (payment: " . $value['payment'] . ") | UserSideId: " . $tariff['userside_id']);
                    }
                }

                //Скорость
                if (isset($value['speed']['up']) && $value['speed']['up'] != $tariff['speed']['up']) {
                    $arrayChange['speed_tx'] = $value['speed']['up'];
                }
                if (isset($value['speed']['down']) && $value['speed']['down'] != $tariff['speed']['down']) {
                    $arrayChange['speed_rx'] = $value['speed']['down'];
                }

                //Трафик
                if (isset($value['traffic']) && $value['traffic'] != $tariff['traffic']) {
                    $arrayChange['traffic'] = $value['traffic'];
                }

                //Тип тарифа
                if (isset($value['service_type']) && $value['service_type'] != $tariff['service_type']) {
                    $arrayChange['service_type'] = $value['service_type'];
                }

                //Если стоит отметка, что нет в биллинге - убираем
                if (1 != $tariff['is_in_billing']) {
                    $arrayChange['is_in_billing'] = 1;
                }

                //Дополнительные данные
                if (!isset($tariff['additional_comment'])) {
                    $tariff['additional_comment'] = '';
                }
                if (isset($value['additional_comment']) && $value['additional_comment'] != $tariff['additional_comment']) {
                    $arrayChange['additional_comment'] = $value['additional_comment'];
                }

                //Есть изменения
                if (0 < count($arrayChange)) {
                    $arrayEditTariffInUs[$tariff['userside_id']] = $arrayChange;
                }

            }

        }

        //Вносим изменения
        if (count($arrayEditTariffInUs) > 0) {
            $responce = $this->editGroupTariffInUserside($arrayEditTariffInUs);
            if (true !== $responce) {
                foreach ($arrayEditTariffInUs as $usersideId => $value) {
                    $this->editTariffInUserside($usersideId, $value);
                }
            }
        }

        //Тарифы, что не найдены в биллинге - помечаем
        foreach ($userside as $value) {
            if (
                !isset($value['isFindInBilling'])
                &&
                1 == $value['is_in_billing']
            ) {
                //Не найден в биллинге
                $bundle     = [
                    'is_in_billing' => 0
                ];
                $usersideId = $value['userside_id'];
                $this->editTariffInUserside($usersideId, $bundle);
            }
        }
    }

}

class Service
{

    public function loadFromBilling()
    {
        global $api, $billing, $billingName;
        Log::w("Load Service From Billing");
        if ('standart' == $billingName) {
            $json = $api->getDataFromBilling("&request=get_services_list", 1);
        } elseif ('carbon5' == $billingName) {
            $json = [];
        } elseif ('abills' == $billingName) {
            $json = [];
        } elseif ('nodenyplus' == $billingName) {
            $json = [];
        } else {
            $json = $billing->getServiceList();
            Log::d(json_encode($json, JSON_UNESCAPED_UNICODE));
        }
        if (!is_array($json)) {
            $json = [];
        }
        Log::w("Count: " . count($json));
        return $json;
    }

    public function loadFromUserside()
    {
        global $api, $billingId, $moduleHelper;
        Log::w("Load Service From UserSide");
        $json = $api->getDataFromUserside("&request=get_services_list&is_id_name=1&billing_id=" . $billingId);
        Log::w("Count: " . count($json));
        return $json;
    }

    private function addServiceToUserside($array)
    {
        global $api, $billingId;
        $name = $array['name'];
        Log::d("Add Service To UserSide. Name: '" . $name . "'");
        $json = $api->sendDataToUserside("&cat=service&action=add&billing_id=" . $billingId . "&name=" . urlencode($name));
        if (isset($json['id'])) {
            Log::d("Adding OK. id: " . $json['id']);
        } else {
            Log::d("Adding ERROR");
        }
        return $json;
    }

    private function editServiceInUserside($id, $bundle)
    {
        global $api;
        Log::d("Edit Service In UserSide. UserSide Id: '" . $id . "'");
        $post = [
            'cat' => 'service',
            'action' => 'edit',
            'id' => $id
        ];
        foreach ($bundle as $i => $value) {
            $post[$i] = $value;
        };
        $additionalString = '';
        foreach ($post as $i => $value) {
            $additionalString .= '&' . $i . '=' . $value;
        };
        $json = $api->sendDataToUserside($additionalString, $post);
        if (
            (
                isset($json['Result'])
                &&
                'OK' == $json['Result']
            )
            ||
            (
                isset($json['result'])
                &&
                'OK' == $json['result']
            )
        ) {
            Log::d("Editing OK");
        } else {
            Log::d("Editing ERROR");
        }
    }

    public function compareService()
    {
        global $serviceBilling, $serviceUserside;
        Log::w("Compare Service");
        //Проходим все услуги из биллинга
        if (is_array($serviceBilling)) {
            foreach ($serviceBilling as $id => $value) {
                if (isset($value['name'])) {
                    $name          = $value['name'];
                    $name          = str_replace('/', '&#047;', $name);
                    $idName        = md5($name);
                    $value['cost'] = str_replace(',', '.', $value['cost']);

                    //Есть ли такая услуга в UserSide
                    if (!isset($serviceUserside[$idName])) {
                        //Нужно добавить услугу в UserSide
                        Log::w("Service '" . $id . "' Not Found In UserSide. Adding...");
                        $bundle                             = [
                            'name' => $name
                        ];
                        $responce                           = $this->addServiceToUserside($bundle);
                        $serviceBilling[$id]['userside_id'] = $responce['id'];
                    } else {
                        //Эта услуга есть. Нужно сверить данные
                        $service                            = $serviceUserside[$idName];
                        $serviceBilling[$id]['userside_id'] = $service['id'];

                        $arrayChange = [];

                        //Стоимость
                        if ($service['cost'] != $value['cost']) {
                            $arrayChange['cost'] = $value['cost'];
                            Log::d("Service Data. Not Right 'cost' US: '" . $service['cost'] . "' - Billing: '" . $value['cost'] . "'");
                        }

                        //Есть изменения
                        if (0 < count($arrayChange)) {
                            $this->editServiceInUserside($service['id'], $arrayChange);
                        }

                    }

                }
            }
            Log::d(json_encode($serviceBilling));
        }
    }

}

class ModuleHelper
{
    public $lockFile; //Путь к файлу блокировки
    public $moduleApiName;

    public static function replaceNonUtfSymbol($text)
    {
        $unwanted_array = [
            'Š' => 'S',
            'š' => 's',
            'Ž' => 'Z',
            'ž' => 'z',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'A',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ø' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'B',
            'ß' => 'Ss',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'a',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'o',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'þ' => 'b',
            'ÿ' => 'y',
            'Nº' => 'No',
            'nº' => 'no',
            "'" => '&#039;'
        ];
        $text           = strtr($text, $unwanted_array);
        return $text;
    }

    public function systemOperation()
    {
        global $api, $billingId;
        //Осуществляем в UserSide все необходимые операции об окончании работы модуля
        $additionalUrl = "&cat=usm_billing&action=system_operation&billing_id=" . $billingId;
        $api->sendDataToUserside($additionalUrl);
    }

    public function initializeModule()
    {
        global $logPath, $logFile, $moduleInformation, $moduleName, $moduleVersion, $moduleRevision, $isSilence;
        global $usersideUrl, $billingId, $billingName, $api, $billing, $moduleHelper, $isAddOnModule, $isUpdateTraffic;
        global $isUpdateAddress, $moduleTimeStart, $lastPaidId, $lastMsgId, $confIpGrayNet, $confIpWhiteNet;
        global $confIsSavePasswordToComment, $confIsImportLessPhone, $confIsSkipUpdateAgreementDate, $rowLimit;
        global $confIsDisableCreateAddress, $isNewHouseBillingCompare, $confIsDoNotUpdateAddPhone;
        global $isEncodeCustomerPassword, $confIsSkipParentLink, $confIsImportSwitchCommutation, $customer;
        global $confIsSkipDeleteEmptyIp, $confSkipDeleteIp, $billingDbProvider, $confIsImportLessAddress;
        global $confIsUpdateEmptyLevel, $confIsUpdateEmptyEntrance, $confIsImportPasswordToUsPassword, $isUpdateMac;
        global $confIsSkipUpdateDateActivity, $confIsSkipUnusedAddress, $addressCompareFormat, $apiGroupCounter;
        global $apiGroupCounter2, $apiGroupHouseCounter, $isWithoutLog, $confAlwaysSetCustomerGroupId;
        global $isCustomerActivityTimestamp, $confIsUseStreetFullName, $moduleCoreRevision, $confIsImportMessage;
        global $billingCrcId, $logDebugFile, $isDebugMode, $confIsForceDeleteEmptyIp, $confIsSkipSyncCustomerIsCorporate;

        $moduleTimeStart = microtime();

        $moduleInformation = $moduleName . " v." . $moduleVersion; // Version
        if ('' != $moduleRevision) {
            $moduleInformation .= '.' . $moduleRevision;
        }

        $this->moduleApiName = 'usm_billing';
        if (1 == $isAddOnModule) {
            $this->moduleApiName = $moduleName;
        }

        if ('/' != substr($logPath, -1, 1)) {
            $logPath .= '/';
        }

        Log::c($moduleInformation);
        Log::c("====================================");
        Log::c(" Start module at " . date('Y-m-d H:i:s'));

        Log::a("====================================");
        Log::a($moduleInformation . " - Module Start");

        $logFile          = $logPath . $moduleName . '.log';
        Log::$logDataBase = $logPath . $moduleName . '_db.log';
        Log::$logRaw      = $logPath . $moduleName . '_raw_';
        $logDebugFile     = $logPath . $moduleName . '_debug.log';

        //Проверяем - есть ли файл блокировки и что там за время
        $this->lockFile = $logPath . $moduleName . '.lock';

        if (true == file_exists($this->lockFile)) {
            $file    = fopen($this->lockFile, "r");
            $content = fread($file, filesize($this->lockFile));
            fclose($file);
            if ('' == $content) {
                //Удаляем файл блокировки
                unlink($this->lockFile);
            } else {
                //Считаем - сколько времени было с последнего запуска и до сейчас
                $dateDiff = time() - $content;
                if ($dateDiff > 7200) {
                    unlink($this->lockFile);
                } else {
                    Log::c('Error: Find Lockfile');
                    Log::a($moduleInformation . " - Module Terminated - Find Lockfile");
                    $this->terminateModule();
                }
            }
        }
        //Создаём файл блокировки
        if (1 != $isWithoutLog) {
            @$file = fopen($this->lockFile, "w");
            @fwrite($file, time());
            @fclose($file);

            if (true != file_exists($this->lockFile)) {
                Log::c('Verify Logs Path: ' . $logPath);
                $this->terminateModule();
            }
        }

        //Удаляем старый лог
        if (true == file_exists($logFile)) {
            unlink($logFile);
        }
        if (true == file_exists($logDebugFile)) {
            unlink($logDebugFile);
        }
        if (true == file_exists(Log::$logDataBase)) {
            unlink(Log::$logDataBase);
        }
        if (true == file_exists(Log::$logRaw . 'tags.log')) {
            unlink(Log::$logRaw . 'tags.log');
        }
        if (true == file_exists(Log::$logRaw . 'tariff.log')) {
            unlink(Log::$logRaw . 'tariff.log');
        }
        if (true == file_exists(Log::$logRaw . 'tariff_2.log')) {
            unlink(Log::$logRaw . 'tariff_2.log');
        }
        if (true == file_exists(Log::$logRaw . 'tariff_period.log')) {
            unlink(Log::$logRaw . 'tariff_period.log');
        }
        if (true == file_exists(Log::$logRaw . 'customer.log')) {
            unlink(Log::$logRaw . 'customer.log');
        }
        if (true == file_exists(Log::$logRaw . 'customer_account.log')) {
            unlink(Log::$logRaw . 'customer_account.log');
        }
        if (true == file_exists(Log::$logRaw . 'customer_address.log')) {
            unlink(Log::$logRaw . 'customer_address.log');
        }
        if (true == file_exists(Log::$logRaw . 'customer_ip.log')) {
            unlink(Log::$logRaw . 'customer_ip.log');
        }
        if (true == file_exists(Log::$logRaw . 'address.log')) {
            unlink(Log::$logRaw . 'address.log');
        }
        if (true == file_exists(Log::$logRaw . 'hierarchy_conflict.log')) {
            unlink(Log::$logRaw . 'hierarchy_conflict.log');
        }

        //Начинаем писать в лог
        Log::w($moduleInformation . " - Module Start");
        Log::w("Module Path                      : " . realpath(__DIR__)); //Путь к модулю
        Log::w("Log Path                         : " . $logPath);
        Log::w("UserSide Path                    : " . $usersideUrl);
        Log::w("isSilence                        : " . $isSilence);
        Log::w("isDebugMode                      : " . $isDebugMode);
        Log::w("billingName                      : " . $billingName);
        Log::w("billingId                        : " . $billingId);
        Log::w("billingCrcId                     : " . $billingCrcId);
        Log::w("billingDbProvider                : " . $billingDbProvider);
        Log::w("confIpGrayNet                    : " . $confIpGrayNet);
        Log::w("confIpWhiteNet                   : " . $confIpWhiteNet);
        Log::w("confIsImportLessPhone            : " . $confIsImportLessPhone);
        Log::w("confIsImportLessAddress          : " . $confIsImportLessAddress);
        Log::w("confIsSavePasswordToComment      : " . $confIsSavePasswordToComment);
        Log::w("confIsSkipUpdateAgreementDate    : " . $confIsSkipUpdateAgreementDate);
        Log::w("confIsDisableCreateAddress       : " . $confIsDisableCreateAddress);
        Log::w("confIsDoNotUpdateAddPhone        : " . $confIsDoNotUpdateAddPhone);
        Log::w("confIsSkipParentLink             : " . $confIsSkipParentLink);
        Log::w("confIsImportSwitchCommutation    : " . $confIsImportSwitchCommutation);
        Log::w("confIsSkipDeleteEmptyIp          : " . $confIsSkipDeleteEmptyIp);
        Log::w("confSkipDeleteIp                 : " . serialize($confSkipDeleteIp));
        Log::w("confIsForceDeleteEmptyIp         : " . $confIsForceDeleteEmptyIp);
        Log::w("confIsUpdateEmptyLevel           : " . $confIsUpdateEmptyLevel);
        Log::w("confIsUpdateEmptyEntrance        : " . $confIsUpdateEmptyEntrance);
        Log::w("confIsImportPasswordToUsPassword : " . $confIsImportPasswordToUsPassword);
        Log::w("confIsSkipUpdateDateActivity     : " . $confIsSkipUpdateDateActivity);
        Log::w("confIsSkipUnusedAddress          : " . $confIsSkipUnusedAddress);
        Log::w("confAlwaysSetCustomerGroupId     : " . $confAlwaysSetCustomerGroupId);
        Log::w("apiGroupCounter                  : " . $apiGroupCounter);
        Log::w("apiGroupCounter2                 : " . $apiGroupCounter2);
        Log::w("apiGroupHouseCounter             : " . $apiGroupHouseCounter);
        Log::w("isWithoutLog                     : " . $isWithoutLog);
        Log::w("confIsUseStreetFullName          : " . $confIsUseStreetFullName);
        Log::w("confIsImportMessage              : " . $confIsImportMessage);
        Log::w("confIsSkipSyncCustomerIsCorporate: " . $confIsSkipSyncCustomerIsCorporate);

        if (extension_loaded('mbstring')) {
            Log::w("Library mbstring                 : YES");
        } else {
            Log::w("Library mbstring                 : NO");
        }
        $memoryLimit = strtoupper(ini_get('memory_limit'));
        Log::w("Memory Limit                     : " . $memoryLimit);
        if (-1 != $memoryLimit) {
            if (0 < strpos($memoryLimit, 'G')) {
                $memoryLimit = str_replace('G', '', $memoryLimit);
                $memoryLimit *= 1024;
            }
            if (0 < strpos($memoryLimit, 'K')) {
                $memoryLimit = str_replace('K', '', $memoryLimit);
                $memoryLimit /= 1024;
            }
            $memoryLimit = str_replace('M', '', $memoryLimit);
            if (2048 > $memoryLimit) {
                ini_set('memory_limit', '-1');
                Log::w("Memory Limit Set To -1");
            }
        }
        $postMaxSize = strtoupper(ini_get('post_max_size'));
        Log::w("Post Max Size                    : " . $postMaxSize);

        if (1 == $confIsImportSwitchCommutation) {
            $customer->isLoadCustomerCommutation = 1;
        }

        if (1 > $billingId) {
            Log::c('Error: Setting billingNumber Is Empty');
            $moduleHelper->finishModule();
        }

        //Пытаемся подключиться к USERSIDE
        Log::w("Try UserSide API");
        $jsonUs = $api->verifyUsersideApi();
        Log::w("US - Date                        : " . $jsonUs['date']);
        Log::w("US - OS                          : " . $jsonUs['os']);
        Log::w("US - Version                     : " . $jsonUs['erp']['version']);

        //Фиксируем в UserSide информацию о модуле
        $additionalUrl = "&cat=" . $this->moduleApiName . "&action=set_start_info&version=" . $moduleInformation . "&path=" . realpath(__DIR__) . "&log_path=" . $logPath;
        $json          = $api->sendDataToUserside($additionalUrl);
        $result        = isset($json['result']) ? $json['result'] : '';
        if ($result != 'OK') {
            Log::c('Error: Bad Start Data From UserSide API (' . $result . ')');
            $moduleHelper->finishModule();
        }
        $moduleMinAllowVersion = isset($json['module_min_allow_version']) ? $json['module_min_allow_version'] : '';
        Log::w("moduleMinAllowVersion            : " . $moduleMinAllowVersion);
        if (
            $moduleMinAllowVersion != ''
            &&
            $moduleCoreRevision < $moduleMinAllowVersion
        ) {
            Log::c('Error: Bad module Version (need: ' . $moduleMinAllowVersion . ')');
            Log::w("Bad module version");
            $moduleHelper->finishModule();
        }

        //Пытаемся подключиться к Биллингу
        Log::w("Try Billing API");
        if ('standart' == $billingName || 'carbon5' == $billingName || 'nodenyplus' == $billingName) {
            $json = $api->verifyBillingApi();
        } else {
            $json = $billing->verifyBillingApi();
        }

        $json['os']                 = isset($json['os']) ? $json['os'] : '';
        $json['date']               = isset($json['date']) ? $json['date'] : '';
        $json['billing']['name']    = isset($json['billing']['name']) ? $json['billing']['name'] : '';
        $json['billing']['version'] = isset($json['billing']['version']) ? $json['billing']['version'] : '';

        Log::w("Billing - Date                   : " . $json['date']);
        Log::w("Billing - OS                     : " . $json['os']);
        Log::w("Billing - Name                   : " . $json['billing']['name']);
        Log::w("Billing - Version                : " . $json['billing']['version']);

        //Получаем параметры работы
        $isUpdateAddress = 0;
        $additionalUrl   = "&cat=usm_billing&action=get_settings&billing_id=" . $billingId . "&billing_time=" . strtotime($json['date']) . "&erp_time=" . strtotime($jsonUs['date']);
        $json            = $api->sendDataToUserside($additionalUrl);
        $result          = isset($json['result']) ? $json['result'] : '';
        if ($result != 'OK') {
            Log::c('Error: Bad Initialize Data From UserSide API (' . $result . ')');
            $moduleHelper->finishModule();
        }
        $isCustomerActivityTimestamp = $json['data']['is_customer_activity_timestamp'] ?? 0;
        $isEncodeCustomerPassword    = $json['data']['is_encode_customer_password'] ?? 0;
        $isUpdateAddress             = $json['data']['is_update_address'] ?? 0;
        $isUpdateTraffic             = $json['data']['is_update_traffic'] ?? 0;
        $isUpdateMac                 = $json['data']['is_update_mac'] ?? 0;
        $lastPaidId                  = $json['data']['last_paid_id'] ?? 0;
        $lastMsgId                   = $json['data']['last_msg_id'] ?? 0;
        $rowLimit                    = $json['data']['row_limit'] ?? 0;
        $isNewHouseBillingCompare    = $json['data']['is_new_house_billing_compare'] ?? 0;
        $addressCompareFormat        = $json['data']['address_compare_format'] ?? 0;
        $dateTrafficUpdate           = $json['data']['date_traffic_update'] ?? 0; // Дата последнего обновления траффика в этом биллинге
        $dateTrafficUpdateInterval   = $json['data']['date_traffic_update_interval'] ?? 1; // Как часто обновлять траффик в этом биллинге (в минутах)
        $dateTrafficUpdateReal       = $isUpdateTraffic;

        Log::w("Settings");
        Log::w("isUpdateAddress                  : " . $isUpdateAddress);
        Log::w("dateTrafficUpdate                : " . $dateTrafficUpdate);
        if ($dateTrafficUpdate > 0) {
            $dateTrafficUpdateFromNow = floor((time() - $dateTrafficUpdate) / 60);
            Log::w("dateTrafficUpdate (2)            : " . date('Y-m-d H:i:s',
                    $dateTrafficUpdate) . ' (' . $dateTrafficUpdateFromNow . ' m)');
            if ($dateTrafficUpdateFromNow < $dateTrafficUpdateInterval) {
                $dateTrafficUpdateReal = 0;
            }
        }
        Log::w("dateTrafficUpdateInterval (m)    : " . $dateTrafficUpdateInterval);
        Log::w("isUpdateTraffic                  : " . $isUpdateTraffic . ' (real: ' . $dateTrafficUpdateReal . ')');
        $isUpdateTraffic = $dateTrafficUpdateReal;
        Log::w("isUpdateMac                      : " . $isUpdateMac);
        Log::w("lastPaidId                       : " . $lastPaidId);
        Log::w("lastMsgId                        : " . $lastMsgId);
        Log::w("rowLimit                         : " . $rowLimit);
        Log::w("addressCompareFormat             : " . $addressCompareFormat);
        Log::w("isNewHouseBillingCompare         : " . $isNewHouseBillingCompare);
        Log::w("isEncodeCustomerPassword         : " . $isEncodeCustomerPassword);
        Log::w("isCustomerActivityTimestamp      : " . $isCustomerActivityTimestamp);
    }

    public function finishModule()
    {
        global $moduleInformation, $api, $billingName, $billing, $isDatabaseOpen;

        //Фиксируем в UserSide информацию об окончании работы модуля
        $additionalUrl = "&cat=" . $this->moduleApiName . "&action=set_stop_info";
        $api->sendDataToUserside($additionalUrl, [], 'finish');

        if ('standart' == $billingName) {
            $counterBilling = $api->counterApiBilling;
        } else {
            $counterBilling = isset($billing->counterApiBilling) ? $billing->counterApiBilling : 0;
        }

        if (1 == $isDatabaseOpen) {
            //Закрываем соединение с базой биллинга
            Database::close();
        }

        Log::w("API Request Counter: UserSide: " . $api->counterApiUserside . " / Billing: " . $counterBilling);

        Log::a($moduleInformation . " - Module Finish");
        Log::w($moduleInformation . " - Module Finish");

        Log::c("Finish module at " . date('Y-m-d H:i:s'));
        Log::c("====================================");

        //Удаляем файл с блокировкой
        if (true == file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }

        exit;
    }

    public function terminateModule()
    {
        Log::c("  Stop module at " . date('Y-m-d H:i:s'));
        Log::c("====================================");
        exit;
    }

}

class Log
{
    static $logDataBase;
    static $logRaw;

    static function i($text)
    {
        self::c($text);
        self::w($text);
    }

    static function c($text)
    {
        global $isSilence;
        //Фиксация в консоль
        if (1 != $isSilence) {
            echo $text . "\n";
        }
    }

    static function w($text)
    {
        global $logFile, $isWithoutLog;
        if (1 == $isWithoutLog) {
            return;
        }
        //Фиксация в лог
        @$file = fopen($logFile, 'ab');
        @fwrite($file, date('Y-m-d H:i:s') . ' - ' . $text . "\n");
        @fclose($file);
    }

    static function d($text, $isFixDebug = 0)
    {
        global $logFile, $isDebugMode, $isWithoutLog;
        if (1 == $isWithoutLog) {
            return;
        }
        //Фиксация в лог только дебага
        if (
            1 == $isDebugMode
            ||
            2 == $isDebugMode
            ||
            1 == $isFixDebug
        ) {
            @$file = fopen($logFile, 'ab');
            @fwrite($file, date('Y-m-d H:i:s') . ' - ' . $text . "\n");
            @fclose($file);
        }
    }

    static function d2($text, $isFixDebug = 0)
    {
        global $logDebugFile, $isDebugMode, $isWithoutLog;
        if (1 == $isWithoutLog) {
            return;
        }
        //Фиксация в лог только дебага
        if (
            1 == $isDebugMode
            ||
            2 == $isDebugMode
            ||
            1 == $isFixDebug
        ) {
            @$file = fopen($logDebugFile, 'ab');
            @fwrite($file, date('Y-m-d H:i:s') . ' - ' . $text . "\n");
            @fclose($file);
        }
    }

    static function a($text)
    {
        global $logPath, $moduleName, $moduleHelper, $isWithoutLog;
        if (1 == $isWithoutLog) {
            return;
        }

        //Фиксация в лог запуска
        $fileName = $logPath . $moduleName . '_run.log';
        //Удаляем большой файл
        if (true == file_exists($fileName)) {
            if (100000 < filesize($fileName)) {
                unlink($fileName);
            }
        }
        @$file = fopen($fileName, 'ab');
        @fwrite($file, date('Y-m-d H:i:s') . ' - ' . $text . "\n");
        @fclose($file);
        if (true != file_exists($fileName)) {
            Log::c('Error: Verify Logs Path: ' . $logPath);
            $moduleHelper->terminateModule();
        }
    }

    static function dBLog($text)
    {
        global $isWithoutLog;
        if (1 == $isWithoutLog) {
            return;
        }
        @$file = fopen(self::$logDataBase, 'ab');
        @fwrite($file, $text . "\n");
        @fclose($file);
    }

    static function rawLog($type, $array, $isDump = 0, $isAlwaysDump = 0)
    {
        global $isDebugMode, $isWithoutLog;
        if (1 == $isWithoutLog) {
            return;
        }
        if (
            $isDebugMode > 0
            ||
            $isAlwaysDump == 1
        ) {
            if (1 != $isDump) {
                @$file = fopen(self::$logRaw . $type . '.log', 'ab');
                $json = json_encode($array, JSON_UNESCAPED_UNICODE);
                @fwrite($file, '<?php' . "\n" . date('Y-m-d H:i:s') . "\n" . $json);
                @fclose($file);
            } else {
                @file_put_contents(self::$logRaw . $type . '.log', var_export($array, true), FILE_APPEND);
            }
        }
    }

}

class Api
{
    private $usApiPath; //Путь к UserSide API
    private $billingApiPath; //Путь к Billing API
    public  $counterApiUserside = 0; //Счетчик запросов к UserSide
    public  $counterApiBilling  = 0; //Счетчик запросов к биллингу

    function sendDataToUserside($additionalUrl, $post = [], $type = '')
    {
        global $moduleHelper;
        Log::d("Additional URL: " . $additionalUrl);
        ++$this->counterApiUserside;
        $url    = $this->usApiPath . $additionalUrl;
        $result = $this->readFromUrl($url, 1, $post);
        if ($result['code'] != 200) {
            Log::w('Responce code: ' . $result['code']);
            $result['data'] = '{}';
        }
        if (
            '' === $result['data']
            &&
            'finish' !== $type
        ) {
            Log::c('Error: Empty Responce From UserSide API');
            $moduleHelper->finishModule();
        }
        return json_decode($result['data'], true);
    }

    function getDataFromUserside($additionalUrl, $logType = 1)
    {
        global $usersideUrl, $usersideApiKey, $moduleHelper;
        ++$this->counterApiUserside;
        if ('' == $this->usApiPath) {
            if ('/' !== substr($usersideUrl, -1, 1)) {
                $usersideUrl .= '/';
            }
            $this->usApiPath = $usersideUrl . 'api.php?key=' . $usersideApiKey . '&cat=module';
        }
        $url    = $this->usApiPath . $additionalUrl;
        $result = $this->readFromUrl($url, 0, [], 0, $logType);
        if ($result['code'] != 200) {
            Log::w('Responce code: ' . $result['code']);
            $result['data'] = '{}';
        }
        if ('' == $result['data']) {
            Log::c('Error: Empty Responce From UserSide API');
            $moduleHelper->finishModule();
        }
        return json_decode($result['data'], true);
    }

    function getDataFromBilling($additionalUrl, $isFixDebug = 0)
    {
        ++$this->counterApiBilling;
        global $billingUrl, $moduleHelper;
        $this->billingApiPath = $billingUrl . '&temp=' . time();
        $url                  = $this->billingApiPath . $additionalUrl;
        $result               = $this->readFromUrl($url, 0, [], $isFixDebug);
        $data                 = $result['data'];
        if ($result['code'] != 200) {
            Log::w('Responce code: ' . $result['code']);
            $data = '';
        }
        if ('' == $data) {
            $data = json_encode([], JSON_UNESCAPED_UNICODE);
        }
        if ('' == $data) {
            Log::c('Error: Empty Responce From Billing API');
            $moduleHelper->finishModule();
        }
        return json_decode($data, true, 512, JSON_INVALID_UTF8_IGNORE);
    }

    function verifyUsersideApi()
    {
        global $moduleHelper;
        $json = $this->getDataFromUserside('&request=get_system_information');
        if (!isset($json['date'])) {
            Log::c('Error: Bad Responce From UserSide API');
            $moduleHelper->finishModule();
        }
        return $json;
    }

    function verifyBillingApi()
    {
        global $moduleHelper, $billingName, $nodenyPlusSessionId;
        if ('carbon5' == $billingName) {
            $request = '&method1=userside_manager.get_system_information';
        } elseif ('nodenyplus' == $billingName) {
            $request = '';
        } else {
            $request = '&request=get_system_information';
        }
        $json = $this->getDataFromBilling($request, 1);
        if ('nodenyplus' == $billingName) {
            $nodenyPlusSessionId         = $json['ses'];
            $request                     = '&a=version';
            $GLOBALS['TEMP_CURL_COOKIE'] = 'noses=' . $nodenyPlusSessionId;
            $array                       = $this->getDataFromBilling($request);
            $json                        = NodenyPlus::systemDataDecode($array);
        }
        if (!isset($json['date'])) {
            Log::c('Error: Bad Responce From Billing API');
            $moduleHelper->finishModule();
        }
        return $json;
    }

    public function readFromUrl($url, $isPost = 0, $bundle = [], $isFixDebug = 0, $logType = 1)
    {
        global $moduleHelper, $usersideApiKey, $isDebugMode;
        $result = '';
        if (function_exists("curl_init")) {
            $c = curl_init();
            if (1 == $isPost) {
                list($url, $post) = explode('?', $url, 2);
                if (0 < count($bundle)) {
                    $bundle['key'] = $usersideApiKey;
                    $post          = http_build_query($bundle);
                }
                curl_setopt($c, CURLOPT_POST, 1);
                curl_setopt($c, CURLOPT_POSTFIELDS, $post);
            } elseif (2 == $isDebugMode) {
                Log::d($url);
            }
            curl_setopt($c, CURLOPT_URL, $url);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 1200);
            curl_setopt($c, CURLOPT_TIMEOUT, 1200);
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
            if (isset($GLOBALS['TEMP_CURL_COOKIE'])) {
                curl_setopt($c, CURLOPT_COOKIE, $GLOBALS['TEMP_CURL_COOKIE']);
            }
            $result   = curl_exec($c);
            $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);
            curl_close($c);
            if (2 == $logType) {
                Log::d2(str_replace("\n", ' ', $result), $isFixDebug);
            } else {
                Log::d(str_replace("\n", ' ', $result), $isFixDebug);
            }
        } else {
            Log::c('Error: Function curl_init Not Found');
            $moduleHelper->finishModule();
        }
        return [
            'data' => $result,
            'code' => $httpCode
        ];
    }

}

class Database
{
    static $connection;
    static $queryCount = 0;

    static function connect(
        $host,
        $user,
        $password,
        $database,
        $port,
        $codepage = 'UTF8',
        $isCustomBase = 0,
        $billingDbProvider = 'mysql'
    ) {
        global $moduleHelper, $moduleName, $billingCodePage;
        if ('usm_hydra' == $moduleName) {
            $conn = oci_pconnect($user, $password, $host . ":" . $port . "/" . $database, $codepage);
        } else {
            if ($billingDbProvider == 'postgresql') {
                $conn = pg_connect("host=" . $host . " port=5432 dbname=" . $database . " user=" . $user . " password=" . $password);
            } else {
                $conn = mysqli_connect($host, $user, $password, $database, $port);
            }
        }
        if (1 != $isCustomBase) {
            self::$connection = $conn;
        }
        if (!$conn) {
            Log::i("Can't connect to Billing DB");
            $moduleHelper->finishModule();
        }
        if ('usm_hydra' == $moduleName) {
            Database::query("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
            if ('cp1251' == $billingCodePage) {
                Database::query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN_CIS.CL8MSWIN1251'");
            } else {
                //Database::query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'English_America.AL32UTF8'");
                Database::query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'"); //TT-16304
            }
        } else {
            if ('none' != $codepage && '' != $codepage && $billingDbProvider != 'postgresql') {
                Database::query('SET CHARACTER SET ' . $codepage);
                Database::query('SET NAMES ' . $codepage);
            }
        }
        return $conn;
    }

    static function close()
    {
        global $moduleName, $billingDbProvider;
        Log::w("DataBase Query Counter: " . self::$queryCount);
        if ('usm_hydra' == $moduleName) {
            oci_close(self::$connection);
        } else {
            if ($billingDbProvider == 'postgresql') {
                pg_close(self::$connection);
            } else {
                mysqli_close(self::$connection);
            }
        }
    }

    public static function query(
        string $query,
        bool $isArray = false,
        string $idName = '',
        $customConnection = ''
    ) {
        global $moduleTimeStart, $moduleName, $billingDbProvider;
        ++self::$queryCount;
        if ($billingDbProvider === 'postgresql') {
            $query = str_replace(
                [
                    'IFNULL',
                    'GROUP_CONCAT'
                ],
                [
                    'NULLIF',
                    'array_agg'
                ], $query);
        }
        Log::dBLog("==========================================================\n" . self::$queryCount . ". Time: " . date('Y-m-d H:i:s') . "\n\n" . $query . "\n");
        $timeStart = microtime();
        if ('' == $customConnection) {
            $customConnection = Database::$connection;
        }
        $responce = [];
        if ('usm_hydra' === $moduleName) {
            $stid        = oci_parse($customConnection, $query);
            $count       = 0;
            $ociResponce = oci_execute($stid);
            if ($ociResponce === false) {
                echo $query . "\n";
                Log::dBLog("Error oci_execute");
            } else {
                if (true == $isArray) {
                    $count = 0;
                    if ('' == $idName) {
                        while ($rs = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
                            $responce[$rs[0]] = $rs;
                            ++$count;
                        }
                    } else {
                        while ($rs = oci_fetch_assoc($stid)) {
                            if (isset($rs[$idName])) {
                                $responce[$rs[$idName]] = $rs;
                                ++$count;
                            }
                        }
                    }
                } else {
                    @$responce = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS);
                }
            }
            $timeFinish = microtime();
        } else {
            if ($billingDbProvider == 'postgresql') {
                $recordSet  = pg_query($customConnection, $query);
                $timeFinish = microtime();
                if ('' != pg_last_error($customConnection)) {
                    Log::dBLog("Error: " . pg_last_error($customConnection));
                }
                if (true == $isArray) {
                    $count = 0;
                    if ('' == $idName) {
                        while ($rs = pg_fetch_array($recordSet)) {
                            $responce[$rs[0]] = $rs;
                            ++$count;
                        }
                    } else {
                        while ($rs = pg_fetch_assoc($recordSet)) {
                            if (isset($rs[$idName])) {
                                $responce[$rs[$idName]] = $rs;
                                ++$count;
                            }
                        }
                    }
                } else {
                    @$responce = pg_fetch_array($recordSet);
                }
                @pg_free_result($recordSet);
            } else {
                $recordSet  = mysqli_query($customConnection, $query);
                $timeFinish = microtime();
                if (0 < mysqli_errno($customConnection)) {
                    Log::dBLog("Error N: " . mysqli_errno($customConnection) . ': ' . mysqli_error($customConnection));
                }
                if (true == $isArray) {
                    $count = 0;
                    if ('' == $idName) {
                        while ($rs = mysqli_fetch_array($recordSet)) {
                            $responce[$rs[0]] = $rs;
                            ++$count;
                        }
                    } else {
                        while ($rs = mysqli_fetch_assoc($recordSet)) {
                            if (isset($rs[$idName])) {
                                $responce[$rs[$idName]] = $rs;
                                ++$count;
                            }
                        }
                    }
                } else {
                    if (!is_bool($recordSet)) {
                        $responce = mysqli_fetch_array($recordSet);
                    }
                }
                if (!is_bool($recordSet)) {
                    mysqli_free_result($recordSet);
                }
            }
        }
        $array      = explode(' ', $timeStart);
        $timeStart  = $array[1] + $array[0];
        $array      = explode(' ', $timeFinish);
        $timeFinish = $array[1] + $array[0];
        $timeUse    = round(($timeFinish - $timeStart) * 1000) * 0.001;
        $textLog    = "Finish: " . $timeUse;

        $array     = explode(' ', $moduleTimeStart);
        $timeStart = $array[1] + $array[0];
        $timeUse   = round(($timeFinish - $timeStart) * 1000) * 0.001;
        $textLog   .= " / " . $timeUse;

        if (true == $isArray) {
            $textLog .= " - ResultCount: " . $count;
        }
        Log::dBLog($textLog . "\n");
        return $responce;
    }

}

class NodenyPlus
{

    public static function systemDataDecode($array)
    {
        $json = [
            'date' => date('Y-m-d H:i:s', $array['timestamp']),
            'billing' => [
                'name' => 'NoDenyPlus',
                'version' => $array['version']
            ]
        ];
        return $json;
    }

    public static function tariffDataDecode($array)
    {
        $json = [];
        foreach ($array as $tariffType => $value) {
            if ($tariffType == 'speed_up' || $tariffType == 'inet_unlim') {
                foreach ($value as $i => $value2) {
                    $value2['param']['speed_out1'] = isset($value2['param']['speed_out1']) ? $value2['param']['speed_out1'] * 0.001 : 0;
                    $value2['param']['speed_in1']  = isset($value2['param']['speed_in1']) ? $value2['param']['speed_in1'] * 0.001 : 0;
                    $json[$value2['service_id']]   = [
                        'id' => $value2['service_id'],
                        'name' => $value2['title'],
                        'payment' => $value2['price'],
                        'payment_interval' => 30,
                        'speed' => [
                            'up' => $value2['param']['speed_out1'],
                            'down' => $value2['param']['speed_in1']
                        ]
                    ];
                }
            }
        }
        return $json;
    }
}
