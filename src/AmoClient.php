<?php

namespace AmoCrmPhpWrapper\Package;

use AmoCrmPhpWrapper\Package\Exception\AmoClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;

class AmoClient {
	private $schema = 'https';
	private $client;

	private $accessTokenFile;

	private $domain;
	private $client_id;
	private $client_secret;
	private $initial_code;
	private $redirect_uri;

	private $accessTokenData;
	private $accessTokenIsActive = false;
	private $accessTokenExpiresCorrection = 100;

	private $time;

	public function __construct($domain, $client_id, $client_secret, $redirect_uri, $initial_code = null) {
		$this->accessTokenFile = __DIR__ . '/../var/accessToken.json';

		$this->time = time();

		$this->domain = $domain;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->redirect_uri = $redirect_uri;
		$this->initial_code = $initial_code;

		$this->initClient();

		$this->setAccessToken();
	}

	private function initClient() {
		$clientParams = [
			'base_uri' => $this->schema . '://' . $this->domain,
		];

		if (!empty($this->accessTokenData)) {
			$clientParams['headers'] = [
				'Authorization' => 'Bearer ' . $this->accessTokenData->access_token,
				'Accept' => 'application/json'
			];
		}

		$this->client = new Client($clientParams);
	}

	private function refreshTokenOrGetInitial () {
		if ($this->accessTokenIsActive) {
			return;
		}

		$requestPayload = [
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri' => $this->redirect_uri
		];

		if (empty($this->accessTokenData)) {
			$requestPayload['grant_type'] = 'authorization_code';
			$requestPayload['code'] = $this->initial_code;
		} else {
			$requestPayload['grant_type'] = 'refresh_token';
			$requestPayload['refresh_token'] = $this->accessTokenData->refresh_token;
		}

		$responseString = $this->request('/oauth2/access_token', $requestPayload, 'form_params', true);

		file_put_contents($this->accessTokenFile, $responseString);

		$this->accessTokenIsActive = true;
		$this->accessTokenData = json_decode($responseString);
	}

	private function setAccessToken () {
		if (file_exists($this->accessTokenFile)) {
			$f = file_get_contents($this->accessTokenFile);

			$accessTokenData = json_decode($f);

			$this->accessTokenIsActive = (filemtime($this->accessTokenFile) + $accessTokenData->expires_in - $this->accessTokenExpiresCorrection) > $this->time;

			if ($this->accessTokenIsActive) {
				$this->accessTokenData = $accessTokenData;
			}
		}

		$this->refreshTokenOrGetInitial();

		$this->initClient();
	}

	public function request ($link, $payload = null, $payload_type = 'json', $is_return_raw = false) {
		$method = 'GET';
		$requestOptions = [];

		if (!empty($payload)) {
			$method = 'POST';
			$requestOptions = [
				$payload_type => $payload
			];
		}

		try {
			$response = $this->client->request($method, $link, $requestOptions);

			$bodyContents = $response->getBody()->getContents();

			if ($is_return_raw) {
				return $bodyContents;
			}

			return json_decode($bodyContents);

		} catch (RequestException $exception) {
			throw new AmoClientException($exception);
		}
	}
}
