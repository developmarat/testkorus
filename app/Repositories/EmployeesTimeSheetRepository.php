<?php
/**
 * Created by PhpStorm.
 * User: Marat
 * Date: 14.11.2018
 * Time: 20:27
 */

namespace App\Repositories;


use App\Env;

/**
 * Репозиторий получения данных для отчета по сотрудникам за отчетный период
 * Class EmployeesTimeSheetRepository
 * @package App\Repositories
 */
class EmployeesTimeSheetRepository
{
    protected $db = null;

    /**
     * EmployeesTimeSheetRepository constructor.
     */
    public function __construct()
    {
        $this->db = $this->getMysqli();
    }

    /**
     * Получение данных за отчетный период
     * @param string $beginDate
     * @param string $endDate
     * @return array
     */
    public function getByInterval(string $beginDate, string $endDate):array
    {
        $timeSheetItems = $this->getTimeSheetItemsByInterval($beginDate, $endDate);
        if(isset($timeSheetItems) && !empty($timeSheetItems)){
            $timeSheetItems = $this->getTimeSheetItemsWithCalculateTimeInSeconds($timeSheetItems);
            $employees = $this->getEmployees();
            return $this->getEmployeesTimeSheet($employees, $timeSheetItems);
        }

        return [];
    }

    /**
     * Извлечение данных по времени работы за отчетный период
     * @param string $beginDate
     * @param string $endDate
     * @return array|null
     */
    protected function getTimeSheetItemsByInterval(string $beginDate, string $endDate):?array
    {
        $stmt = $this->db->prepare("SELECT * FROM time_sheet WHERE Date >= ? and Date <= ?");
        $stmt->bind_param("ss", $beginDate, $endDate);

        if($stmt->execute()){
            $result = $stmt->get_result();
            $timeSheetItems = [];
            while ($item = $result->fetch_assoc()) {
                $timeSheetItems []= $item;
            }
            $stmt->close();
            return $timeSheetItems;
        }
        else{
            $stmt->close();
            return null;
        }
    }

    /**
     * Извлечение данных всех сотрудников (описывающие данные)
     * @return array|null
     */
    protected function getEmployees():?array
    {
        $result = $this->db->query("SELECT * FROM employees ORDER BY Id");
        if($result){
            $employees = [];
            while ($item = $result->fetch_assoc()) {
                $employees []= $item;
            }
            return $employees;
        }
        else{
            return null;
        }
    }


    /**
     * Получение данных о всех сотрудниках и их времени работы за отчетный период
     * @param array $employees
     * @param array $timeSheetItems
     * @return array
     */
    protected function getEmployeesTimeSheet(array $employees, array $timeSheetItems):array
    {
        $employeesTimeSheet = [];

        $timeSheetItemsByEmployeeId = $this->getTimeSheetItemsArrayByEmployeeId($timeSheetItems);

        foreach ($employees as $employee){
            $employeeId = $employee["Id"];
            $employee ["TimeSheet"] = $timeSheetItemsByEmployeeId[ $employeeId ] ?? [];
            $employeesTimeSheet [ $employeeId ] = $employee;
        }

        return $employeesTimeSheet;
    }

    /**
     * Распределение времени работы по сотрудникам
     * @param array $timeSheetItems
     * @return array
     */
    protected function getTimeSheetItemsArrayByEmployeeId(array $timeSheetItems):array
    {
        $employeeTimeSheetItemsByEmployeeId = [];
        foreach ($timeSheetItems as $item){
            $employeeId = $item["EmployeeId"];
            if(!isset($employeeTimeSheetItemsByEmployeeId[$employeeId])){
                $employeeTimeSheetItemsByEmployeeId[$employeeId] = [];
            }

            $employeeTimeSheetItemsByEmployeeId[$employeeId] []= $item;
        }

        return $employeeTimeSheetItemsByEmployeeId;
    }


    /**
     * Вычисление времени работы в секундах и возврат исходного массива с указанием вычисленного времени
     * @param array $timeSheetItems
     * @return array
     */
    protected function getTimeSheetItemsWithCalculateTimeInSeconds(array $timeSheetItems):array
    {
        $timeSheetItemsWithCalculateTime = [];
        foreach ($timeSheetItems as $key => $timeSheetItem){
            $timeSheetItem ["TimeSeconds"] = $this->getSecondsByTimeStr($timeSheetItem["Time"]);
            $timeSheetItemsWithCalculateTime [$key] = $timeSheetItem;
        }

        return $timeSheetItemsWithCalculateTime;
    }

    /**
     * Вычисление кол-ва секунд по строковому кол-ву отработанного времени
     * @param null|string $timeStr
     * @return int
     */
    protected function getSecondsByTimeStr(?string $timeStr):int
    {
        if(!isset($timeStr) || empty($timeStr)){
            return 0;
        }

        $timeArray = explode(':', $timeStr);
        if(count($timeArray) >= 3){
            return (60 * 60 * (int)$timeArray[0]) + (60 * (int)$timeArray[1]) + (int)$timeArray[2];
        }
        else{
            return 0;
        }
    }

    /**
     * @return \mysqli
     */
    protected function getMysqli()
    {
        $env = Env::getInstance();
        return new \mysqli($env->get("DB_HOST"), $env->get("DB_USERNAME"), $env->get("DB_PASSWORD"), $env->get("DB_DATABASE"));

    }
}