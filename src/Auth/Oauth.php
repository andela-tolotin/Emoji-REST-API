<?php
/**
 * @author   Temitope Olotin <temitope.olotin@andela.com>
 * @license  <https://opensource.org/license/MIT> MIT
 */

namespace Laztopaz\EmojiRestfulAPI;

use Laztopaz\EmojiRestfulAPI\User;
use \Firebase\JWT\JWT;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Oauth {

    public function __construct()
    {
        if (! isset($_SESSION)) {
            session_start();
        }
    }

    /**
     * This method authenticate user and log them in if the supplied
     * credentials are valid
     * 
     * @param  array  $loginParams
     * 
     * @return json jwt
     * 
     */
    public function loginUser(Request $request, Response $response)
    {
        $loginParams = $request->getParsedBody();

        if (is_array($loginParams)) {
            $user = User::where('username', '=', $loginParams['username'])->get();
            $user = $user->first();

            $userInfo = ['id' => $user->id, 'username' => $user->username, 'email' => $user->email];

            $_SESSION['userinfo'] = $userInfo;

            if (password_verify($loginParams['password'], $user->password)) {
                $token = $this->buildAcessToken($userInfo);

                return $response->withJson(['status'],200)->withAddedHeader('token', $token)->write($token);
                
            }

            return $response->withJson(['status'],400);
        }

    }

    /**
     *
     * This method logout the user
     *
     * @param $args logout
     *
     * @return $reponse
     */
    public function logoutUser(Request $request, Response $response, $args)
    {
        $token = $this->buildAcessToken($_SESSION['userinfo']);

        unset($_SESSION['userinfo']);
        session_destroy();

        return $response->withJson(['status'],200);

    }

    /**
     *
     * This method serves as middleware that authenticate users
     *
     * @param $request
     *
     * @param json $response
     */
    public function middleware(Request $request, Response $response)
    { 
        $authHeader = $request->getHeader('token');

        $loadEnv = DatabaseConnection::loadEnv();

        $secretKey = base64_decode(getenv('secret'));

        //decode the JWT using the key from config
        //$decodedToken = JWT::decode($jwt, $secretKey, array('HS512'));

        return json_encode($authHeader);

    }

    /**
     * 
     * This method builds an access token for a login user;
     *
     * @param $userData
     *
     * @return string $token
     * 
     */
    public function buildAcessToken(array $userData)
    {
        $tokenId    = base64_encode(mcrypt_create_iv(32));
        $issuedAt   = time();
        $notBefore  = $issuedAt + 10;  //Adding 10 seconds
        $expire     = $notBefore + (float) strtotime('+30 days'); // Adding 30 days expiry date
        $serverName = $_SERVER['HTTP_HOST']; // Retrieve the server name

        /**
         *
         * Create the token params as an array 
         */
        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss'  => $serverName,       // Issuer
            'nbf'  => $notBefore,        // Not before
            'exp'  => $expire,           // Expire
            $userData                    // User Information retrieved from the database
        ];

        $loadEnv = DatabaseConnection::loadEnv();

        $secretKey = base64_decode(getenv('secret'));

        $jwt = JWT::encode(
        $data,      //Data to be encoded in the JWT
        $secretKey, // The signing key
        'HS512'     // Algorithm used to sign the token
        );
        $unencodedArray = ['jwt' => $jwt];

        return json_encode($unencodedArray);
    
    }

}