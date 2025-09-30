<?php
require_once __DIR__ . "/../Models/Unidad.php";

class UnidadController {
    public function api_list() {
        header('Content-Type: application/json; charset=utf-8');
        $programa = $_GET['programa'] ?? '';
        $semestre = $_GET['semestre'] ?? '';
        if (!$programa || !$semestre) {
            echo json_encode([]);
            return;
        }
        $items = Unidad::porProgramaSemestre($programa, $semestre);
        echo json_encode($items);
    }
}
