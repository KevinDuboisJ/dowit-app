
SET SQL_SAFE_UPDATES = 0;

------------------------------------------

ALTER TABLE comments
ADD COLUMN event ENUM(
    'task_created',
    'task_started',
    'task_updated',
    'task_rejected',
    'task_completed',
    'task_help_requested',
    'task_help_given',
    'announcement'
) AFTER id;

------------------------------------------

ALTER TABLE comments
CHANGE COLUMN needs_help help_requested TINYINT(1);

ALTER TABLE tasks
CHANGE COLUMN needs_help help_requested TINYINT(1);

------------------------------------------

UPDATE comments
SET metadata = JSON_REMOVE(
    JSON_SET(
        metadata,
        '$.changed_keys.help_requested',
        JSON_EXTRACT(metadata, '$.changed_keys.needs_help')
    ),
    '$.changed_keys.needs_help'
)
WHERE JSON_EXTRACT(metadata, '$.changed_keys.needs_help') IS NOT NULL;

------------------------------------------

UPDATE comments
SET metadata = JSON_REMOVE(
    JSON_SET(
        metadata,
        '$.changes',
        JSON_EXTRACT(metadata, '$.changed_keys')
    ),
    '$.changed_keys'
)
WHERE JSON_EXTRACT(metadata, '$.changed_keys') IS NOT NULL;

------------------------------------------

/*Set announcements*/

UPDATE comments SET event = 'announcement' WHERE start_date IS NOT NULL;

------------------------------------------

/*Set completed*/

    UPDATE comments
    SET event = 'task_completed'
    WHERE JSON_EXTRACT(metadata, '$.changes.status') = 'Completed';

------------------------------------------

UPDATE comments c
LEFT JOIN (
    SELECT 
        c1.id,
        CONCAT(
            '[',
            GROUP_CONCAT(
                JSON_OBJECT(
                    'id', NULL,
                    'value', JSON_UNQUOTE(JSON_EXTRACT(c1.metadata, CONCAT('$.changes.assignees[', n.n, ']')))
                )
                ORDER BY n.n
                SEPARATOR ','
            ),
            ']'
        ) AS added_assignees
    FROM comments c1
    JOIN (
        SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    ) n
        ON n.n < JSON_LENGTH(JSON_EXTRACT(c1.metadata, '$.changes.assignees'))
    WHERE JSON_TYPE(JSON_EXTRACT(c1.metadata, '$.changes.assignees')) = 'ARRAY'
    GROUP BY c1.id
) a ON a.id = c.id
LEFT JOIN (
    SELECT 
        c1.id,
        CONCAT(
            '[',
            GROUP_CONCAT(
                JSON_OBJECT(
                    'id', NULL,
                    'value', JSON_UNQUOTE(JSON_EXTRACT(c1.metadata, CONCAT('$.changes.unassignees[', n.n, ']')))
                )
                ORDER BY n.n
                SEPARATOR ','
            ),
            ']'
        ) AS removed_assignees
    FROM comments c1
    JOIN (
        SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    ) n
        ON n.n < JSON_LENGTH(JSON_EXTRACT(c1.metadata, '$.changes.unassignees'))
    WHERE JSON_TYPE(JSON_EXTRACT(c1.metadata, '$.changes.unassignees')) = 'ARRAY'
    GROUP BY c1.id
) r ON r.id = c.id
SET c.metadata = JSON_REMOVE(
    JSON_SET(
        c.metadata,
        '$.changes.assignees',
        JSON_OBJECT(
            'added',
            COALESCE(JSON_EXTRACT(a.added_assignees, '$'), JSON_ARRAY()),
            'removed',
            COALESCE(JSON_EXTRACT(r.removed_assignees, '$'), JSON_ARRAY())
        )
    ),
    '$.changes.unassignees'
)
WHERE
    JSON_TYPE(JSON_EXTRACT(c.metadata, '$.changes.assignees')) = 'ARRAY'
    OR JSON_TYPE(JSON_EXTRACT(c.metadata, '$.changes.unassignees')) = 'ARRAY';

------------------------------------------

UPDATE comments
SET metadata = JSON_SET(
    metadata,
    '$.changes.status',
    JSON_OBJECT(
        'to',
        JSON_OBJECT(
            'id',
            CASE JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.changes.status'))
                WHEN 'Added' THEN 1
                WHEN 'Replaced' THEN 2
                WHEN 'Scheduled' THEN 3
                WHEN 'InProgress' THEN 4
                WHEN 'Waiting' THEN 5
                WHEN 'Completed' THEN 6
                WHEN 'Rejected' THEN 7
                WHEN 'FollowUpViaEmail' THEN 8
                WHEN 'WaitingForDelivery' THEN 9
                WHEN 'Postponed' THEN 10
                WHEN 'Paused' THEN 11
                WHEN 'Skipped' THEN 12
                ELSE NULL
            END,
            'value', JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.changes.status'))
        ),
        'from',
        JSON_OBJECT(
            'id', NULL,
            'value', NULL
        )
    )
)
WHERE JSON_TYPE(JSON_EXTRACT(metadata, '$.changes.status')) = 'STRING';

------------------------------------------

/*Set the event task_help_requested based on the metadata.*/
    UPDATE comments
    SET event = 'task_help_requested'
    WHERE JSON_EXTRACT(metadata, '$.changes.help_requested') = true;

------------------------------------------

UPDATE comments
SET metadata = JSON_SET(
    metadata,
    '$.changes.help_requested',
    JSON_OBJECT(
        'to', JSON_OBJECT(
            'value',
            IF(
                JSON_EXTRACT(metadata, '$.changes.help_requested') = CAST(true AS JSON)
                OR JSON_EXTRACT(metadata, '$.changes.help_requested') = CAST(1 AS JSON),
                CAST(true AS JSON),
                CAST(false AS JSON)
            )
        ),
        'from', JSON_OBJECT(
            'value',
            IF(
                JSON_EXTRACT(metadata, '$.changes.help_requested') = CAST(true AS JSON)
                OR JSON_EXTRACT(metadata, '$.changes.help_requested') = CAST(1 AS JSON),
                CAST(false AS JSON),
                CAST(true AS JSON)
            )
        )
    )
)
WHERE JSON_TYPE(JSON_EXTRACT(metadata, '$.changes.help_requested')) IN ('BOOLEAN', 'INTEGER');

------------------------------------------

UPDATE comments
SET metadata = JSON_SET(
    metadata,
    '$.changes.priority',
    JSON_OBJECT(
        'id',
        CASE
            WHEN JSON_TYPE(JSON_EXTRACT(metadata, '$.changes.priority')) = 'NULL' THEN NULL
            WHEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.changes.priority')) = 'Low' THEN 'low'
            WHEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.changes.priority')) = 'Medium' THEN 'medium'
            WHEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.changes.priority')) = 'High' THEN 'high'
            ELSE LOWER(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.changes.priority')))
        END,
        'value',
        CASE
            WHEN JSON_TYPE(JSON_EXTRACT(metadata, '$.changes.priority')) = 'NULL' THEN NULL
            ELSE JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.changes.priority'))
        END
    )
)
WHERE JSON_TYPE(JSON_EXTRACT(metadata, '$.changes.priority')) IN ('STRING', 'NULL');

------------------------------------------


/*Set the event task_created based on the created_at of comment and task. OR content*/

    UPDATE comments c
    JOIN tasks t ON t.id = c.task_id
    SET c.event = 'task_created'
    WHERE c.created_at = t.created_at;

    -----------------------------------------

    UPDATE comments SET event = 'task_created' WHERE content = 'Taak aangemaakt';


/*Change task_statuses → WaitingForSomeone to Waiting*/

UPDATE task_statuses
SET name = 'Waiting'
WHERE name = 'WaitingForSomeone';


/*Set "Status werd automatisch omgezet naar Toegevoegd" */

UPDATE comments
    SET event = 'task_updated'
    WHERE content = 'Status werd automatisch omgezet naar Toegevoegd';

/*Set the event task_help_given based on the metadata. */

UPDATE comments
SET event = 'task_help_given'
WHERE JSON_LENGTH(JSON_EXTRACT(metadata, '$.changes.assignees.added')) = 1 
AND JSON_EXTRACT(metadata, '$.changes.help_requested.to.value') = false;


/* set task_started event */

UPDATE comments
SET event = 'task_started'
WHERE status_id = 4 AND IFNULL(JSON_LENGTH(JSON_EXTRACT(metadata, '$.changes.assignees.added')), 0) = 1;

/* Change status value WaitingForSomeone → Waiting */
UPDATE comments
SET metadata = JSON_SET(
    metadata,
    '$.changes.status.to.value',
    'Waiting'
)
WHERE JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.changes.status.to.value')) = 'WaitingForSomeone';

/* Set by default all help to false */

ALTER TABLE tasks
MODIFY help_requested TINYINT(1) NOT NULL DEFAULT 0;

UPDATE comments
SET help_requested = 0
WHERE help_requested IS NULL;

UPDATE tasks
SET help_requested = false
WHERE help_requested IS NULL;


/* Delete shadow comments */
DELETE FROM comments
WHERE event IS NULL
  AND (content IS NULL OR content = '')
  AND start_date IS NULL
  AND status_id IS NULL
  AND metadata IS NULL;

/* Changes the content to Vervangen */
  UPDATE comments c
LEFT JOIN tasks t ON c.task_id = t.id
SET c.content = 'Status werd automatisch omgezet naar Vervangen'
WHERE c.content = 'Status werd automatisch omgezet naar Toegevoegd'
  AND t.status_id = 2;

/* Changes comments content for old vervangen comments */
UPDATE comments
SET content = 'Status werd automatisch omgezet naar Vervangen'
WHERE content = 'Taak is vervangen';

/* Set task_updated where event is null */
UPDATE comments
SET event = 'task_updated'
WHERE event IS NULL;

/* Set task_rejected where task status is rejected */
UPDATE comments
SET event = 'task_rejected'
WHERE status_id = 7;