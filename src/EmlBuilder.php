<?php

namespace FilippoToso\EmlBuilder;

use Exception;

class EmlBuilder
{
    protected $headers = [];
    protected $subject = null;
    protected $from = null;
    protected $to = [];
    protected $cc = [];

    protected $text = null;
    protected $html = null;

    public function __construct($from = null, $to = [], $subject = null, $text = null, $html = null, $cc = [], $headers = [])
    {
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->text = $text;
        $this->html = $html;
        $this->cc = $cc;
        $this->headers = $headers;
    }

    public function headers($value = null)
    {
        return $this->field('headers', $value);
    }

    public function header($name, $value = null)
    {
        if (is_null($value)) {
            unset($this->headers[$name]);
        } else {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    public function subject($value = null)
    {
        return $this->field('subject', $value);
    }

    public function text($value = null)
    {
        return $this->field('text', $value);
    }

    public function html($value = null)
    {
        return $this->field('html', $value);
    }

    public function from($value = null)
    {
        return $this->fields('from', $value);
    }

    public function to($value = null)
    {
        return $this->fields('to', $value);
    }

    public function cc($value = null)
    {
        return $this->fields('cc', $value);
    }

    protected function field($name, $value = null)
    {
        if (is_null($value)) {
            return $this->$name;
        }

        $this->$name = $value;

        return $this;
    }

    protected function fields($field, $value = null)
    {
        if (is_null($value)) {
            return $this->$field;
        }

        $values = is_array($value) ? $value : [$value];

        foreach ($values as $current) {
            $address = is_a($current, Address::class) ? $current->address : $current;
            $name = is_a($current, Address::class) ? $current->name : null;

            $this->$field[] = Address::make($address, $name);
        }

        return $this;
    }

    protected function addresses($addresses)
    {
        $results = [];

        foreach ($addresses as $address) {
            $results[] = $address->get();
        }

        return implode(', ', $results);
    }

    public function get()
    {
        $result = '';
        $EOL = "\r\n";

        $headers = $this->headers;

        if (is_string($this->subject)) {
            $headers["Subject"] = $this->subject;
        }

        if (!empty($this->from)) {
            $headers["From"] = $this->addresses($this->from);
        }

        if (!empty($this->from)) {
            $headers["To"] = $this->addresses($this->to);
        }

        if (!empty($this->from)) {
            $headers["Cc"] = $this->addresses($this->cc);
        }

        $boundary = "----=" . self::guid();
        if (!isset($headers['Content-Type'])) {
            $headers["Content-Type"] = 'multipart/mixed;' . $EOL . 'boundary="' . $boundary . '"';
        } else {
            $name = self::getBoundary($headers["Content-Type"]);
            $boundary = empty($name) ? $boundary : $name;
        }

        $pattern = '#\r?\n#m';

        foreach ($headers as $key => $value) {
            if (empty($value)) {
                continue; //Skip missing headers
            } elseif (is_string($value)) {
                $result .= sprintf('%s: %s%s', $key, preg_replace($pattern, $EOL . ' ', $value), $EOL);
            } else { //Array
                foreach ($value as $subKey => $subValue) {
                    $result .= sprintf('%s: %s%s', $subKey, preg_replace($pattern, $EOL . ' ', $subValue), $EOL);
                }
            }
        }

        //Start the body
        $result .= $EOL;

        if (!is_null($this->text)) {
            $result .= "--" . $boundary . $EOL;
            $result .= "Content-Type: text/plain; charset=utf-8" . $EOL;
            $result .= $EOL;
            $result .= $this->text;
            $result .= $EOL . $EOL;
        }

        if (!is_null($this->html)) {
            $result .= "--" . $boundary . $EOL;
            $result .= "Content-Type: text/html; charset=utf-8" . $EOL;
            $result .= $EOL;
            $result .= $this->html;
            $result .= $EOL . $EOL;
        }

        return $result;
    }

    public function save($path)
    {
        file_put_contents($path, $this->get());
    }

    protected function guid()
    {
        $len = 32;

        $bytes = '';

        if (function_exists('random_bytes')) {
            try {
                $bytes = random_bytes($len);
            } catch (Exception $e) {
            }
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($len);
        }

        if ($bytes === '') {
            $bytes = hash('sha256', uniqid((string) mt_rand(), true), true);
        }

        return str_replace(['=', '+', '/'], '', base64_encode(hash('sha256', $bytes, true)));
    }

    protected function getBoundary($contentType)
    {
        $pattern = '/boundary="?(.+?)"?(\s*;[\s\S]*)?$/im';

        preg_match_all($pattern, $contentType, $match);

        return $match[1][0] ?? null;
    }
}
