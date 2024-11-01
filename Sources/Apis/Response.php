<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Apis;


use Exception;
use Requests_Utility_CaseInsensitiveDictionary;
use WP_Error;

class Response
{
    private $Head = [];

    private $Data = "";

    private $Error;

    private $Success;

    /**
     * Allow types
     * @var array
     */
    private $Types = [
        "GET"
    ];

    /**
     * wordpress error class.
     * @var WP_Error
     */
    private $Errors;

    /**
     * Http response code
     * @var int
     */
    private $Code = 0;
    
    /**
     * @var array
     */
    private $Config = [
        "url"  => "",
        "type" => "",
        "data" => []
    ];

    /**
     * Response constructor.
     *
     * @param  array  $config
     */
    public function __construct(array $config)
    {
        $this->Config = array_merge($this->Config, $config);
    }

    /**
     * Populate request and send and receive response from api.
     */
    public function Populate(): void
    {
        $data = $this->Access();
        if ($this->Success) {
            try {
                if ( ! empty($data)) {
                    $body       = wp_remote_retrieve_body($data);
                    $head       = wp_remote_retrieve_headers($data);
                    $this->Code = (int)wp_remote_retrieve_response_code($data);
                    if (isset($head) && $head instanceof Requests_Utility_CaseInsensitiveDictionary && isset($body) && $body !== "" && $this->Code === 200) {
                        $this->Head = $head->getAll();
                        //$this->Data = json_decode($body);
                        $this->Data = $body;
                    }
                }
                return;
            } catch (Exception $e) {
                if ($this->Code !== 200) {
                    $this->GenerateErrors($e->getCode(), $e->getMessage(), $e);
                }
            }
        }
        if ( ! $this->Code !== 200) {
            $this->GenerateErrors(
                $this->Code,
                "Something went wrong and there was not error message generated!",
                $data
            );
        }
        $this->Error = true;
    }

    /**
     * access the server and return an array of response.
     * @return array
     */
    private function Access(): array
    {
        $data = [];
        $type = $this->GetConfig("type");
        $url  = $this->GetConfig("url");
        if ($url !== "" && $type !== "") {
            try {
                switch ($type) {
                    case "POST":
                        $args = $this->GetConfig("data");
                        $data = wp_remote_post($url, $args);
                        break;
                    default :
                        $data = wp_remote_get(esc_url_raw($url));
                }
                $this->Success = true;
            } catch (Exception $e) {
                $this->Success = false;
            }
        } else {
            $this->GenerateErrors(0, "Request type must be one of those: [".implode(", ", $this->Types)."].");
        }
        if ( ! is_array($data)) {
            if ($data instanceof WP_Error) {
                $this->Errors  = $data;
                $this->Success = false;
            }
            $data = [];
        }

        return $data;
    }

    /**
     * Get a single config settings.
     *
     * @param  string  $name  key to find
     *
     * @return string value
     */
    private function GetConfig(string $name): string
    {
        return isset($this->Config[$name]) ? $this->Config[$name] : "";
    }

    /**
     * Generate error messages from the request.
     *
     * @param  int  $code  error code
     * @param  string  $message  error message
     * @param  null  $data  error data.
     */
    private function GenerateErrors(int $code, string $message, $data = null)
    {
        if ( ! isset($this->Errors)) {
            $this->Errors = new WP_Error($code, $message, $data);
        }
    }

    /**
     * Get all headers from request.
     * @return array
     */
    public function GetHeaders(): array
    {
        return $this->Head;
    }

    /**
     * Get a single header info from request.
     *
     * @param  string  $name
     *
     * @return mixed|null
     */
    public function GetHeader(string $name)
    {
        return (is_string($name) && $name !== "" && isset($this->Head[$name])) ? $this->Head[$name] : null;
    }

    /**
     * Get the Response from request.
     * @return mixed
     */
    public function GetData(): string
    {
        return $this->Data;
    }

    /**
     * Call is success or not.
     * @return bool
     */
    public function IsSuccess(): bool
    {
        return $this->Success;
    }
}