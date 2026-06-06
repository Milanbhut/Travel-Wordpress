<?php
namespace AdSenseChecklist;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * In-memory memoisation of wp_remote_get / wp_remote_head responses.
 * One default singleton per PHP request. Tests call reset() in setUp/tearDown.
 */
class Http_Cache
{
    private static ?Http_Cache $instance = null;
    private array $get_cache  = [];
    private array $head_cache = [];

    public static function instance(): Http_Cache
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * @return array{code:int, body:string, headers:array, error:?string}
     */
    public function get(string $url, int $timeout = 5): array
    {
        if (isset($this->get_cache[$url])) {
            return $this->get_cache[$url];
        }
        $response = \wp_remote_get($url, ['timeout' => $timeout, 'redirection' => 3]);
        return $this->get_cache[$url] = $this->normalize($response, true);
    }

    /**
     * @return array{code:int, headers:array, error:?string}
     */
    public function head(string $url, int $timeout = 5): array
    {
        if (isset($this->head_cache[$url])) {
            return $this->head_cache[$url];
        }
        $response = \wp_remote_head($url, ['timeout' => $timeout, 'redirection' => 3]);
        return $this->head_cache[$url] = $this->normalize($response, false);
    }

    private function normalize($response, bool $with_body): array
    {
        if (\is_wp_error($response)) {
            $err = [
                'code'    => 0,
                'headers' => [],
                'error'   => 'wp_error',
            ];
            if ($with_body) {
                $err['body'] = '';
            }
            return $err;
        }
        $headers_obj = \wp_remote_retrieve_headers($response);
        $headers = is_array($headers_obj) ? $headers_obj : (array) $headers_obj;
        $result = [
            'code'    => (int) \wp_remote_retrieve_response_code($response),
            'headers' => $headers,
            'error'   => null,
        ];
        if ($with_body) {
            $result['body'] = (string) \wp_remote_retrieve_body($response);
        }
        return $result;
    }
}
