<?php
/**
 * Created by PhpStorm.
 * User: Marat
 * Date: 14.11.2018
 * Time: 20:26
 */

namespace App\Controllers;


use App\Repositories\EmployeeHoursRepository;
use App\Repositories\EmployeesTimeSheetRepository;
use App\Services\EmployeesTimeSheetService;

/**
 * Контроллер обработки запроса на получении на данных по сотрудникам за отчетный период
 * Class EmployeesTimeSheetController
 * @package App\Controllers
 */
class EmployeesTimeSheetController
{
    protected $service = null;
    protected $response = null;

    /**
     * EmployeesTimeSheetController constructor.
     */
    public function __construct()
    {
        $repository = new EmployeesTimeSheetRepository();
        $this->service = new EmployeesTimeSheetService($repository);
    }

    /**
     * Выполнение запроса
     * @param array $requestData
     * @return array|null
     */
    public function execute(array $requestData)
    {
        $this->response = $this->getDataByInterval($requestData);
        $this->showResponse();
        return $this->response;
    }

    /**
     * Получение данных по отчетному периоду
     * @param array $requestData
     * @return array
     */
    protected function getDataByInterval(array $requestData):array
    {
        if(isset($requestData["beginDate"]) && isset($requestData["endDate"])){
            return $this->service->getByInterval($requestData["beginDate"], $requestData["endDate"]);
        }
        else{
            return [];
        }
    }

    /**
     * Показ ответа
     */
    protected function showResponse()
    {
        http_response_code(200);
        echo json_encode($this->response);
        die();
    }
}