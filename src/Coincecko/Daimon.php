<?php

namespace Bot\Coincecko;

use http\Exception;

require __DIR__ . '/../../vendor/autoload.php';
//use Base\CoinGecko\Config;

//use Base\Config;

class Client
{
    const CONFIG_PARAM_NAME = 'exchange_coingecko';
    const CONFIG_PARAM_NAME_DATABSER = 'database';
    const TABLE_NAME = 'exchane_rate';
    /**
     * @var array configuratiom for bot
     */
    private array $config;

    /**
     * @var resource connection to db
     */
    private array $dbh;

    /**
     * @var array currency list
     */
    private array $currencyList;

    public function __construct(array $config = [])
    {
//        print_r("helllg");
        $this->config = $this->getConfig($config);
        $this->db = $this->getConnectDb();
//        print_r($this->config);
    }

    private function getConnectDb()
    {
        $connEctStr = sprintf("host =%s port =%d dbname = %s user = %s password=%s",
            $this->config['database']['postgres_host'],
            $this->config['database']['postgres_port'],
            $this->config['database']['postgres_db_name'],
            $this->config['database']['postgres_app_user'],
            $this->config['database']['postgres_app_password']
        );
        print_r($connEctStr);
        $db = pg_connect($connEctStr);
//        print_r($db);
        if (!$db) {
            print_r("Error : Unable to open database\n");
        } else {
            print_r("Opened database successfully\n");
        }


        return $db;


    }

    private function getConfig(array $config): array
    {
        if (empty($config)) {
            $fileConfig = __DIR__ . "/../config/config.ini";
//        print_r($fileConfig . "\n");
            if (file_exists($fileConfig)) {
//            print_r($config);
                $config = parse_ini_file($fileConfig, true);
            } else {
                print_r("NOT FILE");
                $config = [];
//            $config = Config::get_exchange_coingecko();
            }
        }

        return $config;
    }

    public function checkRateAll(): bool
    {
        if (isset($this->config[self::CONFIG_PARAM_NAME]['coin_pairs'])) {
            $coinPairs = explode(',', $this->config[self::CONFIG_PARAM_NAME]['coin_pairs']);
            if (count($coinPairs) > 0) {
                foreach ($coinPairs as $pair) {
                    $rateData = $this->getExchangeRate(trim($pair));
                    $this->addCurrencyRate( $rateData);

                }
            }

        }

        return true;
    }

    public function getExchangeRate(string $pair): array
    {
        list($ids, $vs_currencies) = explode("/", $pair);

        $currentRequest = "simple/price";
        $idIn = $this->getCurrencyIdWithParam($ids, 'name_coingecko');
        $idOut = $this->getCurrencyIdWithParam($vs_currencies, 'name_coingecko');
        if ($idIn >0 && $idOut>0){
            $params = [
                'ids' => $ids,
                'vs_currencies' => $vs_currencies
            ];

            $response = $this->getResponse($params, $currentRequest);
        }
        $responseRes =json_decode($response);
        $rate = $responseRes->$ids->$vs_currencies;
        print_r($responseRes);
          print_r($rate);

        $result = [
            'in_currency_id'=>$idIn,
            'out_currency_id'=>$idOut,
            'rate'=>$rate,
//          'responce'=>$responseRes
        ];
        return $result;
    }

    private function getCurrencyIdWithParam(string $val, string $nameParam = 'name_coingecko')
    {
        if (!isset($this->currencyList[$val])) {
            $sql = 'SELECT id  FROM currency WHERE ' . $nameParam . "='" . pg_escape_string($val) . "'";
//            print_r($sql . "\n");
            $res = pg_query($this->db, $sql);

            $id = pg_fetch_result($res, 0, 0);
            if (empty($id)){
                $id = 0;
            } else {
                $this->currencyList[$val] = $id;
                $this->addToLog('error',"Not in datebases currency '". $val . "'");
            }

//            print_r("Base=" . $id . "\n");
        } else {
            $id = $this->currencyList[$val];
//            print_r("Arr=" . $id . "\n");
        }
        return $id;
    }

    /**
     * @param array $params
     * @param string $currentRequest
     * @return bool|string
     */
    public function getResponse(array $params, string $currentRequest): string|bool
    {
        $paramGet = http_build_query($params);
        $url = $this->config[self::CONFIG_PARAM_NAME]['url_api'] . '/' . $currentRequest . '?' . http_build_query($params);
//        print_r($url . "\n");
        $curl = curl_init();

        curl_setopt_array($curl, [
            //    CURLOPT_URL => 'https://api.coingecko.com/api/v3//simple/price?ids=%7B%7Bids%7D%7D&vs_currencies=%7B%7Bvs_currencies%7D%7D',
            CURLOPT_URL => $url,
//            CURLOPT_URL =>'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);
        if ($this->config[self::CONFIG_PARAM_NAME]['debug']) {
            curl_setopt_array($curl, [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
        }

        if (!$response = curl_exec($curl)) {
            trigger_error(curl_error($curl));
        }
        curl_close($curl);
        print_r($response);
        print_r("\n");
        return $response;
    }

    private function addCurrencyRate(array $param): bool
    {

        try {
            pg_insert($this->db, self::TABLE_NAME, $param);
            $this->addToLog('log',"Add to table'".self::TABLE_NAME. 'Data '. print_r($param, true) . "'");

        } catch (\Throwable $e) {
            $this->addToLog('error', 'Error insert base. Info:'. $e->getMessage());
        }
        return true;
    }


    /**
     * Method incremants logs
     * @param string $type
     * @param string $text
     * @return void
     */
    private function addToLog(string $type, string $text)
    {
        print_r("Message type " . $type . ':' . $text);
    }
}