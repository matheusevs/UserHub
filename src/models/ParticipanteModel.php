<?php

require_once(RELATIVE_PATH . '/ConnectDb.php');

class ParticipanteModel{

    public $connect;

    public function __construct()
    {

        $con = new ConnectDb();
        $this->connect = $con->connection();

    }

    public function createParticipante($data){

        $sql = "
            INSERT INTO participantes(nome, consumo) VALUES ('{$data['nomeText']}', {$data['consumoNumber']});
        ";

        $participanteCreate = mysqli_query($this->connect, $sql);
        if(!$participanteCreate){
            return ['error' => mysqli_error($this->connect)];
        }
        
        return $participanteCreate;

    }

    public function getParticipantes(){

        $arrayParticipantes = [];

        $sql = "SELECT * FROM participantes;";
        
        $getParticipantes = mysqli_query($this->connect, $sql);
        if(!$getParticipantes){
            return ['error' => mysqli_error($this->connect)];
        }

        if($getParticipantes->num_rows == 0){
            return ['error' => 'Não foi encontrado nenhum participante.'];
        }

        while($row = mysqli_fetch_array($getParticipantes, MYSQLI_ASSOC)){
      
            array_push($arrayParticipantes, $row);
        
        }

        return $arrayParticipantes;

    }

    public function getParticipanteById($id){

        $sql = "SELECT * FROM participantes WHERE id = {$id};";

        $buscaParticipante = mysqli_query($this->connect, $sql);
        if(!$buscaParticipante){
            return ['error' => mysqli_error($this->connect)];
        }
        if($buscaParticipante->num_rows == 0){
            return ['error' => 'Não foi identificado nenhum participante com o id informado'];
        }

        return mysqli_fetch_array($buscaParticipante, MYSQLI_ASSOC);

    }

    public function updateParticipante($id, $data){

        $sql = "
            UPDATE participantes SET nome = '{$data['nomeTextEdit']}', consumo = {$data['consumoNumberEdit']}, hora_update = '{$data['hora_update']}' WHERE id = {$id};
        ";

        $participanteUpdate = mysqli_query($this->connect, $sql);
        if(!$participanteUpdate){
            return ['error' => mysqli_error($this->connect)];
        }
        
        return $participanteUpdate;

    }

    public function deleteParticipantesById($id){

        $sql = "DELETE FROM participantes WHERE id = {$id};";

        $participanteDelete = mysqli_query($this->connect, $sql);
        if(!$participanteDelete){
            return ['error' => mysqli_error($this->connect)];
        }
        
        return $participanteDelete;

    }

}