
When to udpate the user UI
  1. A task can be replaced or added by the task planner
  2. a User updates the task

// This is the test,
TO-DO's
  1. Change icons in filament.
  19. define the task types with Natascha
  25. What if 12 are assigned to a task how does it look in the task tabulator. i need to add ...
  26. If you remove yourself from a task and you are the only one assigned the permission are not synced and i cant still update the task.
  27. What if task has a team where user doesnt belong to or no teams, but still an admin assigned him to the task, and he removes afterwards his assignation.
  29. Improve speed of task update like Collega nodig button takes time to change.
  30. When changing something in task detail it shouldnt rerender the fields that can change because else when chaing and opening again the modal selection list is closes
  31. when adding comment update ui && show order by creation date in DESC
  32. RichText editor is showing a red line when writing in the commentaar toevoegen from activity of a task.
  33. Fix Activity and assignment forms to use the ajaxDebounce function implemented in Task TAB
  34. when a toaster is showed during the use of a Sheet it doesnt allow interaction, probably due to event point set to none somewhere
  35. create enum for observe event type for broadcast, so it is used same on the whole application.
  37. A task start_date_time what i have to filter always on so i need to add in taskplanner the current run_at to the start_date_time
  38. Set mobile variable to somewhere global?
  39. make from devounce ajax a function for use everywhere
  40. A admin can se historiek tasks, add extra filter.
  42. Activity show live icon
  43. When scrolling up, move next tab.
  44. Open document in a iframe
  48. Add a worker in laravel to allow the edit of tasks to be faster, this way i think i dont need Optimistically update the UI.

  50. If im not asigned more to task it can happen that task has to disapear from dashboard that doesnt happen at the time. to simulate assign user to a task where he is not in the team.
  52. Toegewezen if it has more than 4 pictures it gets trimmed.
  53. Implement cache for task that have to be activated so only db query is made when a new task is created, and default get task that start date time < carbon now 
 
  55. add x to delete to select instead of using null value like 'Geen selectie'
  56. Move scrollbar to right edge in sheets.
  57. try catch apart for broadcast in case it fails it doesnt trigger the catch of udpate or store as user still has fallbacks like polling to get new data. Maybe use message queuee to guarantee delivery? investigate
  58. How to know if assignees have been removed in the comment instead assigned
  59. Task that are replaced or skipped are only for the admin views. it should also have another color grey and opacity lower to indicate it is not activated. should it use the is_activate?
  60. in tablet widt the scroll vertical doesnt work
  61. Adjust the filters so it is evident a flter is activated.
  62. Add comment of replacement when a task is skipped.
  63. when clicked on the first task of niet aan mij toegewezen. the group order changes
  64. Fix photo flickering on UI updated
  65. Fix variable for dowittest call for websocket on dashboard and fix the toewijzing section for mobile
  66. Super ADMIn can select from all the teams.

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