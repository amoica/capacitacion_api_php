<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';



class AuthController {
    private $model;
    private $secret = 'capacitacionCet302025phppasantias';


    public function __construct(){
        $this->userModel = new User();
    }
    
    //en la data va a venir email y password, datos necesarios para sesión
    public function login($data){
        //Obtenemos el usuario
        //nombre, email, passoword hasheada
        $user = $this->userModel->getByEmail($data['email']);

        //verifico si existe el usuario y si la password es la correcta
        if($user && password_verify($data['password'], $user['password'])){
            $payload = base64_encode(json_encode([
                'id' => $user['id'],
                'email' => $user['email'],
                'timestamp' => time()
            ]));

            $signature = hash_hmac('sha256', $payload, $this->secret);
            $token = $payload . '.' . $signature;

            Response::json(['token' => $token]);
        }else{
            http_response_code(401);
            Response::json([
                'message' => 'Credenciales inválidas'
            ]);
        }
    }

    //devuelve true o false si el token es valido
    public function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 2) return false;
    
        list($payload, $signature) = $parts;
        $validSig = hash_hmac('sha256', $payload, $this->secret);
        if (!hash_equals($validSig, $signature)) return false;
    
        $data = json_decode(base64_decode($payload), true);
        // Expira a la hora
        if ((time() - $data['timestamp']) > 3600) {
            return false;
        }
        return $data;
    }

    public function register($data) {
        // Valida que vengan name, email y password
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            Response::json(['message' => 'Faltan datos obligatorios']);
            return;
        }
    
        // Usa el modelo para crear (hashea el password)
        $created = $this->userModel->create(
            $data['name'],
            $data['email'],
            $data['password']
        );
    
        if ($created) {
            http_response_code(201);
            Response::json(['message' => 'Usuario registrado correctamente']);
        } else {
            http_response_code(500);
            Response::json(['message' => 'Error al registrar usuario']);
        }
    }
}