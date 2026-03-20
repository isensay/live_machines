<?php

/**
 * Контроллер для управления справочником стран
 */

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Livemachines\CountryModel;

class CountryController extends Controller {
    private $techParam = null;
    private $dbConnection;
    private $paramTypeId = 1; // Технические характеристики

    public function __construct() {
        $this->dbConnection = DB::connection('livemachines');
        $this->countryModel   = new CountryModel([], $this->dbConnection, $this->paramTypeId);
    }

    /**
     * Список стран
     */
    public function index() {
        $dbLm = DB::connection('livemachines');
        $sql =
        "
        SELECT
            `countryId`,
            `countryName`,
            IF(`manufCount` > 0, `manufCount`, '') as `manufCount`,
            IF(`fileCount`  > 0, `fileCount`,  '') as `fileCount`
        FROM
            (
                SELECT
                    `dirty_country_id`               as `countryId`,
                    `dirty_country_name`             as `countryName`,
                    COUNT(DISTINCT `dirty_manuf_id`) as `manufCount`,
                    COUNT(DISTINCT `dirty_file_id`)  as `fileCount`
                FROM `dirty_country`
                    LEFT JOIN `dirty_country_file`  ON (`dirty_country_id` = `dirty_country_file_dirty_country_id`)
                    LEFT JOIN `dirty_file`          ON (`dirty_file_id`    = `dirty_country_file_dirty_file_id`)
                    LEFT JOIN `dirty_manuf_country` ON (`dirty_country_id` = `dirty_manuf_country_dirty_country_id`   AND `dirty_manuf_country_remove_user_id` = 0)
                    LEFT JOIN `dirty_manuf`         ON (`dirty_manuf_id`   = `dirty_manuf_country_dirty_manuf_id`)
                WHERE 1
                    AND `dirty_country_remove_user_id` = 0
                    AND (dirty_file_id IS NULL OR `dirty_file_remove_user_id` = 0)
                GROUP BY
                    `countryId`
            ) as `tmp`
        ";
        $data = $dbLm->select($sql);
        return view('livemachines/country', [
            'title'       => 'Список справочников - Adoxa',
            'description' => '',
            'data'        => $data,
        ]);
    }
}
