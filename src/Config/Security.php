<?php
 namespace App\Config;

 use Dotenv\Dotenv;
 use Firebase\JWT\JWT;
use Firebase\JWT\Key;

 class Security {

    private static $jwt_data;

    final public static function secretKey()
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__,2));
        $dotenv->load();
        return $_ENV['SECRET_KEY'];
    }

    final public static function createPassword(string $pw)
    {
        $pass = password_hash($pw,PASSWORD_DEFAULT);

        return $pass;
    }

    final public static function validatePassword(string $pw, string $pwhash)
    {
        if (password_verify($pw, $pwhash)) {
            return true;
        } else {
            error_log('La contraseña es incorrecta');
            return false;
        }
    }

    final public static function createTokenJwt(string $key, array $data)
    {
       $payload = array(
          'iat' => time(),
          'exp' => time() + (60*60*6),
          'data' => $data 
       );

       $jwt = JWT::encode($payload,$key,'HS256');

 
       return $jwt;     
    }

    final public static function validateTokenJwt(array $token, string $key)
    {
        //echo $token['Authorization'];

//        var_dump($token);

       if(!isset($token['Authorization']))
       {
          die(json_encode(ResponseHttp::status400("El token de acceso es requerido")));
          exit;
       }
       try {
           $jwt = explode(" ", $token['Authorization']);
           $data = JWT::decode($jwt[1],new Key($key,'HS256'));
           self::$jwt_data = $data;
           return $data;
           exit;
       } catch (\Exception $e) {
           error_log('Token invalido o expirado');
           die(json_encode(ResponseHttp::status401('Token invalido o expirado')));
       }
    }

    final public static function getDataJwt()
    {
        $jwt_decoded_array = json_decode(json_encode(self::$jwt_data), true);
        return $jwt_decoded_array['data'];
        exit; 
    }
 }