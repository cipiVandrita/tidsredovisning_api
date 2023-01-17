<?php

declare (strict_types=1);

function connectdb():PDO{
    //koppla mot databasen
    $dsn='mysql:dbname=tidsrapport;host=localhost';
    $dbUser = 'root';
    $dbPassword ="";
    $db = new PDO($dsn, $dbUser, $dbPassword);
    
    return $db;
}

