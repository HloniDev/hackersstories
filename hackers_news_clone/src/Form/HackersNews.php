<?php
namespace Drupal\hackers_news_clone\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Datetime\DrupalDateTime;
Use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Pager;
use Drupal\Core\Database\Query\PagerSelectExtender;


class HackersNews extends FormBase {
  public function getFormId() {
    return 'hackers_news';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $stories  = \Drupal::request()->query->get('hackers_news_option');

    $form = $this->addFilters($form,$form_state,$stories);
      $table_structure = [];
      if(empty($stories)){
      $table_structure['header'] = [
        ['data' => $this->t(' Top Stories')],
        ['data' => $this->t('Comment')]
      ];
      $response = apicall('https://hacker-news.firebaseio.com/v0/topstories.json');
    }elseif ($stories == '1') {
      $table_structure['header'] = [
        ['data' => $this->t('New stories')],
        ['data' => $this->t('Comment')]
      ];
      $response = apicall('https://hacker-news.firebaseio.com/v0/newstories.json');
    }elseif ($stories == '2') {
      $table_structure['header'] = [
        ['data' => $this->t('Best stories')],
        ['data' => $this->t('Comment')]
      ];
      $response = apicall('https://hacker-news.firebaseio.com/v0/beststories.json');
    }
      /*
     * Get Hacker News Stories
     * @return Array of IDs
     */

        $stories_ids = json_decode($response);//decode ids json responce
        $sliced_stories_ids = array_slice($stories_ids, 0, 10);//slice to only 10 ids
        //Now fetch stories using IDs
        $rows = stories($sliced_stories_ids);

        //$form['responce'] = $response;

        $rowPiece = $this->pagerArray($rows, 5);
        $form['table1'] = [
          '#theme' => 'table',
          '#header' => $table_structure['header'],
          '#rows' => $rowPiece,
          '#sticky' => TRUE,
          '#empty' => $this->t('No results found'),
        ];
        // add the pager to the render array, and return.
        $form['pager'] = [
          '#type' => 'pager',
          '#tags' => [],
        ];

        return $form;

  }


  public function pagerArray($items, $itemsPerPage) {
    // Get total items count.
    $total = count($items);
    // Get the number of the current page.
    $currentPag = \Drupal::service('pager.manager');
    $cuurpage = $currentPag->createPager($total, $itemsPerPage)->getCurrentPage();
    // Split an array into chunks.
    $chunks = array_chunk($items, $itemsPerPage);
    // Return current group item.
    $currentPageItems = $chunks[$cuurpage];
    //dump($currentPageItems);die();
    return $currentPageItems;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $options = $form_state->getValue('hackers_news_option');
    $params = array ();
    if (! empty ( $options )) {
      $params ["hackers_news_option"] = $options;
    }
    $current_path = \Drupal::service('path.current')->getPath();
    $current_path_arr = explode('/',$current_path);
    $appps = $current_path_arr[1];
    //dump($current_path_arr);die();
    if($appps == 'hackers-news-clone'){

      $form_state->setRedirect('hackers_news_clone.form_view', [], ['query' => $params]);
    }
  }
  public function addFilters($form,$form_state,$stories){
    $form ['markup_prefix'] = [
        '#type'=>'markup',
        '#markup'=> '<div class="views-exposed-form">'
    ];

    $form['hackers_news_option'] = [
      '#type' => 'select',
      '#title' => 'Filter by story type',
      '#multiple' => false,
      '#default_value'=>$stories,
      '#options' => array(t('Top stories'), t('New stories'), t('Best stories')
     )
    ];

    $form['submit'] = [
      '#prefix' => '<div class="form-actions js-form-wrapper form-wrapper app-filt-sect" id="edit-actions">', //<div class="app-filt">
        '#type'=>'submit',
        '#value'=>'Apply Filters',
        '#attributes' => array('class' => array('btn-primary ML8')),
        '#suffix'=>'   <a href="?" class="rev-report-reset">Reset Filters</a> </div> ' // <div class="rst-sect"><<a href="?" class="rev-report-reset">Reset Filters</a> </div></div>
    ];

    $form ['markup_suffix'] = [
      '#type'=>'markup',
      '#markup'=> '</div> </div>'
    ];

    return $form;
  }

}
