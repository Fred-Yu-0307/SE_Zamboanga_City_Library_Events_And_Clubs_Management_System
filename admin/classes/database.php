<?php

class Database{
    private $host = 'localhost';
    private $username = 'u948427697_zclibrary';
    private $password = 'ZCLibrary12345678';
    private $database = 'u948427697_dbzambocitylib';
    protected $connection;

    function connect(){
        try 
        {
            $this->connection = new PDO("mysql:host=$this->host;dbname=$this->database", 
                                        $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } 
        catch (PDOException $e) 
        {
            echo "Connection error " . $e->getMessage();
        }
        return $this->connection;
    }

    function prepare($query){
        return $this->connection->prepare($query);
    }
}


?>