<?php

/**
 * Class ShipitApiClient
 *
 * This is a simple example class for a Shipit API client.
 * Do not use as is in production!
 *
 * @copyright 2017 - Oy Site Logic Ab
 * @author    Jesse Ihatsu <jesse.ihatsu@sitelogic.fi>
 */
class ShipitApiClient {

    /**
     * API URL
     */
    protected $url = "http://apitest.shipit.ax/";

    /**
     * Request methods.
     */
    const METHOD_POST = 0x01;
    const METHOD_GET = 0x02;

    /**
     * API key
     * @var string
     */
    protected $key = "";

    /**
     * API secret
     * @var string
     */
    protected $secret = "";

    /**
     * API version
     * @var string
     */
    protected $version = "";

    /**
     * Request method.
     * @var int
     */
    protected $method = 0;

    /**
     * Payload to send.
     * @var array
     */
    protected $payload = [];

    /**
     * ShipitApiClient constructor.
     *
     * @param string $key     API key
     * @param string $secret  API secret
     * * @param string $url  API Url
     * @param string $version [optional] API version, defaults to v1.
     */
    public function __construct(string $key, string $secret, $url, $version = null) {
        if(!$version) {
		$version = 'v1';
        }   
        $this->key = $key;
        $this->secret = $secret;
        $this->version = $version;
        $this->url = $url;
    }

    public function setApiUrl($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * Set payload for request.
     *
     * @param array $payload [optional] Set or reset payload.
     *
     * @return $this
     */
     public function payload(array $payload = []){	
        $this->payload = (array) $payload;

        return $this;
    }

    /**
     * Make a POST request to API.
     *
     * @param string $endpoint API endpoint to call.
     *
     * @return array Array with request details and response header and body.
     */
    public function post(string $endpoint) {
        $this->method = self::METHOD_POST;

        return $this->execute(
                        $endpoint, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($this->payload),
                    CURLOPT_CUSTOMREQUEST => "POST"
                        ]
        );
    }

    /**
     * Make a PUT request to API.
     *
     * @param string $endpoint API endpoint to call.
     *
     * @return array Array with request details and response header and body.
     */
    public function put(string $endpoint) {
        return $this->execute(
                        $endpoint, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($this->payload),
                    CURLOPT_CUSTOMREQUEST => "PUT"
                        ]
        );
    }

    /**
     * Make a GET request to API.
     *
     * @param string $endpoint API endpoint to call.
     *
     * @return array Array with request details and response header and body.
     */
    public function get(string $endpoint) {
        $this->method = self::METHOD_GET;
        $query = http_build_query($this->payload);

        return $this->execute($endpoint . (empty($query) ? "" : "?" . $query));
    }

    /**
     * Execute API call.
     *
     * @param string $endpoint API endpoint to call.
     * @param array  $options  CURL options.
     *
     * @return array Array with request details and response header and body.
     */
    public function execute(string $endpoint, array $options = []) {
        $url = $this->url . $this->version . "/" . $endpoint;
        $ch = curl_init();
        $options = array_replace_recursive(
                [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-Shipit-Key: " . $this->key,
                "X-Shipit-Checksum: " . $this->calculateChecksum(),
            ],
                ], $options
        );
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = $this->parseHeader(mb_substr($response, 0, $headerSize));
        $body = json_decode(mb_substr($response, $headerSize), true);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            "status" => $status,
            "request" => [
                "url" => $url,
                "headers" => array_key_exists(CURLOPT_HTTPHEADER, $options) ? $options[CURLOPT_HTTPHEADER] : "",
                "post" => array_key_exists(CURLOPT_POSTFIELDS, $options) ? $options[CURLOPT_POSTFIELDS] : "",
            ],
            "response" => compact("header", "body", "response"
            ),
        ];
    }

    /**
     * Parse header data.
     *
     * @param string $header Raw header data from response.
     *
     * @return array
     */
    protected function parseHeader($header) {
        $headerArray = explode("\r\n", $header);
        $result = [];

        foreach (array_filter($headerArray) as $row) {
            if (preg_match('#([^:]+): (.*)#', $row, $match)) {
                $result[$match[1]] = $match[2];
            } else if (mb_substr($row, 0, 4) === "HTTP") {
                $parts = explode(" ", $row);
                $result["Protocol"] = $parts[0];
                $result["Status-Code"] = $parts[1];
                $result["Status-Message"] = $parts[2];
            }
        }

        return $result;
    }

    /**
     * Calculate payload checksum.
     * @return string
     */
    protected function calculateChecksum() {
        return hash("sha512", json_encode($this->payload) . $this->secret);
    }

}
