<?php

namespace App\Models\OAZIS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Patient extends Model
{
    protected $connection = 'oazis'; // Specify the connection (optional if default is sqlsrv)
    protected $table = 'adt_unipat';   // Specify the table name

    public static function getByPatientVisitId(string $value): null|object
    {
        $result = null;

        $query = "
            SELECT
                adt_unipat.pat_id AS patient_number,
                adt_visit.visit_id AS visit_number,
                adt_unipat.ext_id_1 AS ext_id_1,
                spoken_language,
                campus_id,
                ward_id as department_number,
                room_id as room_number,
                bed_id as bed_number,
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

        $results = DB::connection('oazis')->select($query, [$value]);

        if (!empty($results[0])) {
            $result = self::transformData($results[0]);
        }

        return $result;
    }

    public static function transformData($result)
    {
        $result->birthdate = self::formatBirthdate($result->birthdate);
        $result->gender = self::formatGender($result->gender);
        $result->campus_id = self::formatCampus($result->campus_id);

        // Check all columns for spaces at the start or end and remove them
        foreach ($result as $key => $value) {
            if (is_string($value)) {
                $result->$key = trim($value); // Removes only leading and trailing spaces
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
}
