

Bij een verandering van bed kan het gebeuren dat de patiënt op dat moment geen bed toegewezen heeft? Ik moet het weten, want anders kan een taakplanner uitgeschakeld worden omdat die denkt dat de opname geen bed toegewezen heeft. Dan moet anders de discharge_at gebruik maar wel weten dat soms dit blijkbaar niet wordt aangepast in het systeem.

  TO-DO's
  1. Change icons in filament.
  2. paginate the results of the filters in tasks page
  5. A admin can se historiek tasks, add extra filter. && Task that are replaced or skipped are only for the admin views. it should also have another color grey and opacity lower to indicate it is not activated. should it use the is_activate?
  7. Create an extension for the customLink that has target '_blank' as default, probably has to become a icon as the original link. look at link source code for help.
  20. use the same logic and view of dropdown as the Patient-autocomplete for all the other inputs with dropdown
  36. Add maybe an advanced filters. also when filtered task are showed with the groups of assigned task or not. add filter to show only assigned to me.
  40. Add Phone sound and vibration on task creation
  41. ergowater en fango vaste taken voor logistiek
  42. Holidays library cant access the online api. it still references the 2025 dates
  43. Nathan Van Weydeveldt staat nog i nDowit account is precies niet verwijerd geweest, nakijken
  44. Nadia Simons kan feed van andere teams zien.

1. it get all data from oazis for all occupied beds.
2. it get all the bed_visits records that have vacated_at set to null 
3. it compares both tables. if bed_id, room_id and visit_id is the same it skips it.
4. if the record is not more in the data from oazis. update bed_visit with the stop date (Fetch patient and visit data apart to get the latest data?) AND create the task
Info: If a person was assigned to the wrong bed and this was already recorded in the bed_visits table, a task will be created. A task will also be created if the bed or room number changes due to updates in the Primuz/Oazis database. Note that the script is not designed to track the Primuz/Oazis room and bed tables one-to-one, so new beds or rooms will be created if the number of it is updated.
5. Else create a new bed_visit as there is no need to update cause if something changes in beed or room in oazis data it means new bed visit.

1. Identifier: User gives a identifier in this serves for identifing the chain and for the api it helps creating only one endpoint api/v1/{identifier}
2. description: in case user want to specify what the chain does.
3. trigger_type: the user specifies if it is triggered via the APi or INTERNAL
4. actions user can currently only select 'custom' and add extra field custom_code_class->label is 'interface'


• The hintIcon tooltip hides on click and escapes HTML, so a custom component is used to allow HTML and custom behavior.

Behavior and flow
Teams behaviour:
 1. Super admins can do everything
 2. Records are visible for the creator but are not editable if they dont belong to atleast one team or deletable only if it belong to all teams.
 3. Records are visible for the teams who are assigned to it. If a record is shared between multiple teams an user cant remove a team from the record if they dont belong to it,
    they only can see to what teams the record belongs to



Domain rules:
1. When a user click on the help button to help someone, the task is always set to 'InProgress' and needs_help is set to false
2. If a user is an admin, belongs to one of the teams the record belongs to, or is the creator, they can edit the record
3. If a task is updated by one user while another user is editing outdated data, the second user will receive a stale data error message
4. Holiday Seeder logic Overview:
  1. The holiday name is used as a unique identifier (UID) to update public holidays in the holidays table annually.
  2. The seeder fetches public holiday data from the API and Updates existing holidays in the database using their names as UIDs and adds new records for holidays that are not already in the database
  3. After updating the holidays table any record in the database that does not have a date matching the API's holiday dates is soft-deleted.
  4. a holiday’s name changes, the seeder will treat it as a new holiday and create a new record.
    The previous record, which contains the outdated name, will not have its date updated and will therefore be soft-deleted, as its date no longer matches the API data.
    Although this scenario is highly unlikely, it has been considered as a precaution.
    If the holiday ID is used as a foreign key, a system notification should be implemented to handle this rare event and prevent references to an outdated holiday
5. For Schoonmaak, the task planners are configured to overwrite existing tasks. This was discussed with Natascha, who confirmed that this is the intended behavior, as tasks in Arta are often left open.
6. Super admins can access certain resources admins can't like Rollen, Teams or Algemene instellingen and can access all records
7. Only system newsfeed items or newsfeed items that belong to a team the user belongs to will be shown

