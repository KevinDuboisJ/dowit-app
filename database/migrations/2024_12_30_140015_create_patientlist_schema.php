<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePatientListSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // Create tables in the new database
        // Create the database using the default connection (avoiding `patientlist` connection)
        DB::statement('CREATE DATABASE IF NOT EXISTS ' . Config::get('database.connections.patientlist.database'));

        // Create the patients table
        Schema::connection('patientlist')->create('patients', function ($table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('gender')->nullable();
            $table->string('birthdate')->nullable();
            $table->timestamps();
        });

        // Create visits table
        Schema::connection('patientlist')->create('visits', function ($table) {
            $table->id();
            $table->string('number');
            $table->foreignId('patient_id');
            $table->foreignId('space_id')->nullable();
            $table->foreignId('campus_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->foreignId('bed_id')->nullable();
            $table->datetime('admission');
            $table->datetime('discharge')->nullable();
            // $table->string('adm_date');
            // $table->string('adm_time');
            // $table->string('dis_date');
            // $table->string('dis_time');
            //$table->boolean('dismissed');
            $table->timestamps();

            // Uniqueness constraint to prevent duplicates
            $table->unique(['number', 'patient_id']);
        });

        // Create departments table
        Schema::connection('patientlist')->create('departments', function ($table) {
            $table->id();
            $table->string('number');
            $table->timestamps();
        });

        // Create beds table
        Schema::connection('patientlist')->create('beds', function ($table) {
            $table->id();
            $table->foreignId('room_id');
            $table->string('number');
            $table->datetime('occupied')->nullable();
            $table->datetime('cleaned')->nullable();
            $table->timestamps();

            // Uniqueness constraint to prevent duplicates
            $table->unique(['room_id', 'number']);
        });




        Schema::connection('patientlist')->create('bed_visits', function ($table) {
            $table->id();
            $table->foreignId('bed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_date');
            $table->timestamp('stop_date')->nullable();
            $table->timestamps();
        });

        // Create rooms
        Schema::connection('patientlist')->create('rooms', function ($table) {
            $table->id();
            $table->string('number')->unique();
            $table->timestamps();
        });

        // Create the migrations table
        Schema::connection('patientlist')->create('migrations', function ($table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('patientlist')->dropIfExists('visits');
        Schema::connection('patientlist')->dropIfExists('patients');
        Schema::connection('patientlist')->dropIfExists('departments');
        Schema::connection('patientlist')->dropIfExists('beds');
        Schema::connection('patientlist')->dropIfExists('bed_visits');
        Schema::connection('patientlist')->dropIfExists('rooms');
        Schema::connection('patientlist')->dropIfExists('migrations');
    }
}


// Keys: 'pat_id', 'visit_number', 'firstname', 'lastname', 'ext_id_1', 'spoken_language','campus_id','ward_id','room_id','bed_id', 'adm_date', 'adm_time', 'dis_date', 'dis_time'

// Example of patient 01:
// {#1956 ▼ // app\Http\Controllers\Pages\DashboardController.php:24
//   +"pat_id": "5906281511"
//   +"from_date": "1990-01-0100:00:00.000"
//   +"u_version": "-"
//   +"lastname": "Landuydt"
//   +"firstname": "LucElisabeth"
//   +"sex": "1"
//   +"birthdate": "28-06-1959"
//   +"doctor_id_home": "118668"
//   +"initials": null
//   +"street": "Gemeentestraat16"
//   +"house_no": null
//   +"bus_no": null
//   +"postal_code": "2570"
//   +"post_sub_code": "0"
//   +"country_code": "100"
//   +"state": "Antwerpen"
//   +"nationality": "100"
//   +"country_of_birth": null
//   +"pat_language": "NL"
//   +"phone": "F"
//   +"marital_status": "5"
//   +"religion": null
//   +"ext_id_1": "59062837170"
//   +"ext_id_2": "1514923"
//   +"ext_id_3": null
//   +"esp_lastname": null
//   +"esp_firstname": null
//   +"relation_hosp": null
//   +"date_last_adj": null
//   +"adj_by": null
//   +"patbankaccount": null
//   +"badge": null
//   +"date_death": null
//   +"detail_inv": null
//   +"title": "01"
//   +"check_status": "8"
//   +"parish": null
//   +"u_who": ""
//   +"u_datim": "1900-01-0100:00:00.000"
//   +"date_baddge": null
//   +"u_creator": "HL7IN"
//   +"city": "DUFFEL"
//   +"home_doc_letter": "T"
//   +"occupation": null
//   +"med_alert": null
//   +"dubious_debtor": null
//   +"doc_changed": null
//   +"who_doc_changed": null
//   +"usr_prot_data": null
//   +"source_adr": "USER"
//   +"icon": "VER4"
//   +"source_id": "USER"
//   +"validation_id": "020"
//   +"gsm": "+32475832442"
//   +"e_mail": "luc.landuydt@telenet.be"
//   +"wrongaddress": null
//   +"patbankbic": null
//   +"ext_id_gov": "P5906281511"
//   +"blob_id_pic": "8379514"
//   +"ORIGIN_INST": null
//   +"birth_time": null
//   +"death_time": null
//   +"doctor_id_home_ref": "9772"
//   +"doctor_id_gmf": null
//   +"doctor_riziv_no_gmf": null
//   +"pharmacy_id_gff": null
//   +"doctor_name_gmf": null
//   +"alpha_first": "LUCELISABETH"
//   +"alpha_last": "LANDUYDT"
//   +"alphalast_espname": null
//   +"alphafirst_espname": null
//   +"pat_id_mother": null
//   +"creation_date": null
//   +"obj_type": "000"
//   +"pat_id_suggestion": null
//   +"spoken_language": "NL"
//   +"birth_city": "Mortsel"
//   +"finished_pat": "F"
//   +"lastrncheckdate": "2022-10-2500:00:00.000"
//   +"alpha_street": "GEMEENTESTRAAT"
//   +"postal_code_sub": "0"
//   +"visit_number": "71551560"
//   +"visit_type": "1"
//   +"adm_date": "2025-01-0900:00:00.000"
//   +"adm_time": "1900-01-0110:09:00.000"
//   +"dis_date": null
//   +"dis_time": null
//   +"exp_adm_days": "0"
//   +"doctor_id_ref": null
//   +"phone_code": null
//   +"visitors_code": null
//   +"coming_from": "0"
//   +"ref_by": "1"
//   +"mod_of_adm": "0"
//   +"kind_of_dis": null
//   +"destination_dis": null
//   +"save_code": null
//   +"vadmdecp": null
//   +"tadmdecpsign": null
//   +"dadmdecpprint": null
//   +"dadmdecpsign": null
//   +"vadmdecld": null
//   +"vlistadm": null
//   +"vlistdis": null
//   +"vcardadm": null
//   +"vcarddis": null
//   +"vadmmenthomedoc": null
//   +"vdismentnhomed": null
//   +"code_non_fact": null
//   +"personal_id": null
//   +"verify": "0"
//   +"dadmdecldback": null
//   +"dadmdecldprint": null
//   +"label_print_date": null
//   +"dlistadmprint": null
//   +"dlistdisprint": null
//   +"dcarddisprint": null
//   +"prob_dis_date": "2025-01-0900:00:00.000"
//   +"prob_dis_time": "1900-01-0122:00:00.000"
//   +"invoiced": null
//   +"code_adm": "3"
//   +"kind_of_adm": null
//   +"adm_doc_letter": null
//   +"dis_doc_letter": null
//   +"finished_inv": null
//   +"finished_vis": null
//   +"h_doc_disch_lett": null
//   +"department_id": "1901"
//   +"campus_id": "M"
//   +"ward_id": "1617"
//   +"room_id": "015"
//   +"bed_id": "01"
//   +"ward_adm_id": "1617"
//   +"adm_doctor_id": "000359"
//   +"perform_doctor_id": "000359"
//   +"tm": null
//   +"suppl_doc": null
//   +"multiple": null
//   +"trans_type": null
//   +"trans_visit_id": null
//   +"room_redcod": "0"
//   +"del_reason": null
//   +"origin": null
//   +"reserv_id": "71551560"
//   +"adm_date04": null
//   +"adm_time04": null
//   +"case_id": "24286479"
//   +"case_type": "HOSPI1"
//   +"attest_nbr": null
//   +"menu_id": null
//   +"attest_printed": "0"
//   +"menu_cb": "0"
//   +"AH_UTD": "T"
//   +"form_mkg": null
//   +"urg_cause": null
//   +"urg_role": null
//   +"urg_fol_up": null
//   +"ambu_id": null
//   +"mug_id": null
//   +"mug_fi": null
//   +"mzg_link": null
//   +"doctor_id_ref_2": null
//   +"internet": null
//   +"DESTINATION_INST": null
//   +"reason_id": null
//   +"exit_date": null
//   +"exit_time": null
//   +"discipline_id": "MPIJ"
//   +"dis_date04": null
//   +"dis_time04": null
//   +"study_id": null
//   +"operation_date": null
//   +"operation_time": null
//   +"orig_pat_id": "5906281511"
//   +"id_ok": "Y"
//   +"doctor_id_ref_ref": null
//   +"doctor_id_ref_2_ref": null
//   +"pat_legal_status": null
//   +"prior_cont_adr": null
//   +"country_iso": "B"
//   +"look_up_type": null
//   +"country_language": "NL"
//   +"country_name": "BELGIE"
//   +"country_nat": "Belg"
//   +"postal_length": "4"
//   +"alpha_name": "BELGIE"
//   +"RHMMZG_NAT": "BE"
//   +"country_iso_2": "BE"
//   +"rhmmzg_id": "150"
//   +"inactive_from": null
//   +"GEOCODEFAC": "1"
//   +"bilateral": "F"
//   +"postal_mask": "####"
//   +"min_phone_length": "8"
//   +"max_phone_length": "9"
//   +"dialing_code": "32"
//   +"postal_language": "NL"
//   +"postal_name": "DUFFEL"
//   +"alphaname": "DUFFEL"
//   +"nis": "12009"
//   +"telzone": null
//   +"gender": "M"
// }