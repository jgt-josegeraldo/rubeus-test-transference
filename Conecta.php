<?php
namespace Rubeus\Bd;

class Conecta {
    private $host;
    private $user;
    private $passwd;
    private $db;
    private $objPdo;
    private $argumentos = array();
    private $args = array();
    private $exec;
    private $trans;
    private $erro;
    private $lockAtivo;
    
    public function __construct($base=false) {
        $this->iniciar($base); 
        $this->trans = false;
        $this->erro = array();
    }
   
    public function iniciar($base){
        $this->host = $base->host;
        $this->user = $base->user;
        $this->passwd = $base->password;
        $this->db = $base->base;
        
        if(isset($base->charset)){
            $this->charset = $base->charset;
        }else{
            $this->charset = 'charset=UTF8';
        }
        if(isset($base->sgbd)){
            $this->sgbd = $base->sgbd; 
        }else{
            $this->sgbd = '';
        }
        if(isset($base->utf8)){
            $this->uft8 = $base->utf8;
        }else{
            $this->uft8 = '';
        }
    }
   
    public function setLockAtivo($lockAtivo) {
        $this->lockAtivo = $lockAtivo;
        if(!$lockAtivo){
            $this->trans = false;
        }
    }
 
    public function getDb() {
        return $this->db;
    }
    
    public function getLockAtivo() {
        return $this->lockAtivo;
    }
    
    private function resultadoUtf8($dados){
        if(is_array($dados) || is_object($dados)){
            foreach($dados as $i=>$r){
                if(is_array($r) || is_object($r)){
                    $dados[$i] = $this->resultadoUtf8($r);
                }else{
                    $dados[$i] = utf8_encode($r);
                }
            }
        }else{
            $dados = utf8_encode($r);
        }
        return $dados;
    }

    public function getResultado() {
        if($this->uft8 == 1){
            return $this->resultadoUtf8($this->exec->fetchAll(\PDO::FETCH_ASSOC));
        }
        return $this->exec->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function conectaPDO() {
        try {
            switch ($this->sgbd){
                case 'sqlServer':
                    $this->objPdo = new \PDO ("dblib:host=".$this->host.";dbname=".$this->db.";",
                                            $this->user,$this->passwd);
                    break;     
                case 'GoogleCould':
                        $this->objPdo = new \PDO($this->host,
                                           $this->user,
                                           $this->passwd
                                       );
                        break;
                default :
                    $this->objPdo = new \PDO("mysql:host=" . $this->host . ";dbname=" . $this->db.";".$this->charset, 
                                       $this->user, 
                                       $this->passwd,array(\PDO::ATTR_PERSISTENT => false)
                                   );
            }
             
        } catch (PDOException $e) {
            return false;
        }
        return true;
    }
    
    private function execultar($queryPrepare){
        if(!$this->objPdo->inTransaction()){
            $this->objPdo->beginTransaction();
            $this->trans = $this->objPdo->inTransaction(); 
        }
        if(empty($queryPrepare)){
            return false;
        }
        
        if(is_array($queryPrepare)) {
            foreach ($queryPrepare as $query){ 
                $this->exec = $this->objPdo->prepare($query);
            }
        }else {
            $this->exec = $this->objPdo->prepare($queryPrepare);
        }
        $this->exec->execute($this->args);
        if (0 <> $this->exec->errorCode()){
            $this->erro[] = $this->exec->errorInfo();
        }else{ 
            return $this->objPdo->lastInsertId();  
        }
        return false;
    }
    
    public function executePDO($queryPrepare) {
        try {
           $id = $this->execultar($queryPrepare);
        }catch (PDOException $e) {
            $this->erro[] = $e;
            $id = false;
        }
        return $id;
    }
    
    public function commit(){
        if($this->trans){
            if($this->getLockAtivo()){
                $this->setLockAtivo(false);
                $sql = " UNLOCK TABLES;";            
                $this->executePDO( $sql, false);            
            }
            if($this->objPdo->inTransaction()){
                $this->objPdo->commit();
            }
            $this->trans = $this->objPdo->inTransaction();
        }    
    }

    public function rollBack(){
        if($this->trans){
            if($this->getLockAtivo()){
                $this->setLockAtivo(false);
                $sql = " UNLOCK TABLES;";            
                $this->executePDO( $sql, false);
            }
            if($this->objPdo->inTransaction()){
                $this->objPdo->rollBack();  
            }
            $this->trans = $this->objPdo->inTransaction();
        }
    }
    
    public function setString($value) {
        if($value !== false){
            $value = addslashes($value);
            $this->args[] = $value;
        }else {
            $this->args = array();
        }
        return $value;
    }

     public function setVariavel($value) {
        return addslashes($value);
    }

    public function limparArgumento(){
        $this->args = array();
        return false;
    }
    
    public function setNumber($value) {
        if($value !== false){
            if ($value === null) {
                $this->argumentos[$this->id++] = "null";
                return;
            }
            if (!is_numeric($value)){ 
                return false;
            }
            $this->args[] = $value;
        }else{
            $this->args = array();
        }
    }

}
