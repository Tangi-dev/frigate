<?php

namespace App\Repositories;

use App\Interfaces\PlannedInspectionRepositoryInterface;
use App\Models\PlannedInspectionModel;

class DbPlannedInspectionRepository implements PlannedInspectionRepositoryInterface
{
    protected PlannedInspectionModel $model;

    public function __construct(PlannedInspectionModel $model = null)
    {
        // Позволяю DI-передачу модели или создаю новую
        $this->model = $model ?? new PlannedInspectionModel();
    }

    public function getInspections(array $params): array
    {
        return $this->model->getInspectionsWithSmp($params);
    }

    public function findWithSmp(int $id): ?array
    {
        return $this->model->findWithSmp($id);
    }

    public function getAuthoritiesList(): array
    {
        return $this->model->getAuthoritiesList();
    }

    /**
     * Создать запись плановой проверки и вернуть полную запись (с данными СМП)
     * @param array $data
     * @return array|null
     */
    public function createInspection(array $data): ?array
    {
        // Вставим через модель. Отключим защиту на время вставки
        $this->model->protect(false);
        $insertId = $this->model->insert($data);
        $this->model->protect(true);

        if ($insertId === false) {
            // Вернём ошибки валидации модели, если они есть
            $errors = method_exists($this->model, 'errors') ? $this->model->errors() : [];
            return ['errors' => $errors];
        }

        // Вернём запись с данными СМП
        return $this->findWithSmp((int)$insertId);
    }

    /**
     * Сгенерировать следующий номер проверки
     */
    public function generateInspectionNumber(): string
    {
        return $this->model->generateInspectionNumber();
    }

    /**
     * Обновить запись проверки
     * @param int $id
     * @param array $data
     * @return array|false
     */
    public function updateInspection(int $id, array $data)
    {
        // Отключаем защиту, т.к. данные уже смэппированы
        $this->model->protect(false);
        $success = $this->model->update($id, $data);
        $this->model->protect(true);

        if ($success === false) {
            $errors = method_exists($this->model, 'errors') ? $this->model->errors() : [];
            return ['errors' => $errors];
        }

        // Вернём обновлённую запись
        return $this->findWithSmp($id);
    }

    /**
     * Удалить одну запись проверки
     * @param int $id
     * @return bool
     */
    public function deleteInspection(int $id): bool
    {
        $this->model->protect(false);
        $res = $this->model->delete($id);
        $this->model->protect();
        return (bool)$res;
    }

    /**
     * Найти по номеру проверки
     * @param string $number
     * @return array|null
     */
    public function findByInspectionNumber(string $number): ?array
    {
        return $this->model->where('inspection_number', $number)->first();
    }

    /**
     * Найти по ID
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        return $this->model->find($id);
    }

    /**
     * Дополнительный метод для импорта: найти по СМП и датам
     * @param int $smpId
     * @param string $startDate
     * @param string $endDate
     * @return array|null
     */
    public function findBySmpIdAndDates(int $smpId, string $startDate, string $endDate): ?array
    {
        return $this->model
            ->where('smp_id', $smpId)
            ->where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->first();
    }
}