<?php

namespace Drupal\io_utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\io_utils\Services\SearchAndReplace;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for regex search and replace.
 */
class SearchReplaceForm extends FormBase
{

  /**
   * The search and replace service.
   *
   * @var \Drupal\io_utils\Services\SearchAndReplace
   */
  protected $searchReplaceService;
  protected $itemsPerPage;
  /**
   * Constructs a new SearchReplaceForm.
   *
   * @param \Drupal\io_utils\Services\SearchAndReplace $search_replace_service
   *   The search and replace service.
   */
  public function __construct(SearchAndReplace $search_replace_service)
  {
    $this->searchReplaceService = $search_replace_service;
    $this->itemsPerPage = 10; // Default value, can be changed later if needed
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'io_utils_search_replace_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    $service = $container->get('io_utils.search_and_replace');
    if (!$service instanceof SearchAndReplace) {
      throw new \InvalidArgumentException('Service is not an instance of SearchAndReplace');
    }
    return new static($service);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#theme'] = 'io_utils_search_replace_form';
    $form['#attached']['library'][] = 'io_utils/form_styles';

    $request = \Drupal::request();
    $replacement_done = $request->query->get('replacement_done');

    if ($replacement_done) {
      // Show only the Reset button after replacement
      $form['actions'] = [
        '#type' => 'actions',
        'reset' => [
          '#type' => 'submit',
          '#value' => $this->t('Reset'),
          '#submit' => ['::resetForm'],
          '#limit_validation_errors' => [],
        ],
      ];

      return $form;
    }

    // Get the current page from the request
    $page = $request->query->getInt('page', 0);

    // Preserve form values
    $user_input = $form_state->getUserInput();
    $last_search = $form_state->get('last_search') ?: [];

    // Check if we have GET parameters
    $search = $request->query->get('search') ?? $user_input['search'] ?? $last_search['search'] ?? '';
    $replace = $request->query->get('replace') ?? $user_input['replace'] ?? $last_search['replace'] ?? '';
    $limit_to_fields = $request->query->get('limit_to_fields') ?? $user_input['limit_to_fields'] ?? $last_search['limit_to_fields'] ?? '';
    $moderation_states = $request->query->get('moderation_states') ?? $user_input['moderation_states'] ?? $last_search['moderation_states'] ?? '';
    $total_count = $request->query->get('total_count') ?? $form_state->get(['last_search', 'total_count']) ?? 0;

    // Determine if a search has been performed
    $search_performed = !empty($last_search);

    // Determine if we're in replace mode
    $replace_mode = $request->query->get('replace_mode') === '1' || (!empty($replace) && !empty($search));
    $step = $request->query->get('step') ?? $form_state->get('step');

    // If we have search parameters in the URL, consider it as a performed search
    if ($request->query->has('search')) {
      $search_performed = TRUE;
      // Store the search parameters in the form state
      $form_state->set('last_search', [
        'search' => $search,
        'replace' => $replace,
        'limit_to_fields' => $limit_to_fields,
        'moderation_states' => $moderation_states,
        'total_count' => $total_count,
      ]);
      $form_state->set('step', $step);
    }

    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Pattern'),
      '#description' => $this->t('Enter RegEx term to Search for. (Example "/Corporation/")'),
      '#required' => TRUE,
      '#default_value' => $search,
      '#disabled' => $search_performed && $total_count > 0,
    ];

    $form['replace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replace With'),
      '#description' => $this->t('Enter plain text term to replace the search term with (Example: "Corporation, LLC")'),
      '#required' => FALSE,
      '#default_value' => $replace,
      '#disabled' => $search_performed && $total_count > 0,
    ];

    $form['limit_to_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit To Field(s)'),
      '#description' => $this->t('Enter field names separated by commas. Leave empty to search all fields.'),
      '#required' => FALSE,
      '#default_value' => $limit_to_fields,
      '#disabled' => $search_performed && $total_count > 0,
    ];

    $form['moderation_states'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Moderation State(s)'),
      '#description' => $this->t('Enter moderation states separated by commas. Leave empty to search published only.'),
      '#required' => FALSE,
      '#default_value' => $moderation_states,
      '#disabled' => $search_performed && $total_count > 0,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => ($replace_mode && $step === 'confirm')
          ? $this->t('Continue with Replacement of @count Objects?', ['@count' => $total_count])
          : $this->t('Execute'),
        '#disabled' => ($search_performed && !$replace_mode) && $total_count > 0,
      ],
    ];

    // Add Reset button if a search has been performed
    if ($search_performed) {
      $form['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
        '#limit_validation_errors' => [],
      ];
    }

    // Add results container
    $form['results'] = [
      '#type' => 'details',
      '#title' => $this->t('Results'),
      '#open' => TRUE,
    ];

    // If we have a search term, perform the search
    if (!empty($search)) {
      $results = $this->performSearch($search, $limit_to_fields, $moderation_states, $this->itemsPerPage, $page);

      // Update total_count if it's not set or if it's different from the search results
      if ($total_count == 0 || $total_count != $results['count']) {
        $total_count = $results['count'];
        $form_state->set(['last_search', 'total_count'], $total_count);
      }

      // Calculate the range of items being displayed
      $total_items = $results['count'];
      $start_item = ($page * $this->itemsPerPage) + 1;
      $end_item = min($start_item + $this->itemsPerPage - 1, $total_items);

      $form['results']['summary'] = [
        '#markup' => $total_items > 0
          ? $this->t('Viewing Found Objects @start-@end (Total: @total)', [
            '@start' => $start_item,
            '@end' => $end_item,
            '@total' => $total_items,
          ])
          : $this->t('@total Matching Objects Found', ['@total' => $total_items]),
        '#prefix' => '<div class="search-results-summary">',
        '#suffix' => '</div>',
      ];

      $form['results']['output'] = [
        '#type' => 'textarea',
        '#value' => $results['output'],
        '#rows' => $this->itemsPerPage,
        '#disabled' => TRUE,
      ];

      // Force the pager to always show
      $total_pages = max(1, ceil($results['count'] / $this->itemsPerPage));
      \Drupal::service('pager.manager')->createPager($results['count'], $this->itemsPerPage);

      $form['results']['pager'] = [
        '#type' => 'pager',
        '#quantity' => $total_pages,
        '#parameters' => [
          'search' => $search,
          'replace' => $replace,
          'limit_to_fields' => $limit_to_fields,
          'moderation_states' => $moderation_states,
          'total_count' => $total_count,
          'replace_mode' => $replace_mode ? '1' : null,
          'step' => $step,
        ],
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search = $form_state->getValue('search');
    $replace = $form_state->getValue('replace');
    $limit_to_fields = $form_state->getValue('limit_to_fields');
    $moderation_states = $form_state->getValue('moderation_states');

    try {
      // Process the inputs to ensure they are passed correctly
      $limit_to_fields_array = !empty($limit_to_fields) ? array_map('trim', explode(',', $limit_to_fields)) : [];
      $moderation_states_array = !empty($moderation_states) ? array_map('trim', explode(',', $moderation_states)) : [];

      // Get the current page from the pager
      $page = \Drupal::request()->query->getInt('page', 0);

      // Set items per page
      $items_per_page = $this->itemsPerPage;

      $replace_mode = !empty($replace) && !empty($form_state->get('last_search'));
      $step = $form_state->get('step');

      if ($replace_mode && $step === 'confirm') {
        // Perform the replacement
        $results = $this->searchReplaceService->replaceByRegex($search, $replace, $limit_to_fields_array, $moderation_states_array, $items_per_page, $page);
        $replaced_count = $results['count'] ?? 0;

        // Set the status message
        $this->messenger()->addStatus($this->t('Replaced @count occurrences.', ['@count' => $replaced_count]));

        // Redirect to the same page with replacement_done flag
        $form_state->setRedirect('io_utils.search_replace', [], [
          'query' => [
            'replacement_done' => '1',
          ],
        ]);

        return;
      } else {
        // Perform the search
        $results = $this->searchReplaceService->findByRegex($search, $limit_to_fields_array, $moderation_states_array, $items_per_page, $page);
        $this->processResults($results, $form_state, 'Found', $items_per_page);
      }

      // Ensure we have the correct total count
      $total_count = $results['count'] ?? 0;

      if (!empty($replace) && $total_count > 0) {
        $step = 'confirm';
        $form_state->set('step', $step);
      }

      // Store the search parameters in the form state
      $form_state->set('last_search', [
        'search' => $search,
        'replace' => $replace,
        'limit_to_fields' => $limit_to_fields,
        'moderation_states' => $moderation_states,
        'total_count' => $total_count,
      ]);

      // Redirect to the same page with GET parameters
      $form_state->setRedirect('io_utils.search_replace', [], [
        'query' => [
          'search' => $search,
          'replace' => $replace,
          'limit_to_fields' => $limit_to_fields,
          'moderation_states' => $moderation_states,
          'page' => $page,
          'replace_mode' => $replace_mode ? '1' : null,
          'total_count' => $total_count,
          'step' => $step,
        ],
      ]);

      // Ensure the form is rebuilt to show updated results and pager
      $form_state->setRebuild(TRUE);

    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred during the operation: @error', ['@error' => $e->getMessage()]));
      $form_state->set('results', 'Error occurred during operation.');
    }
  }



  public function confirmSubmit(array &$form, FormStateInterface $form_state)
  {
    $search = $form_state->get('search');
    $replace = $form_state->get('replace');
    $limit_to_fields = $form_state->get('limit_to_fields');
    $moderation_states = $form_state->get('moderation_states');
    $items_per_page = $form_state->get('items_per_page');

    try {
      $limit_to_fields_array = !empty($limit_to_fields) ? array_map('trim', explode(',', $limit_to_fields)) : [];
      $moderation_states_array = !empty($moderation_states) ? array_map('trim', explode(',', $moderation_states)) : [];

      $results = $this->searchReplaceService->replaceByRegex($search, $replace, $limit_to_fields_array, $moderation_states_array, $items_per_page);
      $this->processResults($results, $form_state, 'Replaced', $items_per_page);

      $this->messenger()->addStatus($this->t('Replacement completed successfully.'));
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred during the replace operation: @error', ['@error' => $e->getMessage()]));
      $form_state->set('results', 'Error occurred during replace operation.');
    }

    $form_state->set('step', 'search');
    $form_state->setRebuild(TRUE);
  }


  /**
   * Submit handler for the cancel action.
   */
  public function cancelSubmit(array &$form, FormStateInterface $form_state)
  {
    $this->messenger()->addMessage($this->t('Replace operation cancelled.'));
    $form_state->set('step', 'search');
    $form_state->set('results', ''); // Clear the results
    $form_state->setRebuild(TRUE);
  }

  /**
   * Helper function to process and display results.
   */
  private function processResults(array $results, FormStateInterface $form_state, string $action, int $items_per_page) {
    $total_items = $results['count'];

    $output = "";
    foreach ($results['matches'] as $match) {
      $output .= "URL: {$match['url']}\n";
      $output .= "Type: {$match['type']}\n";
      $output .= "Title: {$match['title']}\n";
      $output .= "Published: " . ($match['published'] ? 'Yes' : 'No') . "\n";
      if (isset($match['moderation_state'])) {
        $output .= "Moderation State: {$match['moderation_state']}\n";
      }
      $output .= "Locations:\n" . implode("\n", $match['locations']) . "\n\n";
    }

    if (isset($results['unsupported_types'])) {
      $output .= "Unsupported types: " . implode(", ", $results['unsupported_types']) . "\n";
    }

    // Set the processed results in the form state
    $form_state->set('results', $output);

    // Initialize the pager
    $pager = \Drupal::service('pager.manager')->createPager($total_items, $items_per_page);
    $current_page = $pager->getCurrentPage();

    // Add pager variables to the form state for use in the form build
    $form_state->set('pager_total', ceil($total_items / $items_per_page));
    $form_state->set('pager_page_count', $current_page + 1);
    $form_state->set('pager_total_items', $total_items);
  }

  protected function performSearch($search, $limit_to_fields, $moderation_states, $limit, $page) {
    $limit_to_fields_array = !empty($limit_to_fields) ? array_map('trim', explode(',', $limit_to_fields)) : [];
    $moderation_states_array = !empty($moderation_states) ? array_map('trim', explode(',', $moderation_states)) : [];

    $results = $this->searchReplaceService->findByRegex($search, $limit_to_fields_array, $moderation_states_array, $limit, $page);
    $output = "";

    foreach ($results['matches'] as $match) {
      $output .= "URL: {$match['url']}\n";
      $output .= "Type: {$match['type']}\n";
      $output .= "Title: {$match['title']}\n";
      $output .= "Published: " . ($match['published'] ? 'Yes' : 'No') . "\n";
      if (isset($match['moderation_state'])) {
        $output .= "Moderation State: {$match['moderation_state']}\n";
      }
      $output .= "Locations:\n" . implode("\n", $match['locations']) . "\n\n";
    }

    return [
      'output' => $output,
      'count' => $results['count'],
    ];
  }

  public function resetForm(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('io_utils.search_replace');
  }
}

