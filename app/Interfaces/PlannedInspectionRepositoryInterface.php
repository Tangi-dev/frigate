<?php

namespace App\Interfaces;

interface PlannedInspectionRepositoryInterface
{
    /**
     * Возвращает массив с ключами
     * @param array $params ['page'=>int,'per_page'=>int,'filters'=>array]
     * @return array
     */
    public function getInspections(array $params): array;

    /**
     * Возвращает запись проверки с данными СМП
     */
    public function findWithSmp(int $id): ?array;

    /**
     * Список уникальных контролирующих органов
     */
    public function getAuthoritiesList(): array;

    /**
     * Создать новую запись проверки
     */
    public function createInspection(array $data): ?array;

    /**
     * Сгенерировать следующий номер проверки
     */
    public function generateInspectionNumber(): string;

    /**
     * Обновить запись проверки
     * @param int $id
     * @param array $data
     * @return array|false true при успехе, false при ошибке
     */
    public function updateInspection(int $id, array $data);

    /**
     * Удалить запись проверки
     * @param int $id
     * @return bool true при успехе, false при ошибке
     */
    public function deleteInspection(int $id): bool;

    /**
     * Найти по ID
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array;

    /**
     * Найти по СМП и датам (для импорта)
     * @param int $smpId
     * @param string $startDate
     * @param string $endDate
     * @return array|null
     */
    public function findBySmpIdAndDates(int $smpId, string $startDate, string $endDate): ?array;
}
