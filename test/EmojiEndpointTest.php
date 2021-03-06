<?php
/**
 * @author   Temitope Olotin <temitope.olotin@andela.com>
 * @license  <https://opensource.org/license/MIT> MIT
 */
namespace Laztopaz\EmojiRestfulAPI\Test;

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Laztopaz\EmojiRestfulAPI\DatabaseConnection;
use Laztopaz\EmojiRestfulAPI\Emoji;
use Laztopaz\EmojiRestfulAPI\EmojiController;
use Laztopaz\EmojiRestfulAPI\Oauth;
use Laztopaz\EmojiRestfulAPI\Schema;
use Laztopaz\EmojiRestfulAPI\SlimRouteApp;
use Laztopaz\EmojiRestfulAPI\UploadTableInfo;
use PHPUnit_Framework_TestCase;
use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Request;

class EmojiEndpointTest extends PHPUnit_Framework_TestCase
{
    protected $app;
    protected $response;
    protected $emojis;

    public function setUp()
    {
        $capsule = new Capsule();
        new DatabaseConnection($capsule);

        new Schema();
        new UploadTableInfo();

        $auth = new Oauth();
        $emoji = new EmojiController($auth);

        $app = new SlimRouteApp($auth, $emoji);
        $this->app = $app->setUpSlimApp();
    }

    public function request($method, $path, $options = [])
    {
        // Prepare a mock environment
         $env = Environment::mock(array_merge([
            'REQUEST_METHOD' => $method,
            'PATH_INFO'      => $path,
            'CONTENT_TYPE'   => 'application/json',

            'SERVER_NAME' => 'slim-test.dev',
            ], $options));

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $this->response = $this->app->run(true);
    }

    /**
     * This method defines a get request for all emojis endpoint.
     *
     * @param  $path
     * @param  $options
     *
     * @return $request
     */
    public function get($path, $options = [])
    {
        $this->request('GET', $path, $options);
    }

    /**
     * @param  $path
     * @param  $options
     *
     * @return $request
     */
    public function post($path, $options = [])
    {
        $this->request('POST', $path, $options);
    }

    /**
     * This method ascertain that emoji index page return status code 404.
     *
     * @param  void
     *
     * @return booleaan true
     */
    public function testPostIndex()
    {
        $this->post('/', ['ACCEPT' => 'application/json']);
        $this->assertEquals('404', $this->response->getStatusCode());
    }

    /**
     * This method ascertain that emoji index page return status code 404.
     *
     * @param  void
     *
     * @return booleaan true
     */
    public function testIndex()
    {
        $this->get('/', ['ACCEPT' => 'application/json']);
        $this->assertEquals('200', $this->response->getStatusCode());
    }

    public function testuserLogin()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/login',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/auth',
        ]);

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testCreateUser()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/register',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
            'PATH_INFO'      => '/auth',
            ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'firstname'  => 'Kuti',
            'lastname'   => 'Gbolahan',
            'username'   => 'kuti',
            'password'   => 'gamik2k16',
            'email'      => 'gbolahan.kuti@andela.com',
            'created_at' => date('Y-m-d h:i:s'),
            'updated_at' => date('Y-m-d h:i:s'),
        ]);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 201);
    }

    public function testPostEmoji()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'POST',
            'REQUEST_URI'        => '/emojis',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
        ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'BONNY FACE',
                    'char'       => '/u{1F608}',
                    'created_at' => date('Y-m-d h:i:s'),
                    'category'   => 1,
                    'created_by' => 1,
                    'keywords'   => 'face, grin, person, eye',
                ]);

        $this->app->getContainer()['request'] = $req;

        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 201);
    }

    public function testThatCorrectLoginCredentialWhereUsedToLogin()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/login',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
            'PATH_INFO'      => '/auth',
        ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'username' => 'laztopaz',
            'password' => 'tope0852',
        ]);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        //$this->setToken($data['jwt']);

        $this->assertArrayHasKey('jwt', $data);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testThatInCorrectLoginCredentialWhereUsedToLogin()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/login',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
            'PATH_INFO'      => '/auth',
        ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'username' => 'xxxx',
            'password' => 'xxxxxxxx',
        ]);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $this->assertSame($response->getStatusCode(), 400);
    }

    private function getCurrentToken()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/login',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
            'PATH_INFO'      => '/auth',
            ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'username' => 'laztopaz',
            'password' => 'tope0852',
        ]);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);

        return $data['jwt'];
    }

    public function testgetAllEmojis()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/emojis',
            ]);

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testGetSingleEmoji()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis/1',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/emojis',
            ]);

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testGetSingleEmojiNotExist()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis/11111',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/emojis',
            ]);

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 404);
    }

    public function testEditEmojiWithPut()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PUT',
            'REQUEST_URI'        => '/emojis/1',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
        ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'KISSING FACE',
                    'char'       => '/u{1F603}',
                    'created_at' => date('Y-m-d h:i:s'),
                    'category'   => 1,
                ]);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testEditEmojiWithPutWithInvalidID()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PUT',
            'REQUEST_URI'        => '/emojis/111111',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
        ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'KISSING FACE',
                    'char'       => '/u{1F603}',
                    'created_at' => date('Y-m-d h:i:s'),
                    'category'   => 1,
                ]);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 404);
    }

    public function testEditEmojiPartially()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PATCH',
            'REQUEST_URI'        => '/emojis/1',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'WINKING FACE',
                ]);

        $this->app->getContainer()['request'] = $req;

        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);

        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testEditEmojiPartiallyWithInvalidID()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PATCH',
            'REQUEST_URI'        => '/emojis/1222222',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'WINKING FACE',
                ]);

        $this->app->getContainer()['request'] = $req;

        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);

        $this->assertSame($response->getStatusCode(), 404);
    }

    public function testGetSingleEmojiReturnsEmojiWithStatusCode200()
    {
        $emoji = Emoji::get()->first();

        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis/'.$emoji->id,
            'PATH_INFO'      => '/emojis',
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);

        $this->assertSame($response->getStatusCode(), 200);
        $this->assertSame($data[0]['id'], $emoji->id);
        $this->assertSame($data[0]['name'], $emoji->name);
    }

    public function testGetAllEmojiReturnEmojisWithStatusCode200()
    {
        $emoji = Emoji::get();

        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis',
            'PATH_INFO'      => '/emojis',
        ]);

        $req = Request::createFromEnvironment($env);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testDeleteEmoji()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'DELETE',
            'REQUEST_URI'        => '/emojis/1',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);

        $req = Request::createFromEnvironment($env);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testuserLogoutWithToken()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/auth/logout',
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testuserWantToLogoutWithoutCorrectQueryParams()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/auth/signout',
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 404);
    }

    public function testuserLogoutWithoutToken()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/auth/logout',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/auth',
            ]);

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);

        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 401);
    }
}
