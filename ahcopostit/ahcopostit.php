<?php

/**
 * 
 *
 * @author Heikki Pals
 * Ahco OY
 * 
 * 2017 
 */
if (!defined('_PS_VERSION_'))
    exit;

class ahcopostit extends Module {

    /**
     *
     * @var type 
     */
    static protected $debug = array();

    /**
     *
     * @var type 
     */
    static protected $_debug_fp = null;

    /**
     * 
     * 
     */
    protected $mySettings = array(
        'A_SI_API_KEY' => array(
            'label' => 'Shipit API Key',
            'default' => '', // e.g. aTpB2OX/4er0bRa6
            'required' => true,
        ),
        'A_SI_API_SECRET' => array(
            'label' => 'Shipit API secret',
            'default' => '', // e.g. H$?dzzKrqER+FD*AkRx&I*V"kedekQ=t
        ),
        'A_SI_API_URL' => array(
            'label' => 'Shipit API URL. Tuotantoosoite on https://api.shipit.ax .',
            'default' => 'https://api.shipit.ax/', //http://apitest.shipit.ax/  , https://api.shipit.ax/
            'required' => true,
        ),
        'A_SI_USERID' => array(
            'label' => 'Shipit käyttäjätunnus',
            'default' => 'shipit',
            'required' => true,
        ),
        'A_SI_PASSWORD' => array(
            'label' => 'Shipit salasana',
            'default' => 'tulossa',
            'required' => true,
        ),
        'A_SI_MAX_PICKUP_LCTS' => array(
            'label' => 'Maksimi määrä noutopisteita',
            'default' => '50',
            'required' => true,
        ),
        'A_SI_T_S_PC' => array(
            'label' => 'API testi, lähettäjän postinumero',
            'default' => '20540',
        ),
        'A_SI_T_S_C' => array(
            'label' => 'API testi, lähettäjän maa',
            'default' => 'FI',
        ),
        'A_SI_T_R_PC' => array(
            'label' => 'API testi, vastaanottajan postinumero',
            'default' => '00100',
        ),
        'A_SI_T_R_C' => array(
            'label' => 'API testi, vastaanottajan maa',
            'default' => 'FI',
        ),
        'A_SI_T_P_T' => array(
            'label' => 'API testi, paketin tyyppi',
            'default' => 'PACKAGE',
        ),
        'A_SI_T_P_WGT' => array(
            'label' => 'API testi, paketin paino',
            'default' => '1',
        ),
        'A_SI_T_P_W' => array(
            'label' => 'API testi, oletus paketin leveys (cm)',
            'default' => '35',
        ),
        'A_SI_T_P_L' => array(
            'label' => 'API testi, oletus paketin pituus (cm)',
            'default' => '23',
        ),
        'A_SI_T_P_H' => array(
            'label' => 'API testi, oletus paketin korkeus (cm)',
            'default' => '3',
        ),
        'A_SI_IS_COMPANY' => array(//  `sender_name`
            'label' => 'Onko lähettäjä yritys, 1 = kyllä, 0 = ei.',
            'default' => '1',
        ),
        'A_SI_SENDER_NAME' => array(//  `sender_name`
            'label' => 'Lähettäjän Nimi',
            'default' => 'Ahco Oy',
        ),
        'A_SI_S_A1' => array(// `sender_address1`
            'label' => 'Lähettäjän osoiterivi 1',
            'default' => 'Ruukinkatu 4',
        ),
        'A_SI_S_A2' => array(// `sender_address2
            'label' => 'Lähettäjän osoiterivi 2',
            'default' => '',
        ),
        'A_SI_S_PC' => array(//  `sender_zipcode`
            'label' => 'Lähettäjän postinumero',
            'default' => '20540',
        ),
        'A_SI_S_CITY' => array(// `sender_city`
            'label' => 'Lähettäjän Kaupunki',
            'default' => 'TURKU',
        ),
        'A_SI_S_C' => array(// `sender_country`
            'label' => 'Lähettäjän maa. ESIM "FI"  ',
            'default' => 'FI',
        ),
        'A_SI_S_CP' => array(// `sender_contact`
            'label' => 'Lähettäjän yhteyshenkilö  ',
            'default' => 'Heikki Pals',
        ),
        'A_SI_S_CP_P' => array(// `sender_phone`
            'label' => 'Lähettäjän y.h. puhelinnumero  ',
            'default' => '0451299998',
        ),
        'A_SI_S_CP_F' => array(// `sender_fax`
            'label' => 'Lähettäjän y.h. fax',
            'default' => '',
        ),
        'A_SI_S_CP_EM' => array(// `sender_phone`
            'label' => 'Lähettäjän y.h. sähköpostiosoite',
            'default' => 'info@ahco.fi',
        ),
        'A_SI_S_CP_SMS' => array(// `sender_sms`
            'label' => 'Lähettäjän y.h. SMS puhelinnumero',
            'default' => '0451299998',
        ),
    );

    /**
     *
     * @var type array representation of data to be sent in json to ship it api url;
     */
    protected $shipItPayLoad = array();

    /**
     *
     *
     *
     */
    public function __construct() {
        $this->name = 'ahcopostit';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.1';
        $this->author = 'Ahco / Heikki Pals';
        $this->need_instance = 0;
        $this->displayName = $this->l('Shipit');
        $this->description = $this->l('Shipit osoitekortti moduuli');
        parent::__construct();
        if (!class_exists('ShipitApiClient')) {
            require_once dirname(__FILE__) . '/ShipitApiClient.php';
        }
    }

    /**
     *
     *
     */
    public function __destruct() {
        if (self::$_debug_fp)
            fclose(self::$_debug_fp);
        // return parent::__destruct();
    }

    /**
     *
     */
    protected function debug($mixed_object) {

        if (empty(self::$debug)) {
            self::$debug[] = array(
                'time' => date('Y-m-d h:i:s'),
                'debug_object' => array(
                    'function' => __FUNCTION__,
                    'line' => __LINE__,
                    'Prestashop version: ' => _PS_VERSION_,
                    'shop_id' => $this->context->shop->id,
                    'Module version: ' => $this->version,
                    'request_uri' => $_SERVER['SERVER_NAME'] . ' ' . $_SERVER['REQUEST_URI'],
                    'Shop Email' => Configuration::get('PS_SHOP_EMAIL'),
                    'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
                    '_GET' => $_GET,
                    '_POST' => $_POST,
                    '_SERVER' => $_SERVER,
                    'employee_name' => isset($this->context->employee->email) ? ( $this->context->employee->firstname . ' ' . $this->context->employee->lastname) : 'n/a',
                    'employee_email' => isset($this->context->employee->email) ? $this->context->employee->email : 'n/a'
                ),
            );
        }

        if (is_string($mixed_object)) {
            $mixed_object = $mixed_object;
        }

        self::$debug[] = array(
            'time' => date('Y-m-d h:i:s'),
            'debug_object' => $mixed_object
        );
    }

    /**
     *
     * Return Prestashop version.
     * @return <type>
     *
     * 
     *
     */
    protected function psV() {
        return substr(_PS_VERSION_, 0, 3);
    }

    /**
     * 
     */
    protected function createDbTables() {
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "_ahco_ship_it_shipments` (
			  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  `id_order` int(11) NOT NULL,
			  `tracking_code` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
                          `shipit_order_id` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
                          `json_interaction` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
			  `pdf_base64` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
			  `created` datetime DEFAULT NULL,
			  `modified` datetime DEFAULT NULL
			) ENGINE=" . _MYSQL_ENGINE_ . "  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Ahco Ship It lähetykset';
			";

        foreach ($sql as $s) {
            try {
                $this->debug(__FUNCTION__ . '() ' . $s);
                if (!Db::getInstance()->execute($s)) {
                    $error = Db::getInstance()->getNumberError();
                    $errorMsg = Db::getInstance()->getMsgError();
                    if ($error) {
                        $this->debug('error on SQL : ' . $error . '  =  ' . $s);
                        $this->debug('error on SQL : ' . $errorMsg . '  = ' . $s);
                        return false;
                    }
                    return false;
                }
            } catch (Exception $e) {
                $this->debug('Exception on SQL : ' . $s);
                $this->debug($e->getCode());
                $this->debug($e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * 
     */
    protected function dropDbTables() {
        $sql[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "_ahco_ship_it_shipments`;";
        foreach ($sql as $s) {
            try {
                $this->debug(__FUNCTION__ . '() ' . $s);
                if (!Db::getInstance()->execute($s)) {
                    return false;
                }
            } catch (Exception $e) {
                $this->debug($e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @return <type>
     *
     * Basic installation
     *
     */
    public function install() {
        if (!parent::install()) {
            return false;
        }

        if (!$this->createDbTables()) {
            return false;
        }

        if (!$this->registerHook('adminOrder')) {
            return false;
        }
        if (!$this->registerHook('displayBackOfficeTop')) {
            return false;
        }
        foreach ($this->mySettings as $key => $sData) {
            Configuration::updateValue($key, $sData['default']);
        }

        return true;
    }

    /*
     *
     * Basic uninstall
     */

    public function uninstall() {

        if (!$this->unregisterHook('adminOrder')) {
            return false;
        }

        if (!$this->unregisterHook('displayBackOfficeTop')) {
            return false;
        }

        if (!$this->dropDbTables()) {
            return false;
        }

        foreach ($this->mySettings as $key => $sData) {
            Configuration::deleteByName($key);
        }
        return parent::uninstall();
    }

    /**
     *  CONFIG!
     *
     */
    public function getContent() {

        foreach ($this->mySettings as $key => $sData) {
            if (isset($_POST[$key])) {
                if (($sData['default'] === null) && ($_POST[$key] == '')) {
                    $_POST[$key] = null;
                }
                Configuration::updateValue($key, $_POST[$key]);
            }
        }

        $html = '<h1>' . $this->l($this->displayName) . '</h1>';
        $html .= '<h2>' . $this->l('Asetukset') . '</h2>';
        $html .= '<form method="post"><table>';
        $anySettingsMissing = false;
        foreach ($this->mySettings as $key => $sData) {
            $fieldValue = Configuration::get($key);
            $html .= '<tr>';
            $html .= '<td><h4>' . $this->l($sData['label']) . ' &nbsp;&nbsp;&nbsp;</h4>';
            if (!empty($sData['default'])) {
                $html .= '<p>' . $this->l('Esimerkiksi') . ' &nbsp; `' . htmlspecialchars($sData['default']) . '` </p> &nbsp; ';
            }

            if (isset($sData['required']) && ( $sData['required'] == true)) {
                $html .= '<p>' . $this->l('Tämä kenttä on pakollinen') . ' </p> &nbsp; ';
                if (!$fieldValue) {
                    $anySettingsMissing = true;
                }
            }

            $html .= '</td>';
            $html .= '<td> &nbsp; <input id="' . htmlspecialchars($key) . '" name="' . htmlspecialchars($key) . '"  type="text" value="' . htmlspecialchars($fieldValue) . '"> </td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '<input type="submit" value="' . htmlspecialchars($this->l('Tallenna')) . '" name="asq_module_save" " >';
        $html .= '</form>'
        ;

        if ($anySettingsMissing == false) {
            $myMethods = get_class_methods($this);
            foreach ($myMethods as $myMethod) {
                if (strpos($myMethod, 'apiTestToHtml') !== 0) {
                    continue;
                }

                $this->{$myMethod}($html);
            }
        }

        if (isset($_GET['ahcodebug'])) {
            $html .= '<pre>' . print_r(self::$debug, true) . '</pre>';
            ;
        }

        return $html;
    }

    /**
     * 
     * @param type $fromPostalCode
     * @param type $fromCountry
     * @param type $toPostalCode
     * @param type $toCountry
     * @param type $packageType string 'PACKAGE' , or array of packages  0 ... X => 
     *             'type' => $packageType,
     *             'weight' => $packageWeight,
     *             'width' => $packageWidth,
     *              'length' => $packageLength,
     *              'height' => $packageHeight
     * @param type $packageWeight
     * @param type $packageWidth
     * @param type $packageLength
     * @param type $packageHeight
     * 
     */
    public function getShippingMethods($fromPostalCode, $fromCountry, $toPostalCode, $toCountry, $packageType = 'PACKAGE', $packageWeight = 1, $packageWidth = 1, $packageLength = 1, $packageHeight = 1) {
        $this->shipItPayLoad['sender'] = array(
            'postcode' => $fromPostalCode,
            'country' => $fromCountry
        );

        $this->shipItPayLoad['receiver'] = array(
            'postcode' => $toPostalCode,
            'country' => $toCountry
        );
        if (is_array($packageType)) {
            $this->shipItPayLoad['parcels'] = $packageType;
        }

        if (!is_array($packageType)) {
            $this->shipItPayLoad['parcels'][] = array(
                'type' => $packageType,
                'weight' => $packageWeight,
                'width' => $packageWidth,
                'height' => $packageHeight,
                'length' => $packageLength,
            );
        }

        $this->shipItPayLoad['retrievePickupLocations'] = true;

        $this->shipItPayLoad['maxPickupLocations'] = Configuration::get('A_SI_MAX_PICKUP_LCTS');

        $client = new ShipitApiClient(trim(Configuration::get('A_SI_API_KEY')), trim(Configuration::get('A_SI_API_SECRET')), trim(Configuration::get('A_SI_API_URL')));
        $this->debug(array(
            'A_SI_API_KEY' => Configuration::get('A_SI_API_KEY'),
            'A_SI_API_SECRET' => Configuration::get('A_SI_API_SECRET'),
            'code' => '%:S[613]Y\76>=#}2Jr[x/1=az-&o?&r'
        ));
        $methods = $client->payload($this->shipItPayLoad)->post('shipping-methods');
        $this->debug(__FUNCTION__ . '() ' . __LINE__ . '  ' . print_r($methods, true));
        return $methods;
    }

    /**
     * 
     * Tests if API works with current settings
     * 
     * Return HTML for getContent() function.
     */
    public function apiTestToHtmlShippingMethods(&$html) {
        $test = $this->getShippingMethods(Configuration::get('A_SI_T_S_PC'), Configuration::get('A_SI_T_S_C'), Configuration::get('A_SI_T_R_PC'), Configuration::get('A_SI_T_R_C'), Configuration::get('A_SI_T_P_T'), Configuration::get('A_SI_T_P_WGT'), Configuration::get('A_SI_T_P_W'), Configuration::get('A_SI_T_P_L'), Configuration::get('A_SI_T_P_H'));
        $html .= '<h3> ' . $this->l('Toimitustavat API TEST') . '</h3>';
        if (!isset($test['response']['body']['methods']) || !is_array($test['response']['body']['methods']) || ( $test['response']['body']['methods'] == 0 )) {
            $html .= '<p> ' . $this->l('Toimitustavat API tunnukset ei toimi tai toimitustapoja ei ole saatavilla testi parametreilla') . '</p>';
            return;
        }

        $html .= '<p> ' . $this->l('Toimitustavat API testi palauti seuraavat toimitustavat:') . '</p><ul>';

        foreach ($test['response']['body']['methods'] as $key => $courier) {

            $html .= '<li>'
                    . '<img width="100" src="'
                    . $courier['logo']
                    . '" alt="'
                    . htmlspecialchars($courier['carrier'] . ' - ' . $courier['serviceName'])
                    . '"><b> '
                    . htmlspecialchars($courier['carrier'] . ' - ' . $courier['serviceName'])
                    //.'<br/>'
                    . '</b> '
                    . $this->l('Toimituksen hinta ALV 0')
                    . ' '
                    . htmlspecialchars($courier['price'])
                    . ' &euro; '
                    . '</li>';
        }

        $html .= '</ul>';
    }

    /**
     * 
     *  List of all available methods without
     *  any sender / receiver nor parcel information.
     */
    public function getListMethods() {
        $client = new ShipitApiClient(trim(Configuration::get('A_SI_API_KEY')), trim(Configuration::get('A_SI_API_SECRET')), trim(Configuration::get('A_SI_API_URL')));
        $list = $client->payload()->get("list-methods");
        $this->debug(array(__FUNCTION__, __LINE__,
            'list' => $list));
        return $list;
    }

    /**
     * 
     * @param string $html
     */
    public function apiTestToHtmlListMethods(&$html) {
        $test = $this->getListMethods();
        $this->debug(array(__FUNCTION__, __LINE__,
            'test' => $test));
        $html .= '<h3>' . $this->l('Kaikki toimitustavat testi') . '</h3>';
        if (!isset($test['response']['body']) || !is_array($test['response']['body']) || ( $test['response']['body'] == 0 )) {
            $html .= '<p> ' . $this->l('Toimitustavat API tunnukset ei toimi tai toimitustapoja ei ole saatavilla testi parametreilla') . '</p>';
            return;
        }

        $html .= '<p> ' . $this->l('Kaikki toimitustavat API testi palauti seuraavat toimitustavat:') . '</p><ul>';
        foreach ($test['response']['body'] as $key => $courier) {

            $html .= '<li>'
                    . '<img width="100" src="'
                    . $courier['logo']
                    . '" alt="'
                    . htmlspecialchars($courier['carrier'] . ' - ' . $courier['name'])
                    . '"><b> '
                    . htmlspecialchars($courier['carrier'] . ' - ' . $courier['name'])
                    //.'<br/>'
                    . '</b> '
                    . '</li>';
        }

        $html .= '</ul>';
    }

    /**
     * 
     * @param type $postItShipment array
     * @return boolean
     */
    public function getServiceList($postItShipment) {

        $shippingMethods = $this->getShippingMethods($postItShipment['sender']['postcode'], $postItShipment['sender']['country'], $postItShipment['receiver']['postcode'], $postItShipment['receiver']['country']
                , $postItShipment['parcels'][0]['type'], $postItShipment['parcels'][0]['weight'], $postItShipment['parcels'][0]['width'], $postItShipment['parcels'][0]['length'], $postItShipment['parcels'][0]['height']
        );

        if (!isset($shippingMethods['response']['body']['methods']) || !is_array($shippingMethods['response']['body']['methods']) || ( $shippingMethods['response']['body']['methods'] == 0 )) {
            $shippingMethods['response']['body']['methods'] = array();
        }

        $shipItservices = array(
            'version' => 1,
            'partners' => [],
            'partner_pickup_locations' => [],
        );

        foreach ($shippingMethods['response']['body']['methods'] as $key => $courier) {
            $shipItservices['partners'][$courier['carrier']]['partner_name'] = $courier['carrier'];
            $shipItservices['partners'][$courier['carrier']]['services'][] = array(
                'service_name' => $courier['serviceName'],
                'service_code' => $courier['serviceId'],
            );
        }

        foreach ($shippingMethods['response']['body']['locations'] as $key => $pup) {
            $shipItservices['partner_pickup_locations'][] = $pup;
        }


        if (sizeof($shipItservices['partners'])) {
            return $shipItservices;
        }


        // fall back to local file, which should return all available services.
        $shipItservicesJson = file_get_contents(dirname(__FILE__) . '/postit_services.json');
        $shipItservices = json_decode($shipItservicesJson, true);
        if (!$shipItservices) {
            return false;
        }

        return $shipItservices;
    }

    /**
     * 
     * @param type $postItShipment array
     */
    public function getServiceListCodes($shipment) {
        $availableServices = $this->getServiceList($shipment);
        $codes = [];
        foreach ($availableServices['partners'] as $partner) {
            foreach ($partner['services'] as $service) {
                $codes[] = $service['service_code'];
            }
        }
        return $codes;
    }

    /**
     * 
     * 
     * @param type $shipment    array()
     * @param type $htmlResponse
     * 
     */
    public function validateShipmentService($shipment, &$htmlResponse) {
        $availableServiceCodes = $this->getServiceListCodes($shipment);

        if (!in_array($shipment['serviceId'], $availableServiceCodes)) {
            return false;
        }
        return true;
    }

    /**
     * 
     * 
     * @param type $shipment
     * @param string $htmlResponse  html string to append 
     */
    public function sendShipItShipment($shipment, &$htmlResponse, $prestasShopOrderID) {

        foreach ($shipment['parcels'] as $n => $package) {
            if (empty($package['type'])) {
                unset($shipment['parcels'][$n]);
            }
        }
        unset($shipment['_updateServices']);
        unset($shipment['_getDeliveryCard']);
        if (!$shipment['pickupId']) {
            unset($shipment['pickupId']);
        }

        $shipment['sender']['isCompany'] = boolval($shipment['sender']['isCompany']);
        $shipment['receiver']['isCompany'] = boolval($shipment['receiver']['isCompany']);

        if ($this->validateShipmentService($shipment, $htmlResponse) == false) {
            $htmlResponse .= '<p class="error" >' . $this->l('Palvelu ei ole mahdollista tämän lähetyksen yhteydessä.') . '</p>';
            return;
        }

        $this->shipItPayLoad = $shipment;
        $client = new ShipitApiClient(trim(Configuration::get('A_SI_API_KEY')), trim(Configuration::get('A_SI_API_SECRET')), trim(Configuration::get('A_SI_API_URL')));
        $shipmentResponse = $client->payload($this->shipItPayLoad)->put('shipment');
        $this->shipItPayLoad = array();

        $this->debug(array(__FUNCTION__, __LINE__,
            'shipment' => $shipment,
            'prestasShopOrderID' => $prestasShopOrderID,
            'shipmentResponse' => $shipmentResponse
        ));

        if (isset($shipmentResponse['response']['header']['Status-Code']) && ( $shipmentResponse['response']['header']['Status-Code'] == 500 )) {
            $htmlResponse .= '<p class="error" >' . $this->l('Rajapinta palauti tunteematon virheen') . '</p>';
            $this->createDebugWebForm($htmlResponse);
            return;
        }

        if (($shipmentResponse['response']['body']['status'] == 1) && isset($shipmentResponse['response']['body']['freightDoc'][0])) {
            $this->downLoadShipmentPdfs($shipmentResponse, $prestasShopOrderID);
        }

        if (!($shipmentResponse['response']['body']['status'] == 1) || !isset($shipmentResponse['response']['body']['freightDoc'][0])) {
            $this->createDebugWebForm($htmlResponse);
        }
    }

    /**
     * 
     * @param type $sendShipmentResponse
     */
    protected function downLoadShipmentPdfs($sendShipmentResponse, $prestasShopOrderID) {

        $this->debug(array(__FUNCTION__, __LINE__, 'sendShipmentResponse' => $sendShipmentResponse));

        foreach ($sendShipmentResponse['response']['body']['freightDoc'] as $nr => $pdfUrl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pdfUrl);
            $this->debug(array(__FUNCTION__, __LINE__, $pdfUrl));

            $headers = array(
                //http://stackoverflow.com/questions/2140419/how-do-i-make-a-request-using-http-basic-authentication-with-php-curl
                'Authorization: Basic ' . base64_encode(Configuration::get('A_SI_USERID') . ':' . Configuration::get('A_SI_PASSWORD'))
            );
            $this->debug(array(__FUNCTION__, __LINE__, 'headers' => $headers));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            $pdfResponse = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $this->debug(array(__FUNCTION__, __LINE__, 'statusCode' => $statusCode));

            if ($statusCode != 200) {
                $this->debug(array(__FUNCTION__, __LINE__,
                    'non_200_statusCode' => $statusCode,
                    'errorPdfResponse' => $pdfResponse));
                continue;
            }

            $dbArray = [
                'id' => null,
                'id_order' => pSQL($prestasShopOrderID),
                'tracking_code' => pSQL($sendShipmentResponse['response']['body']['trackingNumber']),
                'shipit_order_id' => pSQL($sendShipmentResponse['response']['body']['orderId']),
                'json_interaction' => pSQL(json_encode($sendShipmentResponse)),
                'pdf_base64' => pSQL(base64_encode($pdfResponse)),
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s')
            ];
            $this->debug(array(__FUNCTION__, __LINE__,
                'dbArray' => $dbArray,
            ));

            try {
                $saved = DB::getInstance()->insert('_ahco_ship_it_shipments', $dbArray);
                $error = Db::getInstance()->getNumberError();
                $errorMsg = Db::getInstance()->getMsgError();
                if ($error) {
                    throw new Exception($errorMsg, $error);
                }
                if (!$saved) {
                    throw new Exception('DB save Failed.', 500);
                }

                if ($saved) {
                    $this->debug(array(__FUNCTION__, __LINE__, ' onnisustunesti tallennettu paikallisesti.',
                        'dbArray' => $dbArray
                    ));
                }
            } catch (Exception $ex) {
                $this->debug(array(__FUNCTION__, __LINE__,
                    'tallennus epäonnistui',
                    'dbArray' => $dbArray,
                    'ex->getCode()' => $ex->getCode(),
                    'ex->getMessage()' => $ex->getMessage()
                ));
            }
        }
    }

    /**
     * 
     * @param type $html
     */
    protected function createDebugWebForm(&$html) {
        $webFormPostUrl = Context::getContext()->link->getAdminLink('AdminOrders') . '&id_order=' . urlencode($_GET['id_order']) . '&vieworder=1';
        if (isset($_GET['ahcodebug'])) {
            $webFormPostUrl .= '&ahcodebug=1';
        }
        $html .= '<p class="error">' . $this->l('Osoitekortin haussa tapahtui virhe tai ongelma.') . '</p>';
        $button = '<form action="' . $webFormPostUrl . '"  method="POST">';
        $button .= '<input type="hidden" name="shipIt[debugContent]" value="' . htmlspecialchars(print_r(self::$debug, true)) . '"  >';
        $button .= '<input type="submit" name="shipIt[debugSend]" value="' . $this->l('Lähetä moduulin kehittäjälle yksityiskohtaista tietoa') . '"  >';
        $button .= '</form><br/><br/>';
        $html .= $button;
    }

    /**
     * 
     * Haetaan Prestashoppiin paikallisesti tallennettut PDF -tiedot.
     * 
     * @param type $prestasShopOrderID
     * @param type $html
     * @return boolean
     */
    public function getPrestashopOrderRelatedShipItPdfs($prestasShopOrderID, &$html) {
        $sql = 'SELECT id, id_order,    tracking_code , shipit_order_id FROM `' . _DB_PREFIX_ . '_ahco_ship_it_shipments` '
                . '     WHERE id_order  =  ' . (int) $prestasShopOrderID
                . ' AND  ( pdf_base64 <>  \'\'  OR pdf_base64 IS NOT NULL    )  '
                . ' AND  ( tracking_code <>  \'\'  OR tracking_code IS NOT NULL    )  '
        ;
        try {
            $this->debug(__FUNCTION__ . '() ' . $sql);
            if (!($relatedPdfs = Db::getInstance()->ExecuteS($sql))) {
                $error = Db::getInstance()->getNumberError();
                $errorMsg = Db::getInstance()->getMsgError();
                if ($error) {
                    $this->debug(array(__FUNCTION__, __LINE__,
                        'sql' => $sql,
                        'relatedPdfs' => $relatedPdfs,
                        'getNumberError' => $error,
                        '$errorMsg' => $errorMsg
                    ));
                    return false;
                }
                return false;
            }
        } catch (Exception $e) {
            $this->debug(array(__FUNCTION__, __LINE__,
                'Exception on SQL' => $sql,
                'getCode' => $e->getCode(),
                'getMessage' => $e->getMessage()
            ));
            return false;
        }
        $this->debug(__FUNCTION__ . '() ' . print_r($relatedPdfs, true));
        if (!$relatedPdfs) {
            return false;
        }

        $html .= '<h3>' . $this->l('Tilaukseen liittyvät Ship It osoitekortit') . '</h3><ul>';

        foreach ($relatedPdfs as $relatedPdf) {
            $label = $this->l('Seurantakoodi:') . ' ' . $relatedPdf['tracking_code'] . '. ' . $this->l('Tulosta PDF-asiakirja');
            $html .= '<li>' . $this->getPdfFileDisplayLinkHtml($relatedPdf['id'], $prestasShopOrderID, $label, false)
                    . ' '
                    . $this->getPdfRemovalHtmlButton($relatedPdf['id'], $prestasShopOrderID)
                    . '</li>';
        }

        $html .= '</li>';

        return $relatedPdfs;
    }

    /**
     * 
     * @param type $dbId
     * @param type $orderId
     * @param type $label
     * @param type $autoOpen
     * @return string
     */
    protected function getPdfFileDisplayLinkHtml($dbId, $orderId, $label = null, $autoOpen = false) {

        if (!$label) {
            $label = $this->l('Tulosta PDF-asiakirja');
        }

        switch ($this->psV()) {
            case '1.7':
            case '1.6':
            case '1.5':
                if (!Context::getContext()->link) {
                    Context::getContext()->link = new Link();
                }
                $link = '<a  id="ahco_shipit_pdf_doc_link_' . $dbId . '"  class="ahco_pdf_doc_link" target="ahco_unifaun_tab_' . $dbId
                        . '" href="' . Context::getContext()->link->getAdminLink('AdminOrders') . '&id_order=' . urlencode($orderId)
                        . '&vieworder&display_shipit_pdf='
                        . urlencode($dbId) . '">'
                        . htmlspecialchars($label)
                        . '</a>';
                if ($autoOpen) {
                    $link .= "<script type=\"text/javascript\"> "
                            . "\n\n"
                            . " var pdfURL = $('#ahco_shipit_pdf_doc_link_" . (int) $dbId . "').attr('href'); "
                            . "\n\n"
                            . "  window.open( pdfURL , 'ahco_shipit_tab_" . (int) $dbId . "' ); "
                            . "\n\n"
                            . " </script>"
                            . "\n\n"
                    ;
                }
                return $link;
                break;
            case '1.4':
            default:
                return '<p>' . $this->l('Ei tuettu Prestashop versio') . '</p>';
                break;
        }
    }

    /**
     * 
     * @param type $dbId
     * @param type $orderId
     * @param type $label
     */
    protected function getPdfRemovalHtmlButton($dbId, $orderId, $label = null) {
        if (!$label) {
            $label = $this->l('Poista PDF-asiakirja');
        }
        switch ($this->psV()) {
            case '1.7':
            case '1.6':
            case '1.5':
                if (!Context::getContext()->link) {
                    Context::getContext()->link = new Link();
                }
                $webFormPostUrl = Context::getContext()->link->getAdminLink('AdminOrders') . '&id_order=' . urlencode($orderId) . '&vieworder=1';
                if (isset($_GET['ahcodebug'])) {
                    $webFormPostUrl .= '&ahcodebug=1';
                }

                $button = '<form action="' . $webFormPostUrl . '"  method="POST">';
                $button .= '<input type="hidden" name="shipIt[deleteId]" value="' . (int) $dbId . '"  >';
                $button .= '<input type="submit" name="shipIt[delete]" value="' . $this->l('Poista PDF -asiakirja') . '"  >';
                $button .= '</form>';
                return $button;
                break;
            case '1.4':
            default:
                return '<p>' . $this->l('Ei tuettu Prestashop versio') . '</p>';
                break;
        }
    }

    /**
     * Poistetaan osoitekortti, jos näin pyydetään.
     */
    protected function removePdfIfRequested(&$html) {
        if (!isset($_POST['shipIt']['delete']) || !isset($_POST['shipIt']['deleteId'])) {
            return false;
        }

        $failedDelete = '<p>' . $this->l('Osoitekorttia ei  poistettu') . '</p>';
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . '_ahco_ship_it_shipments` '
                . '     WHERE id   =  ' . (int) $_POST['shipIt']['deleteId']
                . ' LIMIT 1 ';
        try {
            if (!($removePdf = Db::getInstance()->Execute($sql))) {
                $error = Db::getInstance()->getNumberError();
                $errorMsg = Db::getInstance()->getMsgError();
                if ($error) {
                    $this->debug(array(__FUNCTION__, __LINE__,
                        'sql' => $sql,
                        'removePdf' => $removePdf,
                        'getNumberError' => $error,
                        'errorMsg' => $errorMsg
                    ));
                    $html .= $failedDelete;
                    return false;
                }
                $html .= $failedDelete;
                return false;
            }

            if ($removePdf) {
                $html .= '<p>' . $this->l('Osoitekortti on onnistuneesti poistettu') . '</p>';
            }
        } catch (Exception $e) {
            $this->debug(array(__FUNCTION__, __LINE__,
                'Exception on SQL' => $sql,
                'getCode' => $e->getCode(),
                'getMessage' => $e->getMessage()
            ));
            $html .= $failedDelete;
            return false;
        }

        return true;
    }

    /**
     * 
     */
    protected function deliverDebugMessageIfRequested(&$html) {
        if (!isset($_POST['shipIt']['debugContent']) || !isset($_POST['shipIt']['debugSend']) || empty($_POST['shipIt']['debugContent'])
        ) {
            return false;
        }

        $success = mail('debug' . '@' . 'ahco' . '.fi', $this->l('Ship It Virhetilanteen logi'), '<pre>' . print_r($_POST['shipIt']['debugContent'], true) . '</pre>');
        if ($success) {
            $html .= '<p>' . $this->l('Virhelogi onnistuneesti lähetetty') . '</p>';
        }
        if (!$success) {
            $html .= '<p>' . $this->l('Virhelogi epäonnistuneesti lähetetty') . '</p>';
        }
    }

    /**
     * 
     * Called by hookDisplayBackOfficeTop and hookBackOfficeTop
     * Outputs pdf if conditions are right
     *
     */
    protected function outputPdfFile() {


        // if ($_GET['controller'] == 'adminorders')
        if (isset($_GET['vieworder']) && isset($_GET['id_order']) && isset($_GET['display_shipit_pdf'])) {

            $db = Db::getInstance();

            $pdf = $db->ExecuteS('SELECT pdf_base64 FROM ' . _DB_PREFIX_ . '_ahco_ship_it_shipments WHERE id  = '
                    . (int) $_GET['display_shipit_pdf']
                    . '  LIMIT 1');

            if (!$pdf) {
                
            }

            header('Content-Type: application/pdf');
            //header('Content-Disposition: attachment; filename="' . $pdf[0]['unifaun_tracking_id'] . '.pdf"');
            $this->debug('Ahcounifaun->outputPdfFile() header_list():');
            $this->debug(headers_list());
            $this->debug($pdf);
            echo base64_decode($pdf[0]['pdf_base64']);
            $this->debug(base64_decode($pdf[0]['pdf_base64']));
            exit();
        }
    }

    /**
     * prestashop 1.5 hook for display Bck office top
     * http://doc.prestashop.com/display/PS15/Hooks+in+PrestaShop+1.5
     * @param <type> $params
     */
    public function hookDisplayBackOfficeTop($params) {
        $this->outputPdfFile();
    }

    /**
     *  http://doc.prestashop.com/display/PS15/Hooks+in+PrestaShop+1.5
     *  Prestashop 1.6
     * @global <type> $smarty
     * @param <type> $params
     * @return string
     *
     */
    public function hookAdminOrder($params) {
        $html = '<h2>' . htmlspecialchars($this->l('Ship It osoitekortti')) . '</h2>' . "\n\t";

        $this->removePdfIfRequested($html);
        $this->deliverDebugMessageIfRequested($html);

        if (isset($_POST['_getDeliveryCard']) || !empty($_POST['_getDeliveryCard'])) {
            $this->sendShipItShipment($_POST['shipit'], $html, $params['id_order']);
        }

        if (!isset($_POST['shipit']) || empty($_POST['shipit'])) {
            $posted = $this->provideDefaultPostValues($params['id_order']);
        } else {
            $posted['shipit'] = $_POST['shipit'];
        }

        if (!$posted['shipit']) {
            return null;
        }

        if (!($shipItservices = $this->getServiceList($posted['shipit']))) {
            return $html . '<p class="error">' . $this->l('Moduuli tai rajapinta ei toimi. Ota yhteyttä moduulin toimittajaan tai Ship It edustajaan') . '</p>';
        }

        $this->getPrestashopOrderRelatedShipItPdfs($params['id_order'], $html);

        $html .= '<form action="' . $_SERVER['REQUEST_URI'] . '"  method="POST" >' . "\n\t";
        $html .= '<table>' . "\n\t";

        $html .= '<tr><td>' . htmlspecialchars($this->l('Lähettäjän sähköposti')) . '</td> '
                . '<td><input type="text" name="shipit[sender][email]"  value="'
                . htmlspecialchars($posted['shipit']['sender']['email'])
                . '" ></td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Lähettäjän nimi')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[sender][name]"   value="'
                . htmlspecialchars($posted['shipit']['sender']['name']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";

        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Lähettäjä on yritys ')) . '</td> '
                . '<td>'
                . '<select name="shipit[sender][isCompany]"  >'
                . '<option value="1" ' . ( ($posted['shipit']['sender']['isCompany'] == 1) ? 'selected' : '' ) . ' >' . htmlspecialchars($this->l('Kyllä')) . '</option>'
                . '<option value="0" ' . ( ($posted['shipit']['sender']['isCompany'] == 0) ? 'selected' : '' ) . ' >' . htmlspecialchars($this->l('Ei')) . '</option>'
                . '</select>'
                . '</td>'
                . '</tr>' . "\n\t";

        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Lähettäjän yhteyshenkilö, jos lähettäjä on yritys')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[sender][contactPerson]"   value="'
                . htmlspecialchars($posted['shipit']['sender']['contactPerson']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";

        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Lähettäjän puhnro.')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[sender][phone]"   value="'
                . htmlspecialchars($posted['shipit']['sender']['phone']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Lähettäjän osoiterivi 1')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[sender][address]"   value="'
                . htmlspecialchars($posted['shipit']['sender']['address']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Lähettäjän osoiterivi 2')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[sender][address2]"   value="'
                . htmlspecialchars($posted['shipit']['sender']['address2']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Lähettäjän postinumero')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[sender][postcode]"   value="'
                . htmlspecialchars($posted['shipit']['sender']['postcode']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Lähettäjän maa')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[sender][country]"   value="'
                . htmlspecialchars($posted['shipit']['sender']['country']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Lähettäjän Kaupunki ')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[sender][city]"   value="'
                . htmlspecialchars($posted['shipit']['sender']['city']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";

        // -------------------------- vastaanottajan tiedot ------------------------------

        $html .= '<tr><td>' . htmlspecialchars($this->l('Vastaanottajan sähköposti')) . '</td> '
                . '<td><input type="text" name="shipit[receiver][email]"  value="'
                . htmlspecialchars($posted['shipit']['receiver']['email'])
                . '" ></td>'
                . '</tr>' . "\n\t";

        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Vastaanottaja on yritys ')) . '</td> '
                . '<td>'
                . '<select name="shipit[receiver][isCompany]"  >'
                . '<option value="1" ' . ( ($posted['shipit']['receiver']['isCompany'] == 1) ? 'selected' : '' ) . ' >' . htmlspecialchars($this->l('Kyllä')) . '</option>'
                . '<option value="0" ' . ( ($posted['shipit']['receiver']['isCompany'] == 0) ? 'selected' : '' ) . ' >' . htmlspecialchars($this->l('Ei')) . '</option>'
                . '</select>'
                . '</td>'
                . '</tr>' . "\n\t";

        $html .= '<tr>'
                . '<td>' . $this->l('Vastaanottajan nimi') . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[receiver][name]"   value="'
                . htmlspecialchars($posted['shipit']['receiver']['name']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";


        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Vastaanottajan yhteyshenkilö, jos lähettäjä on yritys')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[receiver][contactPerson]"   value="'
                . htmlspecialchars($posted['shipit']['receiver']['contactPerson']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";

        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Vastaanottajan puhnro.')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[receiver][phone]"   value="'
                . htmlspecialchars($posted['shipit']['receiver']['phone']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Vastaanottajan osoiterivi 1')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[receiver][address]"   value="'
                . htmlspecialchars($posted['shipit']['receiver']['address']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Vastaanottajan osoiterivi 2')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[receiver][address2]"   value="'
                . htmlspecialchars($posted['shipit']['receiver']['address2']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Vastaanottajan postinumero')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[receiver][postcode]"   value="'
                . htmlspecialchars($posted['shipit']['receiver']['postcode']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Vastaanottajan maa')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[receiver][country]"   value="'
                . htmlspecialchars($posted['shipit']['receiver']['country']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Vastaanottajan Kaupunki ')) . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[receiver][city]"   value="'
                . htmlspecialchars($posted['shipit']['receiver']['city']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";
        ////////////////////////////////////////////////////
        $html .= '<tr>'
                . '<td>' . $this->l('Lähetyksen paketit') . '</td> '
                . '<td>';
        $html .= '<table>' . "\n\t";

        for ($np = 0; $np < 1; $np++) {
            ////////// tyyppi
            $html .= '  <tr>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            $html .= ($np + 1) . '. ' . htmlspecialchars($this->l('Paketin tyyppi'));
            $html .= '          </td>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            if (!isset($posted['shipit']['parcels'][$np]['type'])) {
                $posted['shipit']['parcels'][$np]['type'] = '';
            }
            $html .= ' <select name="shipit[parcels][' . $np . '][type]" value="'
                    . htmlspecialchars($posted['shipit']['parcels'][$np]['type'])
                    . '" >';
            $html .= '<option value=""></option>';
            $html .= '<option value="PACKAGE" ' . (( $posted['shipit']['parcels'][$np]['type'] == 'PACKAGE' ) ? 'selected' : '' ) . '  >PACKAGE</option>';
            $html .= '</select>';
            $html .= '          </td>' . "\n\t";
            $html .= '  </tr>' . "\n\t";
            /////////// paino
            if (!isset($posted['shipit']['parcels'][$np]['weight'])) {
                $posted['shipit']['parcels'][$np]['weight'] = '';
            }
            $html .= '  <tr>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            $html .= ($np + 1) . '. ' . $this->l('Paketin paino');
            $html .= '          </td>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            if (!isset($posted['shipit']['parcels'][$np]['type'])) {
                $posted['shipit']['parcels'][$np]['type'] = '';
            }
            $html .= ' <input type="text" name="shipit[parcels][' . $np . '][weight]" value="'
                    . htmlspecialchars($posted['shipit']['parcels'][$np]['weight'])
                    . '" >';
            $html .= '          </td>' . "\n\t";
            $html .= '  </tr>' . "\n\t";


            /////////// leveys
            if (!isset($posted['shipit']['parcels'][$np]['width'])) {
                $posted['shipit']['parcels'][$np]['width'] = '';
            }
            $html .= '  <tr>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            $html .= ($np + 1) . '. ' . $this->l('Paketin leveys');
            $html .= '          </td>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            if (!isset($posted['shipit']['parcels'][$np]['type'])) {
                $posted['shipit']['parcels'][$np]['type'] = '';
            }
            $html .= ' <input type="text" name="shipit[parcels][' . $np . '][width]" value="'
                    . htmlspecialchars($posted['shipit']['parcels'][$np]['width'])
                    . '" >';
            $html .= '          </td>' . "\n\t";
            $html .= '  </tr>' . "\n\t";

            /////////// length
            if (!isset($posted['shipit']['parcels'][$np]['length'])) {
                $posted['shipit']['parcels'][$np]['length'] = '';
            }
            $html .= '  <tr>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            $html .= ($np + 1) . '. ' . htmlspecialchars($this->l('Paketin pituus'));
            $html .= '          </td>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            if (!isset($posted['shipit']['parcels'][$np]['length'])) {
                $posted['shipit']['parcels'][$np]['type'] = '';
            }
            $html .= ' <input type="text" name="shipit[parcels][' . $np . '][length]" value="'
                    . htmlspecialchars($posted['shipit']['parcels'][$np]['length'])
                    . '" >';
            $html .= '          </td>' . "\n\t";
            $html .= '  </tr>' . "\n\t";

            /////////// korkeus
            if (!isset($posted['shipit']['parcels'][$np]['height'])) {
                $posted['shipit']['parcels'][$np]['height'] = '';
            }
            $html .= '  <tr>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            $html .= ($np + 1) . '. ' . htmlspecialchars($this->l('Paketin korkeus'));
            $html .= '          </td>' . "\n\t";
            $html .= '          <td>' . "\n\t";
            if (!isset($posted['shipit']['parcels'][$np]['height'])) {
                $posted['shipit']['parcels'][$np]['type'] = '';
            }
            $html .= ' <input type="text" name="shipit[parcels][' . $np . '][height]" value="'
                    . htmlspecialchars($posted['shipit']['parcels'][$np]['height'])
                    . '" >';
            $html .= '          </td>' . "\n\t";
            $html .= '  </tr>' . "\n\t";
        }

        $html .= '</table>' . "\n\t";
        $html .= '</td>'
                . '</tr>' . "\n\t";

        $html .= '<tr>'
                . '<td>' . htmlspecialchars($this->l('Kuljetusyhtiö ja palvelu')) . '</td> '
                . '<td>';
        $html .= ' <select id="ahco_shipit_service_selector" name="shipit[serviceId]"  >';
        foreach ($shipItservices['partners'] as $partner) {
            $html .= '<optgroup label="' . htmlspecialchars($partner['partner_name']) . '">';
            foreach ($partner['services'] as $service) {
                $html .= '<option value="' . htmlspecialchars($service['service_code']) . '" '
                        . ( ( $posted['shipit']['serviceId'] == $service['service_code'] ) ? ' selected ' : '' )
                        . '   >' . htmlspecialchars($service['service_name']) . '</option>';
            }
            $html .= '</optgroup>';
        }
        $html .= '</select>';


        $html .= '</td>'
                . '</tr>' . "\n\t";

        // pickupId

        $html .= '<tr id="ahco_ship_it_pickup_id">'
                . '<td>' . $this->l('Kuljetusyhtiön noutopaika') . '</td> '
                . '<td>';
        $html .= ' <select id="pickup_location_selector" name="shipit[pickupId]" >';
        $html .= '<option value=""></option>';

        foreach ($shipItservices['partner_pickup_locations'] as $location) {

            $html .= '<option id="ahco_shipit_pickup_courier_' . htmlspecialchars($location['serviceId'] . '_' . $location['id'])
                    . '" class="ahco_shipit_pickup_locations"'
                    . ' value="' . htmlspecialchars($location['id']) . '" '
                    . ( ( $posted['shipit']['pickupId'] == $location['id'] ) ? ' selected ' : '' )
                    . '   >'
                    . htmlspecialchars($location['name'] . ', ' . $location['address1'] . ', ' . $location['zipcode'] . ', ' . $location['city'] . ',  ' . $location['countryCode'])
                    . '</option>';
        }
        $html .= '</select>';
        $html .= ' <input type="submit" name="_updateServices" value="' . $this->l('Päivitä toimituspalvelut ja noutopisteiden valikot') . '" >' . "\n\t";
        $html .= '</td>'
                . '</tr>' . "\n\t";



        $html .= '<tr>'
                . '<td>' . $this->l('Vapaata tekstia') . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[freeText]"   value="'
                . htmlspecialchars($posted['shipit']['freeText']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";

        $html .= '<tr>'
                . '<td>' . $this->l('Sisältö') . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[contents]"   value="'
                . htmlspecialchars($posted['shipit']['contents']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";

        $html .= '<tr>'
                . '<td>' . $this->l('Lähetyksen arvo') . '</td> '
                . '<td>'
                . '<input type="text"  name="shipit[valueAmount]"   value="'
                . htmlspecialchars($posted['shipit']['valueAmount']) . '" >'
                . '</td>'
                . '</tr>' . "\n\t";

        $html .= '</table>' . "\n\t";
        $html .= ' <input type="submit"  name="_getDeliveryCard" value="' . $this->l('Hae Ship It -osoitekortti.') . '" >' . "\n\t";
        $html .= '</form>' . "\n\t";


        $html .= '<script type="text/javascript" src="' . __PS_BASE_URI__ . 'modules/' . $this->name . '/js/' . 'ship_it.js' . '" ></script>';

        if (isset($_GET['ahcodebug'])) {
            $html .= '<pre>' . print_r(self::$debug, true) . '</pre>';
            ;
        }

        return $html;
    }

    /**
     * 
     * @param type $idOrder
     * @return type
     */
    public function provideDefaultPostValues($idOrder) {

        $prestashopOrder = new Order($idOrder);

        if (!$prestashopOrder->id) {
            return null;
        }
        $orderCustomer = new Customer((int) $prestashopOrder->id_customer);

        $totalWeight = $prestashopOrder->getTotalWeight();
        if (!$totalWeight) {
            $totalWeight = 1;
        }
        $orderDeliveryAddress = new Address($prestashopOrder->id_address_delivery);
        $country = new Country($orderDeliveryAddress->id_country);
        $message = $this->l('Tilaus nro.')
                . ' ' . $prestashopOrder->id
                . ' '
                . $this->l('Tilauksen viite') . ' ' . $prestashopOrder->reference
                . "\n\n"
        ;
        $content = $this->l('Tilaus #') . $prestashopOrder->reference;

        $phoneNumber = !empty($orderDeliveryAddress->phone_mobile) ? $orderDeliveryAddress->phone_mobile : $orderDeliveryAddress->phone;
        $proposed = array(
            'shipit' => array(
                'sender' => array(
                    'email' => Configuration::get('A_SI_S_CP_EM'),
                    'name' => Configuration::get('A_SI_SENDER_NAME'),
                    'contactPerson' => Configuration::get('A_SI_S_CP'),
                    'phone' => Configuration::get('A_SI_S_CP_P'),
                    'address' => Configuration::get('A_SI_S_A1'),
                    'address2' => Configuration::get('A_SI_S_A2'),
                    'postcode' => Configuration::get('A_SI_S_PC'),
                    'country' => Configuration::get('A_SI_S_C'),
                    'city' => Configuration::get('A_SI_S_CITY'),
                    'isCompany' => (Configuration::get('A_SI_IS_COMPANY') == "1") ? 1 : 0,
                ),
                'receiver' => array(
                    'email' => $orderCustomer->email,
                    'name' => strlen($orderDeliveryAddress->company) ? $orderDeliveryAddress->company : ( $orderDeliveryAddress->firstname . ' ' . $orderDeliveryAddress->lastname),
                    'contactPerson' => strlen($orderDeliveryAddress->company) ? $orderDeliveryAddress->firstname . ' ' . $orderDeliveryAddress->lastname : '',
                    'phone' => $phoneNumber,
                    'address' => $orderDeliveryAddress->address1,
                    'address2' => $orderDeliveryAddress->address2,
                    'postcode' => $orderDeliveryAddress->postcode,
                    'country' => $country->iso_code,
                    'city' => $orderDeliveryAddress->city,
                    'isCompany' => strlen($orderDeliveryAddress->company) ? 1 : 0,
                ),
                'parcels' => array(
                    0 => array(
                        'type' => 'PACKAGE',
                        'weight' => $totalWeight,
                        'width' => Configuration::get('A_SI_T_P_W'), // API testi, oletus paketin leveys (m) , 0.35
                        'length' => Configuration::get('A_SI_T_P_L'), // API testi, oletus paketin pituus (m) , 0.23
                        'height' => Configuration::get('A_SI_T_P_H'), // API testi, oletus paketin korkeus (m) , 0.03
                    )
                ),
                'serviceId' => '',
                'pickupId' => '',
                'freeText' => $message,
                'contents' => $content,
                'valueAmount' => $prestashopOrder->getTotalProductsWithTaxes()
            )
        );

        $this->debug(array(__FUNCTION__, __LINE__, 'proposed' => $proposed));

        return $proposed;
    }

}
