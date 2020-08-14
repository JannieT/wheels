<?php

/**
 * @author      Jannie Theunissen <jannie@onesheep.org>
 * @copyright   Copyright (c), 2020 Jannie Theunissen
 * @license     MIT public license
 */

namespace Skateboard\Wheels;

class WebController
{
    /**
     * @var string the file path to the views folder relative to the controller
     */
    public $viewPath = '../views';

    /**
     * @var string file name and extension of a template layout to wrap your views
     */
    public $layout = 'layout.php';

    /**
     * @var array of all request inputs lazyloaded on first call to ->input()
     */
    private $requestInput;

    /**
     * @var array of all request headers lazyloaded on first call to ->getHeaderLine()
     */
    private $requestHeaders;

    /**
     * Render a view template with some data.
     *
     * @param string $view file name of the view in the viewPath folder such as 'about'
     * @param array $data items that will be escaped and provided to the view
     * @param array $raw safe items that will be rendered without escaping
     */
    public function view($view, $data = [], $raw = [])
    {
        $output = $this->render("{$this->viewPath}/$view.php", $data, $raw);

        if (! empty($this->layout)) {
            $raw['content'] = $output;
            $output = $this->render("{$this->viewPath}/{$this->layout}", [], $raw);
        }

        $this->html($output);
    }

    /**
     * Render a json response.
     *
     * @param mixed $output that will be json encoded
     * @param int $code http status code
     */
    public function json($output, $code = 200)
    {
        header('Content-Type: application/json', true, $code);
        echo json_encode($output);
    }

    /**
     * Redirects the request to another route.
     *
     * @param string the relative or absolute route to redirect to such as "/about"
     */
    public function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    /**
     * Exits the request with a status code.
     *
     * @param int $code the 3 digit http response code
     * @param string $html any markup to return in the response body
     */
    public function abort($code, $html = null)
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
        ];

        $message = isset($messages[$code]) ? $messages[$code] : '';
        header("HTTP/1.1 $code $message");

        if ($this->requestIsJson()) {
            $this->json($message, $code);
            exit;
        }

        if (! empty($html)) {
            echo $html;
        }
        exit;
    }

    /**
     * Respond with html markup and a response code.
     *
     * @param string $output the html that should be sent in the response
     * @param int $code the http response code to respond with
     */
    public function html($output, $code = 200)
    {
        header('Content-Type: text/html', true, $code);
        echo $output;
    }

    /**
     * Render some data into a view file.
     *
     * @param string $file the full path name of the view file
     * @param array $data items that will be escaped and provided to the view
     * @param array $raw safe items that will be rendered without escaping
     * @return void
     */
    protected function render($file, $data, $raw = [])
    {
        $safe = $this->safe($data);

        try {
            ob_start();
            extract($this->safe($data));
            extract($raw);
            require $file;

            return ob_get_clean();
        } catch (\Throwable $e) { // PHP 7+
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Check if the request is a json request based on the Accept header.
     *
     * @return bool true if the Accept header specifies json format otherwise false
     */
    public function requestIsJson()
    {
        return $this->getHeaderLine('Accept') == 'application/json';
    }

    /**
     * Get the value of a request header.
     *
     * @param string $key the header key to use to look up the value such as 'Accept'
     * @param string $default the default to return if the key is not found in the request header
     * @return string the value of the specified request header
     */
    public function getHeaderLine($key, $default = null)
    {
        if ($this->requestHeaders == null) {
            $this->requestHeaders = $this->allRequestHeaders();
        }

        return isset($this->requestHeaders[$key]) ? $this->requestHeaders[$key] : $default;
    }

    /**
     * All the headers of the request.
     *
     * @return array of the header key/value pairs
     */
    protected function allRequestHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        foreach ($_SERVER as $name => $value) {
            /* RFC2616 (HTTP/1.1) defines header fields as case-insensitive entities. */
            if (strtolower(substr($name, 0, 5)) == 'http_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }

    /**
     * Escapes the html tag entities to make data safe to render to the screen.
     *
     * @param value|object|array of data to escape
     */
    protected function safe($data)
    {
        if (is_array($data)) {
            return array_map(function ($var) {
                return $this->safe($var);
            }, $data);
        }

        if (is_object($data)) {
            $escaped = [];
            foreach ($data as $key => $value) {
                $escaped[$key] = htmlspecialchars($value);
            }

            return (object) $escaped;
        }

        return htmlspecialchars($data);
    }

    /**
     * Get a request get parameter, post value or a cookie value.
     *
     * @param string $key that identifies the parameter or cookie to get
     * @return string the value that was passed with the request
     */
    public function input($key)
    {
        if ($this->requestInput == null) {
            $this->requestInput = $this->inputs();
        }

        return isset($this->requestInput[$key]) ? $this->requestInput[$key] : null;
    }

    /**
     * All the request's get parameters, post parameters and cookies.
     *
     * @return array of key/value input pairs
     */
    public function inputs()
    {
        if (! empty($_REQUEST)) {
            return $_REQUEST;
        }

        $body = file_get_contents('php://input');

        if ($this->requestIsJson()) {
            return json_decode($body, true);
        }

        return $body;
    }
}
