<?php
namespace Rubeus\Bd;
use Rubeus\Servicos\XML\XML;
use Rubeus\ContenerDependencia\Conteiner;

abstract class Persistencia {
    static private $objConecta;
    static private $preFixo;
    static private $base = "principal";
    static private $config;

    static private $sentencas = array();
    static private $guardar = 0;

    static function setBase($base) {
        self::$base = $base;
    }

    public static function setGuardar($guardar){
        self::$sentencas = [];
        self::$guardar = $guardar;
    }

    public static function setPreFixo($prefixo){
        self::$preFixo = $prefixo;
    }

    public static function getBaseAtual(){
        return self::$base;
    }

    private static function getBase($base='principal'){
        self::$base = $base;
        return self::$config->$base;
    }

    public static function mudarBase($base){
        self::instaciarConecta(false);
        self::$objConecta->commit();
        self::$objConecta->iniciar(self::getBase($base));
        self::$objConecta->conectaPDO();
        return true;
    }

    private static function instaciarConecta($conectar=true){
        if(!isset(self::$objConecta) || is_null(self::$objConecta)){
            self::$config = Conteiner::get('BASE_DADOS');

            self::$objConecta = new Conecta(self::getBase(self::$base));
            if($conectar){
                self::$objConecta->conectaPDO();
            }
        }
    }

    public static function fecharConexao(){
        self::$objConecta = null;
    }
        
    public static function lerXML($pasta,$nomeClasse,$arquivo='mapeamento.xml'){
        $xml = XML::ler($pasta.$arquivo);

        $numClasse = count($xml->classe);
        for ($i = 0; $i < $numClasse; $i++) {
            if (rtrim($xml->classe[$i]['nome']) == rtrim($nomeClasse)){
                return $xml->classe[$i];
            }
        }

        return null;
    }

    public static function commit(){
        if(self::$guardar == 1){
            self::$sentencas[] = array('commit' => true);
        }
        if(isset(self::$objConecta)){
            if(PERMITIR_COMMIT == 1){
                self::$objConecta->commit();
            }
            return true;
        }else{
            return false;
        }
    }

    public static function roolBack(){
        if(self::$guardar == 1) self::$sentencas[] = array('rollBack' => true);
        if(isset(self::$objConecta)){
            self::$objConecta->rollBack();
            return true;
        }else false;
    }


    public static function execultar($sql,$guardarAq=true){
        self::instaciarConecta();
        if($guardarAq)self::guardarSentenca($sql, null);
        return self::$objConecta->executePDO($sql);
    }

    public static function getSentencas(){
        return self::$sentencas;
    }

    private static function guardarSentenca($sql,$parametro){
        if(self::$guardar == 1)
            self::$sentencas[] = array('base'=>self::$objConecta->getDb(),'sentencas'=>$sql,'parametros' => $parametro);
    }

    public static function consultar($arrayParametro, $sql) {
        self::instaciarConecta();
        self::guardarSentenca($sql, $arrayParametro);
        if($arrayParametro !== false && !is_null($arrayParametro)){
            foreach ($arrayParametro as $parametro){
                if(is_string($parametro))  self::$objConecta->setString($parametro);
                else if(is_int($parametro) || is_float($parametro)) self::$objConecta->setNumber($parametro);
                else return self::$objConecta->limparArgumento();
            }
        }
        self::execultar($sql,false);

        $resultado = self::$objConecta->getResultado();
        self::$objConecta->limparArgumento();
        return $resultado;
    }

    public static function limparSenteca($texto){
        $caracteres = array(";", "delete","insert", "update", "\t", "\n", "\r");
        return str_replace($caracteres , " ", $texto);
    }

}
