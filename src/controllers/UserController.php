<?php

    require_once(RELATIVE_PATH . '/src/models/UserModel.php');

    class UserController
    {

        public $userModel;

        public function __construct()
        {

            $this->userModel = new UserModel();
            
        }

        public function insertUser($body, $type = null, $token = null){

            $validateFields = $this->validateFields($body, $type);
            if($validateFields['error']){
                return $validateFields;
            }

            $userModel = new UserModel();

            $findUser = $userModel->findUser($body['email']);
            if($findUser['error']){
                return $findUser;
            }

            if(!$userModel->insert($body)){
                return ['error' => 'Não foi possível criar o novo usuário.'];
            }

            if($token){
                $objUser = $this->convertToken($token);
                if(!$this->userModel->registerLogUser($objUser->id, "cadastrarUsuario", "Usuário {$body['email']} criado. IP: {$_SERVER['REMOTE_ADDR']}")){
                    return ['message' => 'Usuário criado com sucesso, contudo, ocorreu um erro na criação do log.'];
                }
            }

            return ['message' => 'Usuário criado com sucesso'];

        }

        public function updateUser($body, $id, $type = null, $token = null, $updateMyUser = null){

            $validateFields = $this->validateFields($body, $type);

            if($validateFields['error']){
                return $validateFields;
            }

            if($updateMyUser){
                $objUser = $this->convertToken($token);
                if($id != $objUser->id){
                    return ['error' => 'Você não pode atualizar outro usuário!!!!'];
                }
            }

            $set = $this->mountSet($validateFields);

            if(!$this->userModel->update($set, $id)){
                return ['error' => 'Não foi possível editar o usuário.'];
            }

            if($token){
                $objUser = $this->convertToken($token);
                if(!$this->userModel->registerLogUser($objUser->id, "editarUsuario", "Usuário {$body['email']} atualizado. IP: {$_SERVER['REMOTE_ADDR']}")){
                    return ['message' => 'Usuário atualizado com sucesso, contudo, ocorreu um erro na criação do log.'];
                }
            }

            return ['message' => 'Usuário atualizado com sucesso.'];

        }

        public function deleteUser($id, $token, $isAdmin = null, $deleteMyUser = null){

            if(empty($id)){
                return ['error' => 'Id não informado'];
            }

            $getUserById = $this->getUserById($id, $token, $isAdmin);
            if($getUserById['error']){
                return $getUserById;
            }

            $objUser = $this->convertToken($token);
            if($deleteMyUser){
                
                if($id != $objUser->id){
                    return ['error' => 'Você não pode deletar outro usuário!!!!'];
                }

                if($getUserById['roles'] == 'admin'){
                    return ['error' => 'Você é um usuário administrador, não é possível realizar a exclusão via interface.'];
                }

            } else {

                if($id == $objUser->id){
                    return ['error' => 'Você não pode estar logado para apagar seu usuário!'];
                }

            }

            if(!$this->userModel->deleteUserLogs($id)){
                return ['error' => 'Não foi possível deletar os logs do usuário.'];
            }
    
            if(!$this->userModel->delete($id)){
                return ['error' => 'Não foi possível deletar o usuário.'];
            }

            if($token){
                $objUser = $this->convertToken($token);
                if(!$this->userModel->registerLogUser($objUser->id, "deletarUsuario", "Usuário {$getUserById['email']} deletado. IP: {$_SERVER['REMOTE_ADDR']}")){
                    return ['message' => 'Usuário atualizado com sucesso, contudo, ocorreu um erro na criação do log.'];
                }
            }

            return ['message' => 'Usuário deletado com sucesso.'];

        }

        public function updatePassword($id, $token, $passwords){

            if(empty($id)){
                return ['error' => 'Id não informado'];
            }

            $objUser = $this->convertToken($token);
            if($id != $objUser->id){
                return ['error' => 'Você não pode alterar a senha de outro usuário!!!!'];
            }

            $getUserById = $this->getUserById($id, $token);
            if($getUserById['error']){
                return $getUserById;
            }

            if(sha1($passwords['passwordCurrent']) != $objUser->password){
                return ['error' => 'A senha atual informada está incorreta.'];
            }

            if($passwords['passwordNewConfirm'] != $passwords['passwordNew']){
                return ['error' => 'Senhas não correspondem'];
            }

            if(!$this->userModel->updatePassword($id, $passwords['passwordCurrent'], $passwords['passwordNewConfirm'])){
                return ['error' => 'Não foi possível alterar sua senha.'];
            }

            if($token){
                $objUser = $this->convertToken($token);
                if(!$this->userModel->registerLogUser($objUser->id, "editarSenha", "Usuário {$getUserById['email']} alterou sua senha. IP: {$_SERVER['REMOTE_ADDR']}")){
                    return ['message' => 'Usuário atualizado com sucesso, contudo, ocorreu um erro na criação do log.'];
                }
            }

            return ['message' => 'Senha alterada com sucesso.'];

        }

        public function getUsers(){

            return $this->userModel->getUsers();

        }

        public function getLogsUsers(){
            
            return $this->userModel->getLogsUsers();

        }

        public function getUserById($id, $token = null, $isAdmin = false){

            if(empty($id) || !is_numeric($id) || intval($id) <= 0){
                return ['error' => 'O ID informado é inválido.'];
    	    }

            $objUser = $this->convertToken($token);
            if(!$isAdmin && $id != $objUser->id){
                return ['error' => 'Você não pode buscar os dados de outro usuário!!!!'];
            }
            
            $findUser = $this->userModel->findUserById($id);
            return $findUser;

        }

        public function mountSet($set){

            $array = array();

            $set['hora_update'] = date('Y-m-d H:i:s');
            
            foreach ($set as $key => $value) {
                $array[] = "$key = '$value'";
            }
            
            $stringSet = implode(',', $array);
            return $stringSet;

        }

        public function convertToken($token){

            $user = base64_decode($token, true);
            $user = unserialize($user);
            return json_decode(json_encode($user));

        }

        public function validateToken($token, $haveAdmin = null){
            
            if(empty($token)){
                return ['error' => 'Não foi recebido nenhum token.'];
            }

            $objUser = $this->convertToken($token);
            $userLogged = $this->userModel->validateToken($objUser, $haveAdmin);

            return $userLogged;
        }

        public function validateFields($body, $type = null){

            if($type == 'updateAdmin'){

                if(empty($body)){
                    return ['error' => 'O corpo da requisição não pode estar vazio.'];
                }

                if(!empty($body['id'])){
                    unset($body['id']);
                }

                if(empty($body['name'])){
                    unset($body['name']);
                }
    
                if(empty($body['email'])){
                    unset($body['email']);
                } else {
                    if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
                        return ['error' => 'O campo de email não está em um formato válido.'];
                    }
                }

                if(empty($body['roles'])){
                    unset($body['roles']);
                }
    
                return $body;

            } else if($type == 'updateMyUser'){

                if(empty($body)){
                    return ['error' => 'O corpo da requisição não pode estar vazio.'];
                }

                if(!empty($body['id'])){
                    unset($body['id']);
                }

                if(empty($body['name'])){
                    unset($body['name']);
                }

                if(empty($body['email'])){
                    unset($body['email']);
                } else {
                    if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
                        return ['error' => 'O campo de email não está em um formato válido.'];
                    }
                }

                return $body;

            } else {
                if(empty($body)){
                    return ['error' => 'O corpo da requisição não pode estar vazio.'];
                }
    
                if(!isset($body['email']) || empty($body['email'])){
                    return ['error' => 'O campo de email é obrigatório.'];
                }
    
                if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
                    return ['error' => 'O campo de email não está em um formato válido.'];
                }
    
                if(!isset($body['nome']) || empty($body['nome'])){
                    return ['error' => 'O campo de nome é obrigatório.'];
                }
    
                if(!isset($body['senha']) || empty($body['senha'])){
                    return ['error' => 'O campo de senha é obrigatório.'];
                }
    
                if($type != 'createAdmin'){
                    if(!isset($body['confirmar_senha']) || empty($body['confirmar_senha'])){
                        return ['error' => 'O campo de confirmar senha é obrigatório.'];
                    }
        
                    if($body['senha'] != $body['confirmar_senha']){
                        return ['error' => 'Senhas não correspondem'];
                    }
                }
            }
        }
    }