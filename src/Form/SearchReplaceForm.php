<?php

namespace Drupal\io_utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\io_utils\Services\SearchAndReplace;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Form for regex search and replace.
 */
class SearchReplaceForm extends FormBase
{
  const ITEMS_PER_PAGE = 10;

  protected $searchAndReplaceService;

  /**
   * Constructs a new SearchReplaceForm.
   *
   * @param \Drupal\io_utils\Services\SearchAndReplace $searchAndReplaceService
   *   The search and replace service.
   */
  public function __construct(SearchAndReplace $searchAndReplaceService) {
    $this->searchAndReplaceService = $searchAndReplaceService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $searchAndReplaceService = $container->get('io_utils.search_and_replace');

    if (!$searchAndReplaceService instanceof SearchAndReplace) {
      throw new \InvalidArgumentException('Service is not an instance of SearchAndReplace');
    }

    return new static($searchAndReplaceService);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'io_utils_search_replace_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $urlGenerator = \Drupal::service('url_generator');
    $form['#theme'] = 'io_utils_search_replace_form';
    $form['#attached']['library'][] = 'io_utils/form_styles';
    $results = $form_state->get('search_results');
    $has_matches = ($results !== null) && ($results['count'] > 0);
    $replacement_done = $form_state->get('replacement_done');

    // Display search summary (conditional on having already done a search)
    $form['help_link'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="help-link-right">',
      '#suffix' => '</div>',
      '#markup' => $this->t('<a href="@help_url" target="_blank">Tool Help</a>', [
        '@help_url' => Url::fromRoute('io_utils.help')->toString(),
      ]),
      '#access' => !($replacement_done),
    ];

    $form['search_summary'] = [
      '#type' => 'markup',
      '#markup' => $this->generateSearchSummary($form_state),
      '#prefix' => '<div class="search-summary">',
      '#suffix' => '</div>',
      '#access' => $has_matches,
    ];

    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#description' => $this->t('Enter your search term (must be valid RegEx, see Tool Help link at upper right if needed)'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('search', ''),
      '#access' => !($has_matches),
    ];

    $form['replace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replace With (optional)'),
      '#description' => $this->t('What to replace with (Plain Text or RegEx, see Tool Help link at upper right if needed)'),
      '#required' => FALSE,
      '#default_value' => $form_state->getValue('replace', ''),
      '#access' => !($has_matches),
    ];

    $form['limit_to_fields'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit To Field(s) (optional)'),
      '#description' => $this->t('Enter field names separated by commas. Leave empty to search all fields.'),
      '#required' => FALSE,
      '#default_value' => $form_state->getValue('limit_to_fields', ''),
      '#access' => !($has_matches),
    ];

    $form['moderation_states'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Moderation State(s) (optional)'),
      '#description' => $this->t('Enter moderation states separated by commas. Leave empty to search published only.'),
      '#required' => FALSE,
      '#default_value' => $form_state->getValue('moderation_states', ''),
      '#access' => !($has_matches),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    if( $has_matches ) {
      if( !empty($form_state->getValue('replace')) && !($replacement_done )) {
        // We have a search, AND the user wants to replace - but we have not confirmed it!
        $form['actions']['confirmReplace'] = [
          '#type' => 'submit',
          '#value' => t('Confirm Replacement in ' . $results['count'] . ' objects'),
          '#submit' => ['::customReplaceSubmit'],
          '#name' => 'confirm',
          '#button_type' => 'primary',
          '#attributes' => ['class' => ['confirm-replace-button']],
        ];
      }
    } else {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Search'),
        '#button_type' => 'primary',
      ];
    }

    if( $has_matches ) {
      // We have a search, put the Reset Button here
      $form['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
      ];
    }

    $results = $form_state->get('search_results');
    if ($results !== null) {
      $current_page = $form_state->get('current_page') ?? 1;
      $total_pages = ceil($results['count'] / self::ITEMS_PER_PAGE);

      $this->setResultsOutput($results, $current_page, $total_pages, $form_state);

      $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<strong><em>' . $form_state->get('message_output') . '</em></strong>',
        '#allowed_tags' => ['strong', 'em'],
      ];

      // when we're in search/pagination mode, make the box 10 rows, otherwise expand it to be as tall as the results.
      $form['results'] = [
        '#type' => 'textarea',
        '#value' => $form_state->get('results_output'),
        '#rows' => $form_state->get('replacement_done') ? $results['count'] + 1 : 10,
        '#disabled' => TRUE,
        '#access' => $has_matches,
      ];

      // add pagination here if results count > limit add paginator
      if ($total_pages > 1) {
        $form['pager'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['pager-container']],
        ];

        $form['pager']['prev'] = [
          '#type' => 'submit',
          '#value' => $this->t('← Previous'),
          '#submit' => ['::customPagerSubmit'],
          '#name' => 'prev',
          '#disabled' => !($current_page > 1),
          '#access' => !($replacement_done),
          '#attributes' => ['class' => ['pager-button']],
        ];

        $form['pager']['next'] = [
          '#type' => 'submit',
          '#value' => $this->t('Next →'),
          '#submit' => ['::customPagerSubmit'],
          '#name' => 'next',
          '#disabled' => !($current_page < ($total_pages) && !($replacement_done)),
          '#access' => !($form_state->get('replacement_done')),
          '#attributes' => ['class' => ['pager-button']],
        ];
      }
    }
    return $form;
  }

  public function customPagerSubmit(array &$form, FormStateInterface $form_state) {
    $current_page = $form_state->get('current_page') ?? 1;
    $results = $form_state->get('search_results');
    $total_pages = ceil($results['count'] / self::ITEMS_PER_PAGE);

    if ($form_state->getTriggeringElement()['#name'] == 'next' && $current_page < ($total_pages)) {
      $form_state->set('current_page', $current_page + 1);
    } elseif ($form_state->getTriggeringElement()['#name'] == 'prev' && $current_page > 1) {
      $form_state->set('current_page', $current_page - 1);
    }

    $form_state->setRebuild(TRUE);
  }

  // Default search action
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search_terms = $form_state->getValue('search');
    $limit_to_fields = $form_state->getValue('limit_to_fields');
    $moderation_states = $form_state->getValue('moderation_states');

    $limit_to_fields_array = !empty($limit_to_fields) ? array_map('trim', explode(',', $limit_to_fields)) : [];
    $moderation_states_array = !empty($moderation_states) ? array_map('trim', explode(',', $moderation_states)) : [];

    $results = $this->getSearchResults($search_terms, $limit_to_fields_array, $moderation_states_array);

    $form_state->set('search_results', $results);
    $form_state->set('current_page', 1); // Reset to first page on new search
    $form_state->setRebuild(true);
  }

  // Custom post-confirmation of replacement action
  public function customReplaceSubmit(array &$form, FormStateInterface $form_state) {
    $search_terms = $form_state->getValue('search');
    $replace_with = $form_state->getValue('replace');
    $limit_to_fields = $form_state->getValue('limit_to_fields');
    $moderation_states = $form_state->getValue('moderation_states');

    $limit_to_fields_array = !empty($limit_to_fields) ? explode(',', $limit_to_fields) : [];
    $moderation_states_array = !empty($moderation_states) ? explode(',', $moderation_states) : [];

    $results = $this->getSearchResults($search_terms, $limit_to_fields_array, $moderation_states_array, $replace_with);

    $form_state->set('search_results', $results);
    $form_state->set('current_page', 1);
    $form_state->set('replacement_done', 1);
    $form_state->setRebuild(true);
  }

  protected function getSearchResults($search_term, array $limit_to_fields, array $moderation_states, $replace_with = null) {
    if ($replace_with !== null) {
      return $this->searchAndReplaceService->replaceByRegex($search_term, $replace_with, $limit_to_fields, $moderation_states);
    } else {
      return $this->searchAndReplaceService->findByRegex($search_term, $limit_to_fields, $moderation_states);
    }
  }

  protected function setResultsOutput(array $results, int $current_page, int $total_pages, FormStateInterface $form_state) {
    $total_results = $results['count'];
    $replacement_done = $form_state->get('replacement_done');
    $results_output = '';

    if (!$replacement_done) {
      // Format for Search Results
      if ($total_results == 0) {
        $message_output = "0 Matches Found, Please Adjust Search Terms and Retry!";
      } else {
        // Ensure current page is within valid range
        $current_page = max(1, min($current_page, $total_pages));

        // Calculate the slice of results for the current page
        $start_index = ($current_page - 1) * self::ITEMS_PER_PAGE;
        $paged_results = array_slice($results['matches'], $start_index, self::ITEMS_PER_PAGE);
        $start_object = $start_index + 1;
        $end_object = min($start_object + self::ITEMS_PER_PAGE - 1, $total_results);

        $message_output = "Viewing Fields Found In Entities $start_object-$end_object of $total_results (Page $current_page of $total_pages)";

        foreach ($paged_results as $result) {
          $results_output .= "URL: {$result['url']} ({$result['title']}, {$result['type']}, {$result['moderation_state']})\n";
          foreach ($result['locations'] as $location) {
            $results_output .= "  - {$location['message']}\n";
          }
          $results_output .= "\n";
        }
      }
    } else {
      // Format For Replacement Results
      $fullyReplacedCount = 0;
      $totalOccurrences = 0;
      $successfulReplacements = 0;
      $matchCounts = [];

      // First, count total occurrences
      foreach ($results['matches'] as $match) {
        $searchCount = count(array_filter($match['locations'], function($location) {
          return $location['status'] === 'search';
        }));
        $matchCounts[$match['url']] = $searchCount;
        $totalOccurrences += $searchCount;
      }

      foreach ($results['matches'] as $match) {
        $found = 0;
        $replaced = 0;
        $errors = 0;

        foreach ($match['locations'] as $location) {
          switch ($location['status']) {
            case 'search':
              $found++;
              break;
            case 'replace':
              $replaced++;
              $successfulReplacements++;
              break;
            case 'resumable error':
              $errors++;
              break;
          }
        }

        $isFullyReplaced = ($replaced == $matchCounts[$match['url']]);
        if ($isFullyReplaced) {
          $fullyReplacedCount++;
        }

        $results_output .= sprintf(
          "%s \"%s\" with \"%s\" at %s (Found: %d, Replaced: %d, Error: %d, Fully Replaced: %s)\n",
          $isFullyReplaced ? "Replaced" : "Attempted replacement",
          $form_state->getValue('search'),
          $form_state->getValue('replace'),
          $match['url'],
          $found,
          $replaced,
          $errors,
          $isFullyReplaced ? 'Yes' : 'No'
        );
      }

      $message_output = sprintf(
        "Your search term was fully replaced in %d of %d entities (%d of %d fields).",
        $fullyReplacedCount,
        $total_results,
        $successfulReplacements,
        $totalOccurrences
      );

      // Log the replacement results array for audit purposes
      $log_message = $message_output . $results_output;
      \Drupal::logger('io_utils')->notice($log_message);
    }

    $form_state->set('message_output', $message_output);
    $form_state->set('results_output', $results_output);
  }


  protected function generateSearchSummary(FormStateInterface $form_state) {

    $search = $form_state->getValue('search') ?? '';
    $replace = $form_state->getValue('replace') ?? '';
    $moderation_states = $form_state->getValue('moderation_states')?? '';
    $limit_to_fields = $form_state->getValue('limit_to_fields')?? '';
    $replacement_done = $form_state->get('replacement_done')?? '';

    $summary = ['<div><strong><em>Searched For:</em></strong> "' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '"</div>'];

    $summary[] = '<div class="indented"><strong><em>Moderation States:</em></strong></div>';
    if (!empty($moderation_states)) {
      $states = array_map('trim', explode(',', $moderation_states));
      foreach ($states as $state) {
        $summary[] = '<div class="double-indented">' . htmlspecialchars($state, ENT_QUOTES, 'UTF-8') . '</div>';
      }
    } else {
      $summary[] = '<div class="double-indented">published only (default)</div>';
    }

    if (!empty($limit_to_fields)) {
      $summary[] = '<div class="indented"><strong><em>Field List:</em></strong></div>';
      $fields = array_map('trim', explode(',', $limit_to_fields));
      foreach ($fields as $field) {
        $summary[] = '<div class="double-indented">' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . '</div>';
      }
    }

    if (!empty($replace)) {
      if ($replacement_done) {
        $summary[] = '<div><strong><em>Replaced With:</em></strong> "' . htmlspecialchars($replace, ENT_QUOTES, 'UTF-8') . '"</div>';
        $summary[] = '<div><strong><em>NOTICE: Due to Content Sharing, Replacement Results may vary from Search Results</em></strong></div>';
      }
      else {
        $summary[] = '<div><strong><em>Replacing With:</em></strong> "' . htmlspecialchars($replace, ENT_QUOTES, 'UTF-8') . '"</div>';
        $summary[] = '<div><strong><em>PROCEED WITH CAUTION: Use of Replace feature can cause permanent project corruption, prior database backup is advised! </em></strong></div>';
        }
    }

    return implode('', $summary);
  }

  /**
   * Resets the form to its initial state.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    // Clear all form state values
    $form_state->setValues([]);

    // Clear specific form state variables we've set
    $form_state->set('search_results', null);
    $form_state->set('current_page', 1);
    $form_state->set('message_output', '');
    $form_state->set('results_output', '');
    $form_state->set('replacement_done', '');

    // Clear any user input
    $user_input = $form_state->getUserInput();
    // Save the form build id, form id and form token
    $clean_user_input = [
      'form_build_id' => $user_input['form_build_id'],
      'form_id' => $user_input['form_id'],
      'form_token' => $user_input['form_token'],
    ];
    $form_state->setUserInput($clean_user_input);

    // Clear any stored files or uploads
    $form_state->set('files', []);

    // Reset the form cache
//    \Drupal::service('form_cache')->deleteCache($form['#build_id']);

    // Rebuild the form
    $form_state->setRebuild();

    // Redirect to the form's initial route
    $form_state->setRedirect('io_utils.search_replace');
  }

}
