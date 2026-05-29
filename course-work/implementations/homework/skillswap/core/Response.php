<?php

namespace App\Core;

/**
 * Response class for building and sending HTTP responses.
 * Provides methods for setting status codes, headers, and response body content.
 */
class Response
{
	protected int $statusCode = 200;
	protected array $headers = [];
	protected string $body = '';

	/**
	 * Set the HTTP status code for the response.
	 * 
	 * @param int $code The HTTP status code (e.g., 200, 404, 500).
	 * @return self Returns the Response instance for method chaining.
	 */
	public function setStatus(int $code): self
	{
		$this->statusCode = $code;
		return $this;
	}

	/**
	 * Add a header to the response.
	 * 
	 * @param string $key The header name (e.g., 'Content-Type').
	 * @param string $value The header value (e.g., 'application/json').
	 * @return self Returns the Response instance for method chaining.
	 */
	public function header(string $key, string $value): self
	{
		$this->headers[$key] = $value;
		return $this;
	}

	/**
	 * Set the response body as HTML content.
	 * 
	 * @param string $content The HTML content to send in the response body.
	 * @return self Returns the Response instance for method chaining.
	 */
	public function html(string $content): self
	{
		$this->header('Content-Type', 'text/html; charset=utf-8');
		$this->body = $content;
		return $this;
	}

	/**
	 * Set the response body as JSON content.
	 * 
	 * @param array $data The data to encode as JSON and send in the response body.
	 * @return self Returns the Response instance for method chaining.
	 */
	public function json(array $data): self
	{
		$this->header('Content-Type', 'application/json; charset=utf-8');
		$this->body = json_encode($data);
		return $this;
	}

	/**
	 * Set a redirect response to the specified URL.
	 * 
	 * @param string $url The URL to redirect to (can be relative or absolute).
	 * @param int $status The HTTP status code for the redirect (default is 302).
	 * @return self Returns the Response instance for method chaining.
	 */
	public function redirect(string $url, int $status = 302): self
	{
		$this->setStatus($status);
		$this->header('Location', '/' . ltrim($url, '/'));
		$this->body = '';
		return $this;
	}

	public function error(string $message, int $status = 500): self
	{
		if ($status < 400 || $status > 599) $status = 500;

		$this->setStatus($status);
		$this->html(View::render(
			'errors/' . (string)$status,
			['errors' => $message]
		));

		return $this;
	}

	/**
	 * Send the HTTP response to the client, including status code, headers, and body.
	 */
	public function send(): void
	{
		http_response_code($this->statusCode);

		foreach ($this->headers as $key => $value) {
			header("{$key}: {$value}");
		}

		echo $this->body;
	}
}
