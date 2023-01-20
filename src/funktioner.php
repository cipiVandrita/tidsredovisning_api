<?php

declare (strict_types=1);
    function connectDb():PDO{
        static $db=null;


            if($db===null){
                //koppla mot databasen
                $dsn='mysql:dbname=tidsrapport;host=localhost';
                $dbUser = 'root';
                $dbPassword ="";
                $db = new PDO($dsn, $dbUser, $dbPassword);
            }
        

    return $db;
        }
    


