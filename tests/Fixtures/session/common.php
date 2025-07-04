<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Dotenv\Dotenv;
use Eccube\Session\Storage\Handler\SameSiteNoneCompatSessionHandler;
use Symfony\Component\HttpFoundation\Request;

$parent = __DIR__;
while (!@file_exists($parent.'/vendor/autoload.php')) {
    if (!@file_exists($parent)) {
        // open_basedir restriction in effect
        break;
    }
    if ($parent === dirname($parent)) {
        echo "vendor/autoload.php not found\n";
        exit(1);
    }

    $parent = dirname($parent);
}

require $parent.'/vendor/autoload.php';
if (file_exists($parent.'/.env')) {
    Dotenv::createUnsafeMutable($parent, '.env')->load();
}

Request::setTrustedProxies(
    ['127.0.0.1', '::1', 'REMOTE_ADDR'],
    Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO ^ Request::HEADER_X_FORWARDED_HOST
);
Request::setTrustedHosts(['127.0.0.1', '::1']);
Request::createFromGlobals();

error_reporting(-1);
ini_set('html_errors', 0);
ini_set('display_errors', 1);
ini_set('session.gc_probability', 0);
ini_set('session.serialize_handler', 'php');
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_domain', '');
ini_set('session.cookie_secure', '');
ini_set('session.cookie_httponly', '');
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cache_expire', 180);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.lazy_write', 1);
ini_set('session.name', 'sid');
ini_set('session.save_path', __DIR__);
ini_set('session.cache_limiter', '');

header_remove('X-Powered-By');
header('Content-Type: text/plain; charset=utf-8');

register_shutdown_function(function () {
    echo "\n";
    session_write_close();
    print_r(headers_list());
    echo "shutdown\n";
});
ob_start();

class MockSessionHandler extends SessionHandler
{
    private $data;

    public function __construct($data = '')
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    #[ReturnTypeWillChange]
    public function open($path, $name)
    {
        return parent::open($path, $name);
    }
}
class TestSessionHandler extends SameSiteNoneCompatSessionHandler
{
    private $data;
    private $sessionId;

    public function __construct(SessionHandlerInterface $handler)
    {
        parent::__construct($handler);
        $this->data = $handler->getData();
    }

    #[ReturnTypeWillChange]
    public function open($path, $name): bool
    {
        echo __FUNCTION__, "\n";

        return parent::open($path, $name);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function validateId($sessionId): bool
    {
        echo __FUNCTION__, "\n";

        return parent::validateId($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function read($sessionId): string
    {
        echo __FUNCTION__, "\n";

        return parent::read($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function updateTimestamp($sessionId, $data): bool
    {
        echo __FUNCTION__, "\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function write($sessionId, $data): bool
    {
        echo __FUNCTION__, "\n";

        return parent::write($sessionId, $data);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function destroy($sessionId): bool
    {
        echo __FUNCTION__, "\n";

        return parent::destroy($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function close(): bool
    {
        echo __FUNCTION__, "\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function gc($maxLifetime): int|false
    {
        echo __FUNCTION__, "\n";

        return true;
    }

    protected function doRead($sessionId): string
    {
        if (isset($this->sessionId) && $sessionId !== $this->sessionId) {
            echo __FUNCTION__.": invalid sessionId\n";

            return '';
        }
        echo __FUNCTION__.': ', $this->data, "\n";
        $this->sessionId = $sessionId;

        return $this->data;
    }

    protected function doWrite($sessionId, $data): bool
    {
        echo __FUNCTION__.': ', $data, "\n";
        $this->sessionId = $sessionId;

        return true;
    }

    protected function doDestroy($sessionId): bool
    {
        echo __FUNCTION__, "\n";
        $this->sessionId = $sessionId;

        return true;
    }
}
