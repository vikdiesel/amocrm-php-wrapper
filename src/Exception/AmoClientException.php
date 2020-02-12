<?php

namespace AmoCrmPhpWrapper\Package\Exception;

use GuzzleHttp\Exception\RequestException;

class AmoClientException extends \RuntimeException implements AmoClientExceptionInterface {

	public function __construct( RequestException $exception ) {
		$message = 'AmoCRM Error';
		$code = null;

		if ($exception->hasResponse()) {
			$data = json_decode($exception->getResponse()->getBody()->getContents());

			if (!empty($data->hint)) {
				$message = $data->hint;
			} elseif (!empty($data->title)) {
				$message = $data->title;

				if (!empty($data->detail)) {
					$message .= ' / ' . $data->detail;
				}
			}

			if (!empty($data->status)) {
				$code = $data->status;
			}
		}

		parent::__construct($message, $code, $exception);
	}
}
