1. Identifier: User gives a identifier in this serves for identifing the chain and for the api it helps creating only one endpoint api/v1/{identifier}
2. description: in case user want to specify what the chain does.
3. trigger_type: the user specifies if it is triggered via the APi or INTERNAL
4. actions user can currently only select 'custom' and add extra field custom_code_class->label is 'interface'

For the internal trigger: add a TaskObserver with ChainService::execute('internal', $task) and Register it in AppServiceProvider.

1. Bijlage toevoegen in task and taskplanner.
3. Taak van sletuel, taken van nood kloppen
3. Bijlage optie.


Thing to do in production when pulling new version.
1. Make sure the Chain table is equal to the test one


• The hintIcon tooltip hides on click and escapes HTML, so a custom component is used to allow HTML and custom behavior.

Behavior and flow
Teams behaviour:
 1. Super admins can do everything
 2. Records are visible for the creator but are not editable if they dont belong to atleast one team or deletable only if it belong to all teams.
 3. Records are visible for the teams who are assigned to it. If a record is shared between multiple teams an user cant remove a team from the record if they dont belong to it,
    they only can see to what teams the record belongs to
    
If a user belongs to both Bewaking and Schoonmaak CA, and there is a shared assignment rule for both, then when the user creates a task planner that complies with those rules, the task will be assigned to Bewaking and Schoonmaak CA
Schoonmaak CA admin can see that record and see to what teams it applies to, even if they do not belong to that team. But only a user that belongs to all the assigned teams can edit it.

When to update the user UI
  1. A task can be replaced or added by the task planner
  2. a User updates the task.
  

TO-DO's
  IMPORTANT: MAKE comments and task creation with richt text editor.
  1. Change icons in filament.
  2. Clicking the help icon on taskplanner for exampler shows a border around but doesnt display the help text.
  3. Create tabel with proposed teams with the task planner
  38. Set mobile variable to somewhere global?
  19. define the task types with Natascha
  48. Add a worker in laravel to allow the edit of tasks to be faster, this way i think i dont need Optimistically update the UI.
  53. Implement cache for task that have to be activated so only db query is made when a new task is created, and default get task that start date time < carbon now 
  59. A admin can se historiek tasks, add extra filter. && Task that are replaced or skipped are only for the admin views. it should also have another color grey and opacity lower to indicate it is not activated. should it use the is_activate?
  60. Replace tippy.js with floatingUI
  61. Fix the api route for laravel 11
  62. Check basic functionaility works before pushing to production
  63. Push and pull in production.
  64. Get the HL7 message in a api route to test(check mirth to send this data)
  65. Create an extension for the customLink that has target '_blank' as default, probably has to become a icon as the original link. look at link source code for help.
  66. fix issue where user creates task for its own in dashboard and doesnt show because things created or edited by the same user are omited in useWebsocket. Omit only for edit mode.
  67. tiptap both in the react and the filament version seems to be removing the target property of the <a> tag
  69. Add a button to add a assignment rule from within the taskplanner resource. i already have a part just check if there is a way to do something after opslaan is clicked so i can manually create the record using the form data
  70. Fix creatorField in hasFilamentField so that it uses created_by instead of dynamic generated
  71. when searching locations omit characters like '-' or '_' so that receptie - onthaal for example can be found as receptie onthaal
  72. when creating task show loader until notification is showed that it was succesfully created
  73. Set byUserInput that is in spaces in trait to use for all model that have a userinput fetch from the front end.

Questions.
  1. How can i know if a patient left a room?
  2. Oazis patient query has keys that contain same data, which one do i have to trust that will have always the correct info? e.g [exit_date,exit_time, dis_date04, dis_time04, "department_id": "1952  ",
        "campus_id": "002",
        "ward_id": "1952  ",
        "room_id": "00K",
        "bed_id": "137",
        "ward_adm_id": "1952  ",]

Chain Tasks (e.g., "Ketting eindpoets CA"):
Patient discharge
Room cleaning
Logistics task
Chain Tasks (e.g., "Ketting eindpoets CD"):
Patient discharge
Room cleaning
Two Types of Tasks:
Recurring Tasks: These tasks should be linked to a team-up schedule and repeat regularly.
One-Time Copy: A new copy of the task is created each time it is scheduled.
Ad Hoc Tasks
Any nursing department should be able to add tasks as needed.
A task from the fixed planning can be triggered as a one-time task when required.
Task Acceptance Workflow
Task Assignment: Employees can accept tasks via a smartphone interface.
Task Completion: Employees can mark tasks as complete once done.
Comments: Team members can add comments to open tasks, visible to all relevant users.
Task On Hold: Employees can put a task on hold, adding a note on its status. Another colleague should be able to pick up and complete the task if necessary.
Task Assignment by Supervisor
Supervisors should be able to pre-assign tasks to specific employees.
Recurring Tasks: Clarify if the assigned employee should always handle the task or only do so once.
Team Communication
Employees should be able to request help from colleagues directly within the system.
Current Task Tracking: Allow searching by name to see who is currently working on which task.
Color-Coded Task Status
Custom Task Colors: Allow users to choose a color when creating a task.
Unaccepted Task Indicators:
Change the task color if not accepted after 1 hour.
Further color change if not accepted after 2 hours.


DOWIT LOGIC OVERVIEW


  1. Super Admin can access all teams data
  2. Newsfeed:
    • Users can see comments where his team or user_id is in recipient_users or recipient_teams
    • Users can see comments created by himself, or task that are assigned to his teams, or task that are assigned to him.

Initial team assignment.

  1. A Team has a json column that is a text string in the form separated by comma, that defines to what team a user belongs to based on his DEPT number or function number.
  2. The user dept and function is stored in the user table.
  3. When a user is created a user is added to teams based on his DEPT or functions via a method that look what the dept and function is of the user and match is to the json column and is triggered from the user create api.
  4. If a user is edited

A user is added to a team

  1. Super admins can access certain resources admins can't.
  2. Super admins can see all the records on all the resources.
  3. Admins can only see the resources records of there team.
  4. Admins can only add sub teams to there teams.


Teams assignation rules logic overview.
 1. It is important to define the assignment rules correctly, as a user can assign the task to a team they do not belong to.

Task Planner logic overview.
 1. The task planner replaces a task even if it is already assigned or in progress. This was discussed with Natascha, who confirmed that this is how it should function, as in Arta tasks are often left open.
 
Holiday Seeder logic Overview:

  1. The holiday name is used as a unique identifier (UID) to update public holidays in the holidays table annually.

  2. The seeder fetches public holiday data from the API and Updates existing holidays in the database using their names as UIDs.
  Adds new records for holidays that are not already in the database.
  After updating the holidays table:

  4. Any record in the database that does not have a date matching the API's holiday dates is soft-deleted.
  Important Note:

  5. If a holiday's name changes, the seeder will treat it as a new holiday and create a new record.
  The previous record, with the outdated name, will not have its date updated, resulting in it being soft deleted as its date no longer matches the API data.
  This scenario is highly unlikely to occur but has been considered as a precaution. In the rare event that it does happen, and holidays are used as a foreign key for any other entity, the system must notify the user appropriately to ensure proper action can be taken.


Completed.
  • Fallover team cannot be selected in the multiselect
  • Accept the asked help
  • Refactored logic for edit permission. Also now a lock icon with tooltip is wshowed with message that only assigned usrs can edit.
  • Users that are assigned to the task dont see the help icon or the accept help button.







user click on start.
  1. Row data is updated
  2. optmistic update happens: handleRowUpdate and scroll to the task that moves to the todo's group.
  3. update database/websocket happens.


  if error happens.
  1. HandleRowUpdate will use the previous task data before the update to update again the UI.
  2. scroll doesnt need to happen.


  Updated_at has to be sended from the client as utc, cause if a optimistic update happens this data has to be stored in the lastUpdatedTask state to compared afterward with the data obtained in the websocket.
  in this way an extra http request is avoided to obtain just the updated_at.


om een stand van zaken te doen
HL7 vs ADT
intussen of tussen door.
nog iet dat moet besproken worden of zijn hier rond.

is websocket safe and used to maintain data sync between server and client? or can it happen that users misses some update in data?

2/2

ChatGPT said:
ChatGPT
Websockets are widely used to maintain real-time synchronization between the server and the client, making them a popular choice for applications requiring live updates, such as chat apps, collaborative tools, or dashboards. However, their reliability depends on implementation details and network conditions.

Are Websockets Safe?
Websockets are generally safe and reliable when implemented correctly:

Secure Protocol (WSS):

Use wss:// (WebSocket Secure) to ensure data transmission is encrypted, similar to HTTPS.
This protects against man-in-the-middle attacks and ensures the integrity of data.
Authentication:

Ensure proper authentication when establishing a WebSocket connection to prevent unauthorized access.
Use tokens, session-based authentication, or headers to validate the client.
Rate Limiting and Protection:

Protect against denial-of-service (DoS) attacks by rate-limiting connections or using firewalls.
Can Users Miss Updates?
While Websockets are designed to provide reliable real-time communication, updates can be missed under certain conditions:

Network Instability:

Temporary loss of internet connection may result in missed messages if not handled properly.
If the connection drops and is re-established, missed updates need to be resent manually.
Server Overload:

If the server cannot process messages fast enough, updates may be delayed or dropped.
Client Limitations:

Poorly implemented client-side logic might fail to process updates if the client is overwhelmed or experiences errors.
No Delivery Guarantee:

Websockets do not inherently guarantee delivery or order of messages like TCP; the application must implement its own acknowledgment and retry mechanisms if needed.
Best Practices to Ensure Reliable Sync
Implement Acknowledgments:

Use a mechanism where the client acknowledges receipt of important messages.
The server can resend unacknowledged updates.
Fallback Mechanisms:

Use a periodic HTTP poll or data sync mechanism as a backup for ensuring data consistency in case WebSocket messages are missed.
Resync on Reconnect:

When a WebSocket connection is re-established, synchronize the client with the current server state to ensure no data was lost during the disconnection.
Example: When a user reconnects, fetch the latest data via an API call to reconcile any differences.

Use Libraries with Built-in Reliability:

Libraries like Socket.IO or Phoenix Channels provide higher-level abstractions with features like automatic reconnections and message buffering.
Ping/Pong Heartbeats:

Regularly send heartbeats (ping/pong messages) to detect broken connections quickly.
Scalability and Load Balancing:

Ensure your WebSocket server is scalable and can handle reconnections gracefully. Use tools like Redis pub/sub for broadcasting updates across server instances.
Hybrid Approach:
Combine WebSockets with periodic polling or API-based syncing:

WebSockets: For real-time updates.
Polling/Resync: Periodically fetch the current state to ensure consistency (e.g., every 5 minutes or on reconnection).
Conclusion:
Websockets are safe and effective for maintaining real-time sync between the server and clients, but they need proper handling to ensure users don’t miss updates. By implementing acknowledgment, resynchronization, and fallback mechanisms, you can ensure a robust and reliable system even in cases of network or server issues.


improve this promt. I have a laravel 11 task management system and i want to add an chain module using laravel filament v3 that basically triggers a task based on certain event the table is named chains. For example first when certain action happen internally in the system like when a task is created with specific conditions like it has certain task type, create then another task or specify that is custom code that is written inside the laravel 11 app. And second most important via API that when certain endpoint is triggered also a task is created with certain condition or the same specify as before that is a custom code that wil be executed. For the api as security add only a IP whitelist field so that only those ip can trigger the endpoint (allow multiple IPs field). For the API for example a HL7 message wil be sended when an patient is admited, create for me also the custom code to process this. The idea is that the patient information liek this: $table->string('patient_id');
            $table->string('visit_id');
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('gender')->nullable();
            $table->string('birthdate')->nullable();
            $table->string('ext_id_1')->nullable();
            $table->string('campus_id')->nullable();
            $table->string('ward_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('bed_id')->nullable();
            $table->datetime('admission')->nullable();
            $table->datetime('discharge')->nullable();
are stored in the patient table. also there wil be a department table to track the departments where the patient is to track if a transfer happens also a room and bed table wil exist to track in what room and bed the patient is.  Also give me ideas of improvement if you see necessary to develope this idea.


