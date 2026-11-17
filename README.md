TODO's:

1. inifinite scroll not working on mobile for tasks
2. repasar la logica de patientservice y revisar si el hecho de que varios bed visit con occupied at con diferentes timestamps reciben el mismo vacated_at timestamp. Creo que deberian ser diferentes y tener el timestamp del momento en que realmente el patiente se fue.
3. repasar la logica de patientservice y revisar como evitar errores en la sincronizacion de $noLongerOccupied porque una vez esta marque la habitacion como desocupada ya no se creara una tarea en caso de haber fallado algo en la creacion de la tarea
4. when a suggested team in taskplannerresource is deleted, it show a bullet icon before the red hypen


---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//

1. it get all data from oazis for all occupied beds.
2. it get all the bed_visits records that have vacated_at set to null
3. it compares both tables. if bed_id, room_id and visit_id is the same it skips it.
4. if the record is not more in the data from oazis. update bed_visit with the stop date (Fetch patient and visit data apart to get the latest data?) AND create the task
    Info: If a person was assigned to the wrong bed and this was already recorded in the bed_visits table, a task will be created. A task will also be created if the bed or room number changes due to updates in the Primuz/Oazis database. Note that the script is not designed to track the Primuz/Oazis room and bed tables one-to-one, so new beds or rooms will be created if the number of it is updated.
5. Else create a new bed_visit as there is no need to update cause if something changes in beed or room in oazis data it means new bed visit.

6. Identifier: User gives a identifier in this serves for identifing the chain and for the api it helps creating only one endpoint api/v1/{identifier}
7. description: in case user want to specify what the chain does.
8. trigger_type: the user specifies if it is triggered via the APi or INTERNAL
9. actions user can currently only select 'custom' and add extra field custom_code_class->label is 'interface'

---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//

Domain rules:

1. When a user click on the help button to help someone, the task is always set to 'InProgress' and help_requested is set to false
2. If a user is an admin, belongs to one of the teams the record belongs to, or is the creator, they can edit the record
3. If a task is updated by one user while another user is editing outdated data, the second user will receive a stale data error message
4. Holiday Seeder logic Overview:
5. The holiday name is used as a unique identifier (UID) to update public holidays in the holidays table annually.
6. The seeder fetches public holiday data from the API and Updates existing holidays in the database using their names as UIDs and adds new records for holidays that are not already in the database
7. After updating the holidays table any record in the database that does not have a date matching the API's holiday dates is soft-deleted.
8. a holiday’s name changes, the seeder will treat it as a new holiday and create a new record.
   The previous record, which contains the outdated name, will not have its date updated and will therefore be soft-deleted, as its date no longer matches the API data.
   Although this scenario is highly unlikely, it has been considered as a precaution.
   If the holiday ID is used as a foreign key, a system notification should be implemented to handle this rare event and prevent references to an outdated holiday
9. For Schoonmaak, the task planners are configured to overwrite existing tasks. This was discussed with Natascha, who confirmed that this is the intended behavior, as tasks in Arta are often left open.
10. Super admins can access certain resources admins can't like Rollen, Teams or Algemene instellingen and can access all records
11. Only system newsfeed items or newsfeed items that belong to a team the user belongs to will be shown (task or mededeling)

---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//

Facts:

• The hintIcon tooltip hides on click and escapes HTML, so a custom component is used to allow HTML and custom behavior.