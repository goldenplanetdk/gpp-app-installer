<?php

namespace GoldenPlanet\Gpp\App\Installer;

class CurlHttpClient implements Client
{

    public function request($method, $url, $options = [])
    {
        $defaults = [
            'headers' => [],
            'form_params' => [],
            'query' => [],
        ];

        $options = array_merge($defaults, $options);

        $url = $this->curlAppendQuery($url, $options['query']);
        $ch = curl_init($url);
        $this->curlSetopts($ch, $method, $options['form_params'], $options['headers']);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new \Exception($error, $errno);
        }
        list($message_headers, $message_body) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);

        $headers = $this->curlParseHeaders($message_headers);
        if ($headers['http_status_code'] >= 400) {
            throw new \DomainException(
                sprintf(
                    "Bad request. [%d]\nURL:%s\nMETHOD: %s\nBody:%s",
                    $headers['http_status_code'],
                    $url,
                    $method,
                    $message_body
                )
            );
        }

        return $message_body;
    }

    private function curlAppendQuery($url, $query)
    {
        if (empty($query)) return $url;
        if (is_array($query)) return "$url?" . http_build_query($query);
        else return "$url?$query";
    }

    private function curlSetopts($ch, $method, $payload, $request_headers)
    {
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($request_headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        if ($method != 'GET' && !empty($payload)) {
            if (is_array($payload)) $payload = http_build_query($payload);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
    }

    private function curlParseHeaders($message_headers)
    {
        $header_lines = preg_split("/\r\n|\n|\r/", $message_headers);
        $headers = array();
        list(, $headers['http_status_code'], $headers['http_status_message']) = explode(' ', trim(array_shift($header_lines)), 3);
        foreach ($header_lines as $header_line) {
            list($name, $value) = explode(':', $header_line, 2);
            $name = strtolower($name);
            $headers[$name] = trim($value);
        }
        return $headers;
    }
}
