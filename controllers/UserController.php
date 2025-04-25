<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';

class UserController {
    private $model;

    public function __construct() {
        $this->model = new User();
    }

    public function getAll() {
        $users = $this->model->getAll();
        Response::json($users);
    }

    public function get($id) {
        $user = $this->model->getById($id);
        Response::json($user);
    }

    public function create($data) {
        $success = $this->model->create($data['name'], $data['email']);
        Response::json(['success' => $success]);
    }

    public function update($id, $data) {
        $success = $this->model->update($id, $data['name'], $data['email']);
        Response::json(['success' => $success]);
    }

    public function delete($id) {
        $success = $this->model->delete($id);
        Response::json(['success' => $success]);
    }
}
