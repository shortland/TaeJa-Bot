<?php

class Header {
	
	/**
	 * @var string
	 */
	private $contentType;

	/**
	 * @var bool
	 */
	private $displayErrors;

	public function __construct(
		string $contentType, 
		bool $displayErrors
	) {
		$this->contentType = $contentType;
		$this->displayErrors = $displayErrors;
		$this->show();
	}

	private function show() {
		switch ($this->contentType) {
			case 'html':
				header('Content-Type: text/html; charset=utf-8');
				break;
			case 'text':
				header('Content-Type: text; charset=utf-8');
				break;
			case 'text/html':
				header('Content-Type: text/html; charset=utf-8');
				break;
			case 'image':
				header('Content-Type: image/gif; charset=utf-8');
				break;
			default:
				header('Content-Type: text; charset=utf-8');
		}
		
		if ($this->displayErrors) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
		}

		ini_set("default_charset", "UTF-8");
		mb_internal_encoding("UTF-8");
	}
}