<?php

namespace CreateProject\Controller;

use App\Service\JiraService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use CreateProject\Form\CreateProjectForm;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CreateProjectController
 */
class CreateProjectController extends Controller
{
  private $jiraService;
  private $formData;

  public function __construct(JiraService $jiraService)
  {
    $this->jiraService = $jiraService;
  }

  /**
   * Create a project and all related entities.
   *
   * @Route("/new", name="create_project_form")
   */
  public function createProject(Request $request)
  {
    $form = $this->createForm(CreateProjectForm::class);
    $form->handleRequest($request);

    $this->formData = [
      'form' => $form->getData(),
      'projects' => $this->jiraService->getAllProjects(),
      'accounts' => $this->jiraService->getAllAccounts(),
      'projectCategories' => $this->jiraService->getAllProjectCategories(),
      'allTeamsConfig' => $this->getParameter('teamconfig'),
      'user' => $this->jiraService->getCurrentUser(),
      'jira_url' => $_ENV['JIRA_URL'],
    ];

    if ($form->isSubmitted() && $form->isValid()) {
      // Do stuff on submission.

      // Set selected team config.
      foreach ($this->formData['projectCategories'] as $team) {
        if ($team->id == $this->formData['form']['team']) {
          $this->formData['selectedTeamConfig'] = $this->formData['allTeamsConfig'][$team->name];
        }
      }

      // Create project.
      $newProjectKey = $this->createJiraProject();
      if (!$newProjectKey) {
        exit('Error: No project was created.');
      }
      else {
        $project = $this->jiraService->getProject($newProjectKey);
      }

      // Check for new account.
      if ($this->formData['form']['new_account']) {
        // Add project ID if account key is municipality name.
        if(!is_numeric($this->formData['form']['new_account_key'])) {
          $this->formData['form']['new_account_key'] = str_replace(' ', '_', $this->formData['form']['new_account_key']) .  '-' . $newProjectKey;
        }

        // Check for new customer.
        if ($this->formData['form']['new_customer']) {

          // Create customer.
          $customer = $this->createJiraCustomer();

          // Set customer key from new customer.
          $this->formData['form']['new_account_customer'] = $customer->key;
        }
        else {
          foreach ($this->formData['accounts'] as $account) {
            // Get the account that was selected in form.
            // This holds the customer data we need.
            if ($account->key == $this->formData['form']['account']) {
              // Set customer key from selected customer.
              $this->formData['form']['new_account_customer'] = $account->customer->key;
            }
          }
        }

        // Create account if new account is selected.
        $account = $this->createJiraAccount();
      }
      else {
        // Set account key from selected account in form.
        $account = $this->jiraService->get('rest/tempo-accounts/1/account/key/' . $this->formData['form']['account']);
      }

      // Add project to tempo account
      $this->addProjectToAccount($project, $account);

      // Create project board
      $this->createProjectBoard($project);

      // Go to form submitted page.
      $_SESSION['form_data'] = $this->formData;
      return $this->redirectToRoute('create_project_submitted');
    }

    // The initial form build.
    return $this->render('@CreateProjectBundle/createProjectForm.html.twig', [
      'form' => $form->createView(),
      'formConfig' => json_encode(array(
        'allProjects' => $this->allProjectsByKey())
      ),
    ]);
  }

  /**
   * Receipt page displayed when a project was created.
   *
   * @Route("/submitted", name="create_project_submitted")
   */
  public function createProjectSubmitted(JiraService $jiraService, Request $request)
  {
    return $this->render('@CreateProjectBundle/createProjectSubmitted.html.twig', [
      'form_data' => $_SESSION['form_data'],
    ]);
  }

  /**
   * Create a jira customer.
   *
   * @return mixed
   *   The customer that was created or an error.
   */
  private function createJiraCustomer()
  {
    $customer = [
      'isNew' => 1,
      'name' => $this->formData['form']['new_customer_name'],
      'key' =>  $this->formData['form']['new_customer_key'],
    ];
    $response = $this->jiraService->post('rest/tempo-accounts/1/customer/', $customer);

    return $response;
  }

  /**
   * Create a Jira account.
   *
   * @return mixed
   *   The account that was created or an error.
   */
  private function createJiraAccount()
  {
    // Note! Price tables (rateTable) do not seem to work with the api at the moment. 23.05.2019
    // Even though they are included @ http://developer.tempo.io/doc/accounts/api/rest/latest/
    $account = [
      'name' => $this->formData['form']['new_account_name'],
      'key' => $this->formData['form']['new_account_key'],
      'status' => 'OPEN',
      'category' => array(
        'key' => 'DRIFT',
      ),
      'customer' => array(
        'key' => $this->formData['form']['new_account_customer'],
      ),
      'contact' => array(
        'username' => $this->formData['form']['new_account_contact']
      ),
      'lead' => array(
        'username' => $_ENV['CPB_ACCOUNT_MANAGER']
      ),
    ];
    $response = $this->jiraService->post('rest/tempo-accounts/1/account/', $account);
    return $response;
  }

  /**
   * Create a jira project.
   *
   * @return null|string
   *   A project key if the project was created, else NULL.
   */
  private function createJiraProject()
  {
    // @todo:
    // Cleanup of workflow, issuetypes and screen. (Make robot @Anders)
    $projectKey = strtoupper($this->formData['form']['project_key']);
    $project = [
      'key' => $projectKey,
      'name' => $this->formData['form']['project_name'],
      'lead' => $this->formData['selectedTeamConfig']['team_lead'],
      'typeKey' => 'software',
      'templateKey' => 'com.pyxis.greenhopper.jira:basic-software-development-template', //https://community.atlassian.com/t5/Answers-Developer-Questions/JIRA-API-7-1-0-Create-Project/qaq-p/551444
      'description' => $this->formData['form']['description'] . ' - Oprettet af: ' . $this->formData["user"]->displayName,
      'assigneeType' => 'UNASSIGNED',
      'categoryId' => $this->formData['selectedTeamConfig']['project_category'],
      'issueTypeScheme' => $this->formData['selectedTeamConfig']['issue_type_scheme'],
      'workflowScheme' => $this->formData['selectedTeamConfig']['workflow_scheme'],
      'issueTypeScreenScheme' => $this->formData['selectedTeamConfig']['issue_type_screen_scheme'],
      'permissionScheme' => $this->formData['selectedTeamConfig']['permission_scheme'],
    ];

    $response = $this->jiraService->post('rest/extender/1.0/project/createProject', $project);

    return ($response->message == 'project was created') ? $projectKey : NULL;
  }

  /**
   * Create a project link to account.
   *
   * @param $project
   *  The project that was created on form submit.
   * @param $account
   *  The account that was created on form submit.
   */
  private function addProjectToAccount($project, $account)
  {
    $link = [
      'scopeType' => 'PROJECT',
      'defaultAccount' => 'true',
      'linkType' => 'MANUAL',
      'key' => $project->key,
      'accountId' => $account->id,
      'scope' => $project->id,
    ];
    $response = $this->jiraService->post('rest/tempo-accounts/1/link/', $link);
  }

  /**
   * Create a project board and an issue filter.
   *
   * @param $project
   *   The project that was created on form submit.
   */
  private function createProjectBoard($project)
  {
    // If no template is configured dont create a board.
    if (empty($this->formData['selectedTeamConfig']['board_template'])) {
      return;
    }

    // Create project filter.
    $filter = [
      'name' => 'Filter for Project: ' . $project->name,
      'description' => 'Project filter for '. $project->name,
      'jql' => 'project = ' . $project->key . ' ORDER BY Rank ASC',
      'favourite' => false,
      'editable' => false,
    ];

    $filterResponse = $this->jiraService->post('/rest/api/2/filter', $filter);

    // Share project filter with project members.
    $projectShare = [
      "type" => "project",
      "projectId" => $project->id,
      "view" => true,
      "edit" => false
    ];

    $projectShareResponse = $this->jiraService->post('/rest/api/2/filter/' . $filterResponse->id . '/permission', $projectShare);


    // Create board with project filter.
    $board = [
      'name' => 'Project: ' . $project->name,
      'type' => $this->formData['selectedTeamConfig']['board_template']['type'],
      'filterId' => $filterResponse->id,
    ];

    $boardResponse = $this->jiraService->post('/rest/agile/1.0/board', $board);
  }

  /**
   * Create array of all project names and their keys.
   *
   * @return array
   *   All projects indexed by key.
   */
  private function allProjectsByKey()
  {
    $projects = [];
    $allProjects = $this->jiraService->getAllProjects();
    foreach ($allProjects as $project) {
      $projects[$project->key] = array(
        'key' => $project->key,
        'name' => $project->name
      );
    }

    return $projects;
  }
}
