<?php

namespace App\DB;

use App\Config\ResponseHttp;
use App\DB\ConnectionDB;

class Sql extends ConnectionDB {



    public static function exists(string $request, string $condition, $param)
    {
         try {
            $con = self::getConnection();
            $query = $con->prepare($request);
            $query->execute([
                $condition => $param
            ]);

            $res = ($query->rowCount() == 0) ? false: true;
            return $res;
         } catch (\PDOException $e) {
            error_log('Sql::exist -> '. $e);
            die(json_encode(ResponseHttp::status500()));
         }
    }
}