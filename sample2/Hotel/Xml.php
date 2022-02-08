<?php

namespace Hotel;

use Hotel\Xml\Signature;

class Xml {

	public Signature $sign;
	public string $secret_key;
	public int $hotel_id;
	private const ERRORS = [
		1 => "Внутренняя ошибка, информация отправлена администратору",
		100 => "Неверный XML",
		101 => "Неверный ID гостиницы",
		102 => "Неверная подпись запроса",
		104 => "Неверные параметры XML",

		201 => "Не установлен параметр room",
		202 => "Не установлен room ID",
		203 => "Неверный ID тарифа",
		204 => "room ID не верен",
		205 => "Повторяющийся room ID",
		206 => "Не присутствует параметр date в room ID",
		207 => "Неправильно задана дата",
		208 => "Дата должна быть не ранее сегодняшней даты",
		209 => "Повторяющаяся дата",
		210 => "closed может быть 1 или 0",
		211 => "release_days может принимать целочисленные значения от 0 до 30",
		212 => "breakfastIncluded может быть 1 или 0",
		213 => "guarantee может быть 1 или 0",
		214 => "sellquantity может принимать целочисленные значения от 0 и выше",
		215 => "Укажите количество свободных номеров sellquantity при открытых продажах closed = 0",
		216 => "Ошибка в параметрах дат: максимальный период между крайними датами может составлять не более 360 дней",
		217 => "Неправильно заданы цены",

		301 => "Не задан список бронирований reservation_id",
		302 => "Не существует бронирование reservation_id",
		303 => "Можно загружать максимум 20 бронирований одновременно",

		401 => "Неправильно задана дата from_date",
		402 => "Дата from_date должна быть не позднее сегодняшней даты",
		403 => "Ошибка в from_date: может отстоять от текущей даты не более чем на 14 дней",

		501 => "Не установлен либо неверен параметр sdate",
		502 => "Не установлен либо неверен параметр edate",
	];

	public bool $test = true;
	public $log_handle  = null;

	public ?string $http_response_string = null;
	public ?string $http_response_body = null;
	public array $incoming_request_data = [];
	public array $hotel_data = [];


    public function __construct (){
		$this->sign = new Signature;
	}


    /**
     * Register error handler
     */
    public function register_error_handle(): void {
		set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            $options = [
                'type'    => $errno,
                'message' => $errstr,
                'file'    => $errfile,
                'line'    => $errline,
                'isError' => true,
            ];
			$this->error_handling(new \Phalcon\Error\Error($options));
        } );
		
        set_exception_handler(function ($e) {
            $options = [
                'type'        => $e->getCode(),
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'isException' => true,
                'exception'   => $e,
            ];
            $this->error_handling(new \Phalcon\Error\Error($options));
        });
		
        register_shutdown_function(function () {
            if (!is_null($options = error_get_last())) {
				$this->error_handling(new \Phalcon\Error\Error($options));
            }
        });
	}

    /**
     * Handle errors
     *
     * @param \Phalcon\Error\Error $error
     */
    protected function error_handling(\Phalcon\Error\Error $error): void {
		
		

        $type = \Phalcon\Error\Handler::getErrorType($error->type());
        $message = "$type: {$error->message()} in {$error->file()} on line {$error->line()}";
		
		$logger = new \Phalcon\Logger\Adapter\File(TEMP_DIR . '/hotel_xml_error.log');
		$formatter = new \Phalcon\Logger\Formatter\Line('[%date%][%type%] %message%', 'Y-m-d H:i:s O');

		$logger->setFormatter($formatter);

		if ($error->type() === E_NOTICE || $error->type() === E_STRICT || $error->type() === E_DEPRECATED) {
            return;
        }
		
		$logger->log(\Phalcon\Error\Handler::getLogType($error->type()), $message);
	

		if (APPLICATION_ENV == 'production'){
			$this->sendErrorOnErrorHandlingByEmail($error);
		}

		if ($error->type() === E_WARNING || $error->type() === E_USER_WARNING) {
            return;
        }

		$this->send_error(1);
	}

    protected function sendErrorOnErrorHandlingByEmail(\Phalcon\Error\Error $error): void
    {
        $view = new \My\View;
        $view->setDi(\Phalcon\Di::getDefault());

        $data = print_r($_SERVER, true);
        $errcontext = '';

        if (!empty($this->incoming_request_data)){
            $errcontext .= "\n\nREQUEST\n------\n".print_r($this->incoming_request_data, true);
        }


        $view->setVar("time", date("d.m.Y H:i"));
        $view->setVar("globals", $data);
        $view->setVar("errno", $error->type());
        $view->setVar("errstr", $error->message());
        $view->setVar("errfile", $error->file());
        $view->setVar("errline", $error->line());
        $view->setVar("errcontext", $errcontext);

        $msgbody=$view->render("emails/error.volt");

        $email= 'null@null';

        $to      = $email;
        $headers = 'From: '.$email. "\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n";

        mail($to, 'XML Hotel Error at '.HOME_URL, $msgbody, $headers, '-f'.RETURN_PATH);
    }


    /**
     * Process incoming request
     *
     * @return array
     */
    public function incoming_request(): array {

		$this->incoming_request_data = [];


		if ($this->log_handle){
			fwrite($this->log_handle, "\n\n".date('d.m.Y H:i').". Incoming request\n-----------------------------\n");
		}

		$xml_string = file_get_contents("php://input");

		if (!$xml_string){
			$this->send_error(100);
		}

		$this->incoming_request_data["post_data"] = $xml_string;
		if ($this->log_handle){
			fwrite($this->log_handle, $xml_string."\n\n");
		}

		$xml_element = @simplexml_load_string($xml_string);
		if ($xml_element === false){
			$this->send_error(100);
		}
		
		$xml = (array) simpleXMLToArray($xml_element);
		

		$this->incoming_request_data["parsed_data"] = print_r($xml, true);
		if ($this->log_handle){
			fwrite($this->log_handle, $this->incoming_request_data["parsed_data"]."\n\n");
		}
		$this->hotel_id = isset($xml["hotel_id"]) && is_numeric($xml["hotel_id"]) ? intval($xml["hotel_id"]) : 0;
		$this->get_secret_key();

		$sig = $this->sign->makeXML( $this->sign->getOurScriptName(), trim($xml_string), $this->secret_key);
		if (!isset($xml["sig"]) || $xml["sig"] != $sig){
			$this->send_error(102);
		}

		unset($xml["salt"], $xml["sig"], $xml["hotel_id"]);

		return $xml;
	}

    /**
     * Process incoming request (TEST)
     *
     * @return array
     */

    public function incoming_request_test(): array {

		if ($this->log_handle){
			fwrite($this->log_handle, "\n\n".date('d.m.Y H:i').". Incoming request\n-----------------------------\n");
		}

		$xml_string = file_get_contents("php://input");

		if (!$xml_string){
			$this->send_error(100);
		} 

		if ($this->log_handle){
			fwrite($this->log_handle, $xml_string."\n\n");
		}

		$xml_element = @simplexml_load_string($xml_string);
		if ($xml_element === false){
			$this->send_error(100);
		}

		$xml = (array) simpleXMLToArray($xml_element);

		if ($this->log_handle){
			fwrite($this->log_handle, print_r($xml, true)."\n\n");
		}

		$this->get_secret_key();

		$sig = $this->sign->makeXML( $this->sign->getOurScriptName(), $xml_string, $this->secret_key);
		if (!isset($xml["sig"]) || $xml["sig"] != $sig){
			$this->send_error(102);
		}

        unset($xml["salt"], $xml["sig"], $xml["hotel_id"]);

		return $xml;
	}


    /**
     * Send error and exit
     *
     * @param int $error_id
     * @param string $error_info
     */
    public function send_error(int $error_id, string $error_info = ""): void {

		$this->send_response([
            "status" => "error",
            "error_code" => $error_id,
            "error_description" => $error_info,
        ]);

		if (APPLICATION_ENV == 'production' && !in_array($error_id, [1, 101, 100])){
            $this->sendErrorByEmail($error_id, $error_info);
		}
		exit;
	}

    /**
     * Send error in xml to admins email
     *
     * @param int $error_id
     * @param string $error_info
     */
    protected function sendErrorByEmail(int $error_id, string $error_info = ""): void {
        $view = new \My\View;
        $view->setDi(\Phalcon\Di::getDefault());

        $view->setVar("date", date("d.m.Y H:i:s"));
        $view->setVar("errno", $error_id);
        $view->setVar("errstr", static::ERRORS[$error_id]."\n".$error_info);
        $view->setVar("hotel_data", $this->hotel_data);

        $view->setVar("data", $this->incoming_request_data);

        $msgbody=$view->render("emails/error_hotel_xml.volt");

        $to      = 'null@null.ru';
        $headers = 'From: null@null.ru' . "\r\n";

        mail($to, 'XML Error at '.HOME_URL, $msgbody, $headers, '-f'.RETURN_PATH);
    }

    /**
     * Get secret key. Return false on unsuccess
     *
     * @param bool $incoming
     * @return bool
     */
    public function get_secret_key(bool $incoming = true): bool {

		$sql = "SELECT hotel.xml_log, hotel.id, hotel.name, city.name as city_name, country.name as country_name, channel_managers.status, hotel.xml_secret_key FROM m:Hotel hotel, m:HotelVendors hotel_vendors, m:City city, m:Country country, m:ChannelManagers channel_managers WHERE channel_managers.id = hotel.channel_manager_id AND hotel.city_id=city.id AND city.country_id = country.id AND hotel_vendors.hotel_id=hotel.id AND hotel_vendors.vendor_id=1 AND hotel.id=".$this->hotel_id." AND hotel.channel_manager_id IS NOT NULL AND hotel.xml_secret_key IS NOT NULL";
		
		if ($incoming){
			$sql .= " AND hotel_vendors.status > 0";
		}
	
		$q=new \My\Query($sql);
		$data = $q->execute();
		
		if ($data->count() == 0){
			if ($incoming) {
                $this->send_error(101);
            }
			return false;
		}
		
		$this->hotel_data = $data->getFirst()->toArray();
		$this->hotel_id = $this->hotel_data["id"];
		$this->secret_key = $this->hotel_data["xml_secret_key"];
		if ($this->hotel_data["status"] === 2) {
            $this->test = 0;
        }
		return true;
	}

    /**
     * Send response
     *
     * @param array $params
     */
    public function send_response (array $params): void {

		if ($this->secret_key){
			$params["salt"] = md5(uniqid());

			$XmlConstruct = new \XmlConstruct('response');
			$XmlConstruct->fromArray($params);
			$xml = $XmlConstruct->getDocument();

			$params["sig"] = $this->sign->makeXML($this->sign->getOurScriptName(), $xml, $this->secret_key);
		}

		if ($this->log_handle){
			fwrite($this->log_handle, date('d.m.Y H:i').". Response\n-----------------------------\n");
			fwrite($this->log_handle, print_r($params, true)."\n\n");
		}

		$XmlConstruct = new \XmlConstruct('response');
		$XmlConstruct->fromArray($params);
		$XmlConstruct->output();
	}

    /**
     * Send request
     *
     * @param string $url
     * @param array $params
     * @return array|false
     */
	public function send_request(string $url, array $params){

		$params["hotel_id"] = $this->hotel_id;
		$params["salt"] = md5(uniqid());

		$XmlConstruct = new \XmlConstruct('request');
		$XmlConstruct->fromArray($params);
		$xml = $XmlConstruct->getDocument();
        
		$params["sig"] = $this->sign->makeXML($this->sign->getScriptNameFromUrl($url), $xml, $this->secret_key);


		$XmlConstruct = new \XmlConstruct('request');
		$XmlConstruct->fromArray($params);
		$xml = $XmlConstruct->getDocument();

		if ($this->log_handle){
			fwrite($this->log_handle, date('d.m.Y H:i').". Request to ".$url."\n-----------------------------\n");
			fwrite($this->log_handle, $xml."\n\n");
		}

		$this->http_response_string = null;
		$this->http_response_body = null;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POST, True);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)"); 
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-type: text/html; charset=\"utf-8\"")); 
 
		$this->http_response_string = curl_exec($curl);
		if ($this->http_response_string === false){
			if ($this->log_handle){
				$error = curl_error($curl);
				fwrite($this->log_handle, date('d.m.Y H:i').". Request failed." .$error);

			}
			curl_close($curl);
			return false;
		}
		
		curl_close($curl);
		
		if ($this->log_handle){
			fwrite($this->log_handle, date('d.m.Y H:i').". Getting response\n-----------------------------\n");
			fwrite($this->log_handle, $this->http_response_string."\n\n");
		}

		$this->http_response_body = $this->http_response_string;

		$xml = Array();
		if ($this->http_response_body){
			libxml_use_internal_errors(true);
			$xml_element = @simplexml_load_string($this->http_response_body);
			if ($xml_element === false || !isset($xml_element)){
				fwrite($this->log_handle, date('d.m.Y H:i').". Error in response: bad XML ".$this->http_response_body."\n");
				return false;
			}

			$xml = (array) simpleXMLToArray($xml_element);

			$xml_sig = $this->sign->makeXML($this->sign->getScriptNameFromUrl($url), $xml_element, $this->secret_key);
			if (!isset($xml["status"]) || !isset($xml["sig"])){
				fwrite($this->log_handle, date('d.m.Y H:i').". Error in response: no status or sig\n");
				return false;
			}
			if ($xml["sig"] != $xml_sig){
				fwrite($this->log_handle, date('d.m.Y H:i').". Error in response: bad sig\n");
				return false;
			}

			if ($xml["status"] != "ok"){
				fwrite($this->log_handle, date('d.m.Y H:i').". Error in response: status is not ok\n");
				return false;
			}

			fwrite($this->log_handle, date('d.m.Y H:i').". XML parsed OK\n-----------------------------\n");
			fwrite($this->log_handle, print_r($xml, true)."\n\n");
			return $xml;
		}

        if ($this->log_handle){
            if (!$this->http_response_body) {
                fwrite($this->log_handle, date('d.m.Y H:i') . ". Error in response: no post body exists\n");
            }
            else {
                fwrite($this->log_handle, date('d.m.Y H:i') . ". Error in response:\n" . $this->http_response_string);
            }
        }
        return false;
    }

}
