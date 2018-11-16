<?php
/**
 * Created by PhpStorm.
 * User: Marat
 * Date: 15.11.2018
 * Time: 11:44
 */

namespace App\Services;


use App\Repositories\EmployeesTimeSheetRepository;

/**
 * Сервис преобразования данных о времени работы сотрудников для отчетного периода, полученных из репозитория
 * Class EmployeesTimeSheetService
 * @package App\Services
 */
class EmployeesTimeSheetService
{
    const WORK_DAY_TIME_IN_SECOND = 28800;//8 часов

    protected $repository = null;

    /**
     * EmployeesTimeSheetService constructor.
     * @param EmployeesTimeSheetRepository $repository
     */
    public function __construct(EmployeesTimeSheetRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Получение данных за отчетный период
     * @param string $beginDate
     * @param string $endDate
     * @return array
     */
    public function getByInterval(string $beginDate, string $endDate):array
    {
        $employeesTimeSheet = $this->repository->getByInterval($beginDate, $endDate);
        if(!empty($employeesTimeSheet)){
            $employeesTimeSheet = $this->checkEmailValid($employeesTimeSheet);
            $employeesTimeSheet = $this->checkDaysTime($employeesTimeSheet);
            $employeesTimeSheet = $this->convertToHierarchyStructure($employeesTimeSheet);
            $employeesTimeSheet = $this->calculateTotalTimes($employeesTimeSheet);

            return $employeesTimeSheet;
        }
        else{
            return [];
        }

    }

    /**
     * Проверка валидности поля E-mail
     * @param array $employees
     * @return array
     */
    protected function checkEmailValid(array $employees):array
    {
        $fieldCode = "EmailIsValid";
        foreach ($employees as &$employee){
            if(isset($employee["Email"]) && filter_var($employee["Email"], FILTER_VALIDATE_EMAIL)){
                $employee[ $fieldCode ] = true;
            }
            else{
                $employee[ $fieldCode ] = false;
            }
        }

        return $employees;
    }

    /**
     * Проверка времени работы за день
     * @param array $employeesTimeSheet
     * @return array
     */
    protected function checkDaysTime(array $employeesTimeSheet):array
    {
        $employeesTimeSheetWithCheckDateTime = [];
        foreach ($employeesTimeSheet as $key => $employee){
            $employee ["flawDays"] = $this->getFlawDays($employee);
            $employee ["flawDaysStr"] = $this->getFlawDaysStr($employee["flawDays"]);
            $employeesTimeSheetWithCheckDateTime [$key] = $employee;
        }

        return $employeesTimeSheetWithCheckDateTime;
    }

    /**
     * Получение дней с недоработками
     * @param array $employee
     * @return array
     */
    protected function getFlawDays(array $employee):array
    {
        $flawDays = [];
        foreach ($employee["TimeSheet"] as $timeSheetItem){
            if($timeSheetItem["TimeSeconds"] < self::WORK_DAY_TIME_IN_SECOND){
                $flawDays []= $timeSheetItem;
            }
        }

        return $flawDays;
    }

    /**
     * Получение строкового представления дней с недоработками
     * @param array $flawDays
     * @return string
     */
    protected function getFlawDaysStr(array $flawDays):string
    {
        $flawDaysStr = '';
        foreach ($flawDays as $day){
            $flawDaysStr .= ($flawDaysStr? ', ': '') . $day["Date"] . ' ('.$day["Time"].')';
        }

        return $flawDaysStr;
    }

    /**
     * Преобразование массива сотрудников в иерархичную структуру
     * @param array $employeesTimeSheet
     * @return array
     */
    protected function convertToHierarchyStructure(array $employeesTimeSheet):array
    {
        $hierarchyStructure = [];

        foreach ($employeesTimeSheet as $employee){
            if(!isset($employee["Employer"]) || !$employee["Employer"]){
                $subordinates = $this->getSubordinateEmployees($employee, $employeesTimeSheet);
                if(!empty($subordinates)){
                    $employee ["Subordinates"] = $subordinates;
                }

                $hierarchyStructure [ $employee["Id"] ] = $employee;
            }
        }

        return $hierarchyStructure;
    }

    /**
     * Получение подчиненных сотрудников. Рекурсивный метод
     * @param array $employee
     * @param array $employeesTimeSheet
     * @return array
     */
    protected function getSubordinateEmployees(array $employee, array $employeesTimeSheet):array
    {
        $subordinateEmployees = [];
        foreach ($employeesTimeSheet as $employeeItem){
            if($employeeItem["Employer"] == $employee["Id"]){
                $subordinates = $this->getSubordinateEmployees($employeeItem, $employeesTimeSheet);
                if(!empty($subordinates)){
                    $employeeItem["Subordinates"] = $subordinates;
                }

                $subordinateEmployees []= $employeeItem;
            }
        }

        return $subordinateEmployees;
    }

    /**
     * Вычисление времени работы сотрудника за отчетный период
     * @param array $employeesTimeSheet
     * @return array
     */
    protected function calculateTotalTimes(array $employeesTimeSheet):array
    {
        $employeesTimeSheetWithSingleTotalTime = $this->calculateSingleTotalTimes($employeesTimeSheet);
        $employeesTimeSheetWithTotalTimeIncludeSubordinate = $this->calculateTotalTimesIncludeSubordinate($employeesTimeSheetWithSingleTotalTime);
        return $employeesTimeSheetWithTotalTimeIncludeSubordinate;
    }

    /**
     * Вычисление времени работы сотрудника за отчетный период(без времени подчиненных).Рекурсивный метод
     * @param array $employeesTimeSheet
     * @return array
     */
    protected function calculateSingleTotalTimes(array $employeesTimeSheet):array
    {
        $employeesTimeSheetWithTotalTime = [];

        foreach ($employeesTimeSheet as $key => $employee){
            if(!empty($employee["Subordinates"])){
                $employee["Subordinates"] = $this->calculateSingleTotalTimes($employee["Subordinates"]);
            }

            $employee["TotalTime"] = $this->calculateTotalTimeEmployee($employee);
            $employee["TotalTimeStr"] = $this->getTimeStrByCountSeconds($employee["TotalTime"]);
            $employeesTimeSheetWithTotalTime [$key] = $employee;
        }

        return $employeesTimeSheetWithTotalTime;
    }

    /**
     * Вычисление работы одного сотрудника за отчетный период
     * @param array $employee
     * @return int
     */
    protected function calculateTotalTimeEmployee(array $employee):int
    {
        $totalTime = 0;
        foreach ($employee["TimeSheet"] as $timeSheetItem){
            $totalTime += $timeSheetItem["TimeSeconds"];
        }

        return $totalTime;
    }

    /**
     * Вычисление времени работы сотрудников за отчетный период(с учетом времени работы подчиненных)
     * @param array $employeesTimeSheet
     * @return array
     */
    protected function calculateTotalTimesIncludeSubordinate(array $employeesTimeSheet):array
    {
        $employeesTimeSheetWithTotalTime = [];
        foreach ($employeesTimeSheet as $key => $employee){
            if(!empty($employee["Subordinates"])){
                $employee["Subordinates"] = $this->calculateTotalTimesIncludeSubordinate($employee["Subordinates"]);
            }

            $employee["TotalTimeIncludeSubordinate"] = $this->calculateTotalTimeEmployeeIncludeSubordinate($employee);
            $employee["TotalTimeIncludeSubordinateStr"] = $this->getTimeStrByCountSeconds($employee["TotalTimeIncludeSubordinate"]);

            $employeesTimeSheetWithTotalTime [$key] = $employee;
        }

        return $employeesTimeSheetWithTotalTime;
    }

    /**
     * Вычисление времени работы одного сотрудника за отчетный период(с учетом времени работы подчиненных).Рекурсивный метод
     * @param array $employee
     * @return int
     */
    protected function calculateTotalTimeEmployeeIncludeSubordinate(array $employee):int
    {
        $totalTime = $employee["TotalTime"];
        if(!empty($employee["Subordinates"])){
            foreach ($employee["Subordinates"] as $subordinateEmployee){
                $totalTime += $this->calculateTotalTimeEmployeeIncludeSubordinate($subordinateEmployee);
            }
        }

        return $totalTime;
    }

    /**
     * Получение строкового представления отработанного времени (часы, минуты, секунды)
     * @param int $seconds
     * @return string
     */
    protected function getTimeStrByCountSeconds(int $seconds):string
    {
        return \gmdate("H:i:s", $seconds);
    }

}