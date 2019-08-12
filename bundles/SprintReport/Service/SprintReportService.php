<?php

namespace SprintReport\Service;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Service\JiraService;

class SprintReportService extends JiraService
{
    /**
     * Constructor.
     * @param $tokenStorage
     * @param $customerKey
     * @param $pemPath
     * @param $jiraUrl
     */
    public function __construct(
        $tokenStorage,
        $customerKey,
        $pemPath,
        $jiraUrl
    ) {
        parent::__construct($jiraUrl, $tokenStorage, $customerKey, $pemPath);
    }

    /**
     * Get all future sprints.
     *
     * @return array
     */
    public function getFutureSprints() {
        $boardId = getenv('JIRA_DEFAULT_BOARD');
        $sprints = [];

        $start = 0;
        while (true) {
            $result = $this->get('/rest/agile/1.0/board/' . $boardId . '/sprint?startAt='.$start.'&maxResults=50&state=future,active');
            $sprints = array_merge($sprints, $result->values);

            if ($result->isLast) {
                break;
            }

            $start = $start + 50;
        }

        return $sprints;
    }

    /**
     * Get all issues for sprint.
     *
     * @param $sprintId
     * @return array
     */
    public function getIssuesInSprint($sprintId) {
        $boardId = getenv('JIRA_DEFAULT_BOARD');
        $issues = [];
        $fields = implode(
            ',',
            [
                'timetracking',
                'summary',
                'status',
                'assignee',
                'project',
            ]
        );

        $start = 0;
        while (true) {
            $result = $this->get('/rest/agile/1.0/board/'.$boardId.'/sprint/'.$sprintId.'/issue?startAt=' . $start . '&fields='.$fields);
            $issues = array_merge($issues, $result->issues);

            $start = $start + 50;

            if ($start > $result->total) {
                break;
            }
        }

        return $issues;
    }

    /**
     * Get the sprint report for a given version.
     *
     * @param integer $vid version id.
     * @return array
     */
    public function getSprintReport($vid) {
        // Get version, project, customFields from Jira.
        $version = $this->get('/rest/api/2/version/'.$vid);
        $project = $this->getProject($version->projectId);
        $customFields = $this->get('/rest/api/2/field');

        // Find customField definitions.
        $customFieldEpicLink = array_search(
            'Epic Link',
            array_column($customFields, 'name')
        );
        if ($customFieldEpicLink == false) {
            throw new HttpException(
                500,
                'Epic Link custom field does not exist'
            );
        }
        $customFieldEpicLink = $customFields[$customFieldEpicLink];

        $customFieldSprint = array_search(
            'Sprint',
            array_column($customFields, 'name')
        );
        if ($customFieldSprint == false) {
            throw new HttpException(
                500,
                'Sprint custom field does not exist'
            );
        }
        $customFieldSprint = $customFields[$customFieldSprint];

        // Get active sprint.
        $boardId = getenv('JIRA_DEFAULT_BOARD');
        $activeSprint = $this->get(
            '/rest/agile/1.0/board/'.$boardId.'/sprint', [
                'state' => 'active',
            ]
        );
        $activeSprint = count(
            $activeSprint->values
        ) > 0 ? $activeSprint->values[0] : null;

        // Get all issues for version.
        $issues = [];
        $startAt = 0;
        $fields = implode(
            ',',
            [
                'timetracking',
                'worklog',
                'timespent',
                'timeoriginalestimate',
                'summary',
                'assignee',
                'status',
                'resolutionDate',
                $customFieldEpicLink->id,
                $customFieldSprint->id,
            ]
        );

        while (true) {
            // @TODO: Move to separate call.
            $results = $this->get(
                '/rest/api/2/search',
                [
                    'jql' => 'fixVersion='.$vid,
                    'project' => $version->projectId,
                    'maxResults' => 50,
                    'fields' => $fields,
                    'startAt' => $startAt,
                ]
            );
            $issues = array_merge($issues, $results->issues);

            $startAt = $startAt + 50;

            if ($results->total < $startAt) {
                break;
            }
        }

        $epics = [];
        $epics['NoEpic'] = (object)[
            'id' => null,
            'name' => 'No epic',
            'spentSum' => 0,
            'remainingSum' => 0,
            'originalEstimateSum' => 0,
            'plannedWorkSum' => 0,
        ];
        $sprints = [];
        $spentSum = 0;
        $remainingSum = 0;

        // Extract sprint and epics from agile custom field.
        foreach ($issues as $issue) {
            $issue->sprints = [];

            $p = 1;

            // Get sprints for issue.
            foreach ($issue->fields->{$customFieldSprint->id} as $sprintString) {
                $replace = preg_replace(
                    ['/.*\[/', '/].*/'],
                    '',
                    $sprintString
                );
                $fields = explode(',', $replace);

                $sprint = [];
                foreach ($fields as $field) {
                    $split = explode('=', $field);

                    $sprint[$split[0]] = $split[1];
                }

                // Set shortName
                $sprint['shortName'] = str_replace('Sprint ', '', $sprint['name']);

                $issue->sprints[] = (object) $sprint;

                if (!isset($sprints[$sprint['id']])) {
                    $sprints[$sprint['id']] = (object) $sprint;
                }

                if ($sprint['state'] == 'ACTIVE' || $sprint['state'] == 'FUTURE') {
                    $issue->assignedToSprint = (object) $sprint;
                }
            }

            // Get issue epic.
            if (isset($issue->fields->{$customFieldEpicLink->id})) {
                if (!isset($epics[$issue->fields->{$customFieldEpicLink->id}])) {
                    $epic = $epics[$issue->fields->{$customFieldEpicLink->id}] = $this->get(
                        'rest/agile/1.0/epic/'.$issue->fields->{$customFieldEpicLink->id}
                    );

                    $epic->spentSum = 0;
                    $epic->remainingSum = 0;
                    $epic->originalEstimateSum = 0;
                    $epic->plannedWorkSum = 0;
                }
                $issue->epic = $epics[$issue->fields->{$customFieldEpicLink->id}];
            }
            else {
                $issue->epic = $epics['NoEpic'];
            }

            // Gather worklogs for sprints/epics.
            if (!isset($issue->epic->worklogs)) {
                $issue->epic->worklogs = [];
            }
            if (!isset($issue->epic->loggedWork)) {
                $issue->epic->loggedWork = [];
            }
            foreach ($issue->fields->worklog->worklogs as $worklog) {
                $workLogStarted = strtotime($worklog->started);
                $sprint = array_filter($sprints, function($k) use ($workLogStarted) {
                    return
                        strtotime($k->startDate) <= $workLogStarted &&
                        (isset($k->completeDate) ? strtotime($k->completeDate) : strtotime($k->endDate)) > $workLogStarted;
                });

                if (!empty($sprint)) {
                    $sprint = array_pop($sprint);

                    if (!isset($issue->epic->worklogs[$sprint->id])) {
                        $issue->epic->worklogs[$sprint->id] = [];
                    }
                    $issue->epic->worklogs[$sprint->id][] = $worklog;

                    $issue->epic->loggedWork[$sprint->id] = (isset($issue->epic->loggedWork[$sprint->id]) ? $issue->epic->loggedWork[$sprint->id] : 0) + $worklog->timeSpentSeconds;
                }
                else {
                    if (!isset($issue->epic->worklogs['NoSprint'])) {
                        $issue->epic->worklogs['NoSprint'] = [];
                    }
                    $issue->epic->worklogs['NoSprint'][] = $worklog;

                    $issue->epic->loggedWork['NoSprint'] = (isset($issue->epic->loggedWork['NoSprint']) ? $issue->epic->loggedWork['NoSprint'] : 0) + $worklog->timeSpentSeconds;
                }
            }

            // Accumulate spentSum.
            $spentSum = $spentSum + $issue->fields->timespent;
            $issue->epic->spentSum = $issue->epic->spentSum + $issue->fields->timespent;

            // Accumulate remainingSum.
            if (!$issue->fields->status->name != 'Done' && isset($issue->fields->timetracking->remainingEstimateSeconds)) {
                $remainingSum = $remainingSum + $issue->fields->timetracking->remainingEstimateSeconds;

                $issue->epic->remainingSum = $issue->epic->remainingSum + $issue->fields->timetracking->remainingEstimateSeconds;

                if (!empty($issue->assignedToSprint)) {
                    $sprint = $issue->assignedToSprint;
                    $issue->epic->remainingWork[$sprint->id] = (isset($issue->epic->remainingWork[$sprint->id]) ? $issue->epic->remainingWork[$sprint->id] : 0) + $issue->fields->timetracking->remainingEstimateSeconds;
                    $issue->epic->plannedWorkSum = $issue->epic->plannedWorkSum + $issue->fields->timetracking->remainingEstimateSeconds;
                }
            }

            // Accumulate originalEstimateSum.
            if (isset($issue->fields->timeoriginalestimate)) {
                $issue->epic->originalEstimateSum = $issue->epic->originalEstimateSum + $issue->fields->timeoriginalestimate;
            }
        }

        // Sort sprints by key.
        ksort($sprints);

        // Calculate spent, remaining hours.
        $spentHours = $spentSum / 3600;
        $remainingHours = $remainingSum / 3600;

        return [
            'version' => $version,
            'project' => $project,
            'issues' => $issues,
            'activeSprint' => $activeSprint,
            'sprints' => $sprints,
            'spentSum' => $spentSum,
            'spentHours' => $spentHours,
            'remainingHours' => $remainingHours,
            'epics' => $epics,
        ];
    }
}
