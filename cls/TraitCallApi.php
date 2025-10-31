<?php
namespace eBizindia;
trait TraitCallApi{
	private $req_headers;
	private $req_data;
	private $req_data_type; // form-data, x-www-form-urlencoded, raw
	private $resp_body;
	private $resp_http_code;
	private $time_taken_by_api;
	private $api_call_debug_info = [];

	public function __get($name){
		return $this->{$name};
	}
	
	private function callApi(string $endpoint, string $method=''): void{
		$this->resp_body = '';
		$this->resp_http_code='';
		$this->time_taken_by_api = 0;
		$this->api_call_debug_info[] = $endpoint;
		$ch = curl_init($endpoint);
		if(strcasecmp($method,'POST')===0)
			curl_setopt($ch, CURLOPT_POST, true);
		else if(!empty($method)){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}
		if(!empty($this->req_headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->req_headers);
		curl_setopt($ch, CURLOPT_HEADER, true);
		// curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 1); 
		// curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		// curl_setopt ($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		if($this->req_data_type=='x-www-form-urlencoded'){
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->req_data));			
		}else{
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->req_data);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$start_time = microtime(true); // start timer
		$server_output = curl_exec($ch);
		$end_time = microtime(true);
		$this->time_taken_by_api = round(($end_time-$start_time)*1000,0); // recording time in mi
		$info = curl_getinfo($ch);
		$header_size = $info['header_size'];
		$this->resp_http_code = $info['http_code'];
		if($this->resp_http_code>0)
			$this->resp_body = substr($server_output, $header_size);
		else
			$this->resp_body = '';
		$this->api_call_debug_info[] = $info;
		$this->api_call_debug_info[] = curl_error($ch);
		$this->api_call_debug_info[] = $server_output;
		curl_close($ch);
	}

}