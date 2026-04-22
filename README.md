[2026-02-17 08:50:14] production.DEBUG: array (
'message' => 'An error occurred in createTask: Pusher error: cURL error 28: SSL connection timeout (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://dowit.monica.be:6002/apps/795051/events?auth_key=s3w3thzezulgp5g0e5bw&auth_timestamp=1771314604&auth_version=1.0&body_md5=8260bfbc34d4b4d312cd3778e15667f9&auth_signature=65c947ca67b02880a06b28f95b6850205cfe9ca74255184706580afb80af8ef1.',
'file' => 'D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\Broadcasters\\PusherBroadcaster.php',
'line' => 164,
'trace' => '#0 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\BroadcastEvent.php(93): Illuminate\\Broadcasting\\Broadcasters\\PusherBroadcaster->broadcast()
#1 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(36): Illuminate\\Broadcasting\\BroadcastEvent->handle()
#2 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()
#3 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(95): Illuminate\\Container\\Util::unwrapIfClosure()
#4 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod()
#5 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(696): Illuminate\\Container\\BoundMethod::call()
#6 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Bus\\Dispatcher.php(126): Illuminate\\Container\\Container->call()
#7 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(170): Illuminate\\Bus\\Dispatcher->Illuminate\\Bus\\{closure}()
#8 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(127): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()
#9 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Bus\\Dispatcher.php(130): Illuminate\\Pipeline\\Pipeline->then()
#10 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\BroadcastManager.php(182): Illuminate\\Bus\\Dispatcher->dispatchNow()
#11 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Events\\Dispatcher.php(358): Illuminate\\Broadcasting\\BroadcastManager->queue()
#12 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Events\\Dispatcher.php(281): Illuminate\\Events\\Dispatcher->broadcastEvent()
#13 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Events\\Dispatcher.php(267): Illuminate\\Events\\Dispatcher->invokeListeners()
#14 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\PendingBroadcast.php(72): Illuminate\\Events\\Dispatcher->dispatch()
#15 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\helpers.php(223): Illuminate\\Broadcasting\\PendingBroadcast->**destruct()
#16 D:\\Applicaties\\dowit\\app\\Services\\TaskPlannerService.php(147): broadcast()
#17 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\Concerns\\ManagesTransactions.php(32): App\\Services\\TaskPlannerService->App\\Services\\{closure}()
#18 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\DatabaseManager.php(495): Illuminate\\Database\\Connection->transaction()
#19 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Support\\Facades\\Facade.php(361): Illuminate\\Database\\DatabaseManager->**call()
#20 D:\\Applicaties\\dowit\\app\\Services\\TaskPlannerService.php(116): Illuminate\\Support\\Facades\\Facade::\_\_callStatic()
#21 D:\\Applicaties\\dowit\\app\\Console\\Commands\\HandleTaskPlanners.php(32): App\\Services\\TaskPlannerService->execute()
#22 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(36): App\\Console\\Commands\\HandleTaskPlanners->handle()
#23 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()
#24 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(95): Illuminate\\Container\\Util::unwrapIfClosure()
#25 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod()
#26 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(696): Illuminate\\Container\\BoundMethod::call()
#27 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(213): Illuminate\\Container\\Container->call()
#28 D:\\Applicaties\\dowit\\vendor\\symfony\\console\\Command\\Command.php(318): Illuminate\\Console\\Command->execute()
#29 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(182): Symfony\\Component\\Console\\Command\\Command->run()
#30 D:\\Applicaties\\dowit\\vendor\\symfony\\console\\Application.php(1092): Illuminate\\Console\\Command->run()
#31 D:\\Applicaties\\dowit\\vendor\\symfony\\console\\Application.php(341): Symfony\\Component\\Console\\Application->doRunCommand()
#32 D:\\Applicaties\\dowit\\vendor\\symfony\\console\\Application.php(192): Symfony\\Component\\Console\\Application->doRun()
#33 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php(198): Symfony\\Component\\Console\\Application->run()
#34 D:\\Applicaties\\dowit\\artisan(35): Illuminate\\Foundation\\Console\\Kernel->handle()
#35 {main}',
)

[2026-03-05 15:06:15] production.DEBUG: array (
'message' => 'An error occurred while handling task planners: Pusher error: cURL error 28: SSL connection timeout (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://dowit.monica.be:6002/apps/795051/events?auth_key=s3w3thzezulgp5g0e5bw&auth_timestamp=1772719565&auth_version=1.0&body_md5=cba9d216fd15e58b476849f7848fb974&auth_signature=384bb4e5ca810b7be8a1e31bff89c859cdd9aee6bbefe9cf95d4c08b8b5af9c0.',
'file' => 'D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\Broadcasters\\PusherBroadcaster.php',
'line' => 164,
'trace' => '#0 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\BroadcastEvent.php(93): Illuminate\\Broadcasting\\Broadcasters\\PusherBroadcaster->broadcast()
#1 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(36): Illuminate\\Broadcasting\\BroadcastEvent->handle()
#2 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()
#3 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(95): Illuminate\\Container\\Util::unwrapIfClosure()
#4 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod()
#5 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(696): Illuminate\\Container\\BoundMethod::call()
#6 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Bus\\Dispatcher.php(126): Illuminate\\Container\\Container->call()
#7 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(170): Illuminate\\Bus\\Dispatcher->Illuminate\\Bus\\{closure}()
#8 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(127): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()
#9 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Bus\\Dispatcher.php(130): Illuminate\\Pipeline\\Pipeline->then()
#10 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\BroadcastManager.php(182): Illuminate\\Bus\\Dispatcher->dispatchNow()
#11 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Events\\Dispatcher.php(358): Illuminate\\Broadcasting\\BroadcastManager->queue()
#12 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Events\\Dispatcher.php(281): Illuminate\\Events\\Dispatcher->broadcastEvent()
#13 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Events\\Dispatcher.php(267): Illuminate\\Events\\Dispatcher->invokeListeners()
#14 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\PendingBroadcast.php(72): Illuminate\\Events\\Dispatcher->dispatch()
#15 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\helpers.php(223): Illuminate\\Broadcasting\\PendingBroadcast->**destruct()
#16 D:\\Applicaties\\dowit\\app\\Services\\TaskPlannerService.php(147): broadcast()
#17 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\Concerns\\ManagesTransactions.php(32): App\\Services\\TaskPlannerService->App\\Services\\{closure}()
#18 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Database\\DatabaseManager.php(495): Illuminate\\Database\\Connection->transaction()
#19 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Support\\Facades\\Facade.php(361): Illuminate\\Database\\DatabaseManager->**call()
#20 D:\\Applicaties\\dowit\\app\\Services\\TaskPlannerService.php(116): Illuminate\\Support\\Facades\\Facade::\_\_callStatic()
#21 D:\\Applicaties\\dowit\\app\\Console\\Commands\\HandleTaskPlanners.php(32): App\\Services\\TaskPlannerService->execute()
#22 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(36): App\\Console\\Commands\\HandleTaskPlanners->handle()
#23 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()
#24 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(95): Illuminate\\Container\\Util::unwrapIfClosure()
#25 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod()
#26 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(696): Illuminate\\Container\\BoundMethod::call()
#27 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(213): Illuminate\\Container\\Container->call()
#28 D:\\Applicaties\\dowit\\vendor\\symfony\\console\\Command\\Command.php(318): Illuminate\\Console\\Command->execute()
#29 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(182): Symfony\\Component\\Console\\Command\\Command->run()
#30 D:\\Applicaties\\dowit\\vendor\\symfony\\console\\Application.php(1092): Illuminate\\Console\\Command->run()
#31 D:\\Applicaties\\dowit\\vendor\\symfony\\console\\Application.php(341): Symfony\\Component\\Console\\Application->doRunCommand()
#32 D:\\Applicaties\\dowit\\vendor\\symfony\\console\\Application.php(192): Symfony\\Component\\Console\\Application->doRun()
#33 D:\\Applicaties\\dowit\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php(198): Symfony\\Component\\Console\\Application->run()
#34 D:\\Applicaties\\dowit\\artisan(35): Illuminate\\Foundation\\Console\\Kernel->handle()
#35 {main}',
)

Bij een verandering van bed kan het gebeuren dat de patiënt op dat moment geen bed toegewezen heeft? Ik moet het weten, want anders kan een taakplanner uitgeschakeld worden omdat die denkt dat de opname geen bed toegewezen heeft. Dan moet anders de discharge_at gebruik maar wel weten dat soms dit blijkbaar niet wordt aangepast in het systeem.


1. Change icons in filament.
2. A admin can se historiek tasks, add extra filter. && Task that are replaced or skipped are only for the admin views. it should also have another color grey and opacity lower to indicate it is not activated. should it use the is_activate?
3. Create an extension for the customLink that has target '\_blank' as default, probably has to become a icon as the original link. look at link source code for help.
4. Add Phone sound and vibration on task creation
5. See if i can see if a user is active in an application, and add this information to the user resource in admin panel
6. Patientransport task where patient is selected from the taskplanner has to update the "FROM location" automatic when the patient moves from bed.
8. When an announcement is deleted the dashboard announcemenet feed doesnt pull the new state
9. mededeling zonder einddatum wordt eindatum op +1 maand
10. inifinite scroll not working on mobile for tasks
11. repasar la logica de patientservice y revisar si el hecho de que varios bed visit con occupied at con diferentes timestamps reciben el mismo vacated_at timestamp. Creo que deberian ser diferentes y tener el timestamp del momento en que realmente el patiente se fue.
12. repasar la logica de patientservice y revisar como evitar errores en la sincronizacion de $noLongerOccupied porque una vez esta marque la habitacion como desocupada ya no se creara una tarea en caso de haber fallado algo en la creacion de la tarea
16. Ask Natascha how to handle the updates of the tasks. Currently if a user asking teams is also the execution team then it can update it. Task id 18819 is an example of this


---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//

19. it get all data from oazis for all occupied beds.
20. it get all the bed_visits records that have vacated_at set to null
21. it compares both tables. if bed_id, room_id and visit_id is the same it skips it.
22. if the record is not more in the data from oazis. update bed_visit with the stop date (Fetch patient and visit data apart to get the latest data?) AND create the task
    Info: If a person was assigned to the wrong bed and this was already recorded in the bed_visits table, a task will be created. A task will also be created if the bed or room number changes due to updates in the Primuz/Oazis database. Note that the script is not designed to track the Primuz/Oazis room and bed tables one-to-one, so new beds or rooms will be created if the number of it is updated.
23. Else create a new bed_visit as there is no need to update cause if something changes in beed or room in oazis data it means new bed visit.

24. Identifier: User gives a identifier in this serves for identifing the chain and for the api it helps creating only one endpoint api/v1/{identifier}
25. description: in case user want to specify what the chain does.
26. trigger_type: the user specifies if it is triggered via the APi or INTERNAL
27. actions user can currently only select 'custom' and add extra field custom_code_class->label is 'interface'

• The hintIcon tooltip hides on click and escapes HTML, so a custom component is used to allow HTML and custom behavior.

Behavior and flow
Teams behaviour:

1.  Super admins can do everything
2.  Records are visible for the creator but are not editable if they dont belong to atleast one team or deletable only if it belong to all teams.
3.  Records are visible for the teams who are assigned to it. If a record is shared between multiple teams an user cant remove a team from the record if they dont belong to it,
    they only can see to what teams the record belongs to

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
