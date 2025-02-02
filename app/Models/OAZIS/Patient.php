<?php

namespace App\Models\OAZIS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Patient extends Model
{
    protected $connection = 'oazis'; // Specify the connection (optional if default is sqlsrv)
    protected $table = 'adt_unipat';   // Specify the table name

    public static function getByPatientId(string $patientId): null|object
    {
        $result = null;

        $query = "
            SELECT
                adt_unipat.pat_id AS pat_id,
                adt_visit.visit_id AS visit_id,
                adt_unipat.ext_id_1 AS ext_id_1,
                spoken_language,
                campus_id,
                ward_id,
                room_id,
                bed_id,
                adm_date,
                adm_time,
                dis_date,
                dis_time,
                firstname,
                lastname,
                adt_unipat.sex AS gender,
                adt_unipat.birthdate AS birthdate
            FROM 
                (
                    (
                        (adt_unipat INNER JOIN unisuper u ON u.pat_id = adt_unipat.pat_id AND u.u_datim = (SELECT MAX(u_datim) FROM unisuper u2 WHERE u2.pat_id = u.pat_id))
                        INNER JOIN adt_visit ON u.pat_id = adt_visit.pat_id
                    )
                LEFT JOIN 
                    country c ON adt_unipat.country_code = c.country_code 
                    AND c.country_language = 'NL' 
                    AND c.u_datim = (SELECT MAX(u_datim) FROM country c2 WHERE c.country_code = c2.country_code)
                )
                LEFT JOIN 
                    postal_codes p ON adt_unipat.postal_code = p.postal_code 
                    AND adt_unipat.country_code = p.country_code 
                    AND p.postal_language = 'NL' 
                    AND p.postal_code_sub = (SELECT MIN(postal_code_sub) FROM postal_codes p3 WHERE p.postal_code = p3.postal_code) 
                    AND p.u_datim = (SELECT MAX(u_datim) FROM postal_codes p2 WHERE p.postal_code = p2.postal_code)
            WHERE 
                adt_visit.visit_id = ?
            ORDER BY 
                adt_unipat.from_date DESC
        ";

        $results = DB::connection('oazis')->select($query, [$patientId]);
        
        if (!empty($results[0])) {
            $result = $results[0];
            $result->birthdate = self::formatBirthdate($result->birthdate);
            $result->gender = self::formatGender($result->gender);
            $result->campus_id = self::formatGender($result->campus_id);

            // Check all columns for spaces at the start or end and remove them
            foreach ($result as $key => $value) {
                if (is_string($value)) {
                    $result->$key = trim($value); // Removes only leading and trailing spaces
                }
            }
        }

        return $result;
    }

    public static function formatBirthdate(string|null $birthdate)
    {
        if ($birthdate) {
            return Carbon::parse($birthdate)->format('d-m-Y');
        }
        return null;
    }

    public static function formatGender(string|null $gender)
    {
        if ($gender == '1' || strtoupper($gender) == 'M')
            return 'M';
        if ($gender == '2' || strtoupper($gender) == 'V')
            return 'V';

        return '';
    }

    public static function formatCampus(string|null $campus)
    {
        if ($campus == '002')
            return 'Deurne';
        if ($campus == '001')
            return 'Antwerpen';

        return '';
    }

    // public static function getByPatientId($patientId)
    // {
    //     $query = "
    //         SELECT 
    //             *, 
    //             adt_unipat.sex AS patsex, 
    //             unisuper.pat_id AS patid, 
    //             unisuper.ext_id_1 AS insz 
    //         FROM 
    //             (((adt_unipat 
    //             INNER JOIN unisuper ON unisuper.pat_id = adt_unipat.pat_id)
    //             INNER JOIN adt_visit ON unisuper.pat_id = adt_visit.pat_id)
    //             INNER JOIN ward_descr ON ward_descr.ward_id = adt_visit.ward_id)
    //         LEFT JOIN 
    //             postal_codes 
    //             ON adt_unipat.postal_code = postal_codes.postal_code 
    //             AND adt_unipat.country_code = postal_codes.country_code 
    //             AND postal_language = 'NL'
    //         LEFT JOIN 
    //             country 
    //             ON postal_codes.country_code = country.country_code
    //         WHERE 
    //             adt_visit.visit_id = ?
    //         ORDER BY 
    //             adt_unipat.from_date DESC, 
    //             ward_descr.from_date DESC
    //     ";

    //     return DB::connection('oazis')->select($query, [$patientId]);
    // }
}
