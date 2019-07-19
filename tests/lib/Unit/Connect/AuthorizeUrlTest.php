<?php

namespace CloudCreativity\LaravelStripe\Tests\Unit\Connect;

use CloudCreativity\LaravelStripe\Connect\AuthorizeUrl;
use PHPUnit\Framework\TestCase;
use Stripe\Stripe;
use Stripe\Util\Util;

class AuthorizeUrlTest extends TestCase
{

    /**
     * @var AuthorizeUrl
     */
    private $url;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        Stripe::setClientId('my_client_id');
        $this->url = new AuthorizeUrl('state_secret');
    }

    /**
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();
        Stripe::setClientId(null);
    }

    /**
     * @return array
     */
    public function valueProvider()
    {
        return [
            'read_only' => [
                ['scope' => 'read_only'],
                'readOnly',
            ],
            'read_write' => [
                ['scope' => 'read_write'],
                'readWrite',
            ],
            'redirect_uri' => [
                ['redirect_uri' => 'https://example.com'],
                'redirectUri',
                'https://example.com',
            ],
            'login' => [
                ['stripe_landing' => 'login'],
                'login',
            ],
            'register' => [
                ['stripe_landing' => 'register'],
                'register',
            ],
            'always_prompt' => [
                ['always_prompt' => 'true'],
                'alwaysPrompt',
            ],
            'user' => [
                ['stripe_user' => ['email' => 'bob@example.com']],
                'user',
                ['email' => 'bob@example.com'],
            ],
            'stripe_user' => [
                ['stripe_user' => ['email' => 'bob@example.com']],
                'stripeUser',
                ['email' => 'bob@example.com', 'foo' => null],
            ],
        ];
    }

    /**
     * @param array $expected
     * @param string $method
     * @param mixed|null $value
     * @dataProvider valueProvider
     */
    public function testStandard(array $expected, $method, $value = null)
    {
        $args = !is_null($value) ? [$value] : [];
        $result = call_user_func_array([$this->url, $method], $args);

        $this->assertSame($this->url, $result, "{$method} is fluent");
        $this->assertUrl('https://connect.stripe.com/oauth/authorize', $expected, "{$method}");
    }

    /**
     * @param array $expected
     * @param string $method
     * @param mixed|null $value
     * @dataProvider valueProvider
     */
    public function testExpress(array $expected, $method, $value = null)
    {
        $this->assertSame($this->url, $this->url->express(), 'express is fluent');

        $args = !is_null($value) ? [$value] : [];
        $result = call_user_func_array([$this->url, $method], $args);

        $this->assertSame($this->url, $result, "{$method} is fluent");
        $this->assertUrl('https://connect.stripe.com/express/oauth/authorize', $expected, "{$method}");
    }

    /**
     * @param string $uri
     * @param array $params
     * @param string $message
     * @return void
     */
    private function assertUrl($uri, array $params, $message = '')
    {
        $params = array_replace([
            'state' => 'state_secret',
            'response_type' => 'code',
        ], $params);

        ksort($params);

        $params['client_id'] = 'my_client_id';

        $expected = $uri . '?' . Util::encodeParameters($params);

        $this->assertSame($expected, (string) $this->url, $message);
    }
}
