<?php

namespace App\Models;

use App\Config\ResponseHttp;
use App\Config\Security;
use App\DB\ConnectionDB;
use App\DB\Sql;

class UserModel extends ConnectionDB
{

    private static string $nombre;
    private static string $dni;
    private static string $correo;
    private static int $rol;
    private static string $password;
    private static string $IDToken;
    private static string $fecha;


    public function __construct(array $data)
    {
        self::$nombre = $data['name'];
        self::$dni = $data['dni'];
        self::$correo = $data['email'];
        self::$rol = $data['rol'];
        self::$password = $data['password'];
    }

    final public static function getName()
    {
        return self::$nombre;
    }

    final public static function getDni()
    {
        return self::$dni;
    }

    final public static function getCorreo()
    {
        return self::$correo;
    }

    final public static function getRol()
    {
        return self::$rol;
    }

    final public static function getPassword()
    {
        return self::$password;
    }

    final public static function getIDToken()
    {

        return self::$IDToken;
    }

    final public static function getDate()
    {
        return self::$fecha;
    }

    final public static function setNombre(string $nombre)
    {
        self::$nombre = $nombre;
    }

    final public static function setDni(int $dni)
    {
        self::$dni = $dni;
    }

    final public static function setCorreo(string $correo)
    {
        self::$correo = $correo;
    }

    final public static function setRol(int $rol)
    {
        self::$rol = $rol;
    }

    final public static function setPassword(string $password)
    {
        self::$password = $password;
    }

    final public static function setIDToken(string $IDToken)
    {
        self::$IDToken = $IDToken;
    }

    final public static function setDate(string $fecha)
    {
        self::$fecha = $fecha;
    }

    ////////////////////////// LOGIN ////////////////////////////////////
    final public static function login()
    { 
         try {
            $con = self::getConnection()->prepare("SELECT * FROM usuario WHERE correo = :correo");

            $con->execute([
                ':correo' => self::getCorreo()
            ]);

            if($con->rowCount() === 0){
               return ResponseHttp::status400('El usuario o la contraseña son incorrectos');
            }else {
                foreach($con as $res){
                    if(Security::validatePassword(self::getPassword(), $res['password']))
                    {
                        $payload = ['IDToken' => $res['IDToken'] ];
                        $token = Security::createTokenJwt(Security::secretKey(), $payload);
                        
                        $data = [
                            'name'   => $res['nombre'],
                            'rol'    => $res['rol'],
                            'token'  => $token
                        ];

                        return ResponseHttp::status200($data);
                        exit;
                    } else {
                        return ResponseHttp::status400('El usuario o contraseña son incorrecto');
                    }
                }
            }
         } catch (\PDOException $e) {
            error_log('UserModel::login --> '. $e);
            return ResponseHttp::status500();
         }

    }

    ////////////////////// REGISTRAR USUARIO ///////////////////////////

    final public static function post()
    {
        if (Sql::exists("SELECT dni FROM usuario WHERE dni = :dni", ":dni", self::getDni())) {
     
            return ResponseHttp::status400('El DNI se encuentra registrado');
     
        } else if (Sql::exists("SELECT correo FROM usuario WHERE correo = :correo", ":correo", self::getCorreo())) {
            
            return ResponseHttp::status400('El correo se encuentra registrado');
     
        }else {
            self::setIDToken(hash('sha512',self::getDni().self::getCorreo()));
            self::setDate(date('d-m-y H:i:s'));

            try {
                $con = self::getConnection();
                $query1 = "INSERT INTO usuario (nombre, dni, correo, rol, password,IDToken,fecha) VALUES";
                $query2 = "(:nombre,:dni,:correo,:rol,:password,:IDToken,:fecha)";

                $query = $con->prepare($query1 . $query2);

                $query->execute([
                    ':nombre' => self::getName(),
                    ':dni' => self::getDni(),
                    ':correo' => self::getCorreo(),
                    ':rol' => self::getRol(),
                    ':password' => Security::createPassword(self::getPassword()),
                    ':IDToken' => self::getIDToken(),
                    ':fecha' => self::getDate()
                ]);

                if($query->rowCount() > 0){
                    return ResponseHttp::status200('El usuario se registro con exito');
                }else {
                    return ResponseHttp::status500('No se puede registrar el usuario');
                }
            } catch (\PDOException $e) {
                error_log('UserModel::post --> '. $e);
                die(json_encode(ResponseHttp::status500()));
            }
        }

    }

    final public static function getAll(){
        try {
            $con = self::getConnection();
            $query = $con->prepare("SELECT * FROM usuario");
            $query->execute();
            $rs['data'] = $query->fetchAll(\PDO::FETCH_ASSOC);
            return $rs;
        } catch (\PDOException $e) {
            error_log("UserModel::getAll -> ". $e);
            die(json_encode(ResponseHttp::status500('No se puede obtener los datos')));
        }
    }
}
