<?php

namespace Config;

use App\Repositories\SmpRepository;
use App\Services\Import\ExcelImportProcessor;
use App\Services\Import\ExcelService;
use App\Services\Import\InspectionDataTransformer;
use App\Services\Import\InspectionDataValidator;
use App\Services\SmpService;
use CodeIgniter\Config\BaseService;
use App\Repositories\DbPlannedInspectionRepository;
use App\Models\PlannedInspectionModel;
use App\Services\InspectionService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function plannedInspectionRepository(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('plannedInspectionRepository');
        }

        return new DbPlannedInspectionRepository(new PlannedInspectionModel());
    }

    public static function smpRepository($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('smpRepository');
        }

        return new SmpRepository();
    }

    public static function excelService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('excelService');
        }

        return new ExcelService();
    }

    public static function excelImportProcessor($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('excelImportProcessor');
        }
        $validator = new InspectionDataValidator();
        $transformer = new InspectionDataTransformer();

        $smpService = new SmpService(
            self::smpRepository(false)
        );

        $inspectionService = new InspectionService(
            self::plannedInspectionRepository(false),
            $transformer
        );

        return new ExcelImportProcessor($validator, $smpService, $inspectionService);
    }

    public static function inspectionService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('inspectionService');
        }
        $plannedInspectionRepo = new DbPlannedInspectionRepository();
        $transformer = new InspectionDataTransformer();

        return new InspectionService($plannedInspectionRepo, $transformer);
    }
}
