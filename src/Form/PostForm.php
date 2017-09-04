<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\Comment\Form;

use Pi;
use Pi\Form\Form as BaseForm;


/**
 * Form of comment post
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class PostForm extends BaseForm
{
    /**
     * Editor type
     *
     * @var string
     */
    protected $markup = 'text';

    /**
     * Constructor
     *
     * @param string|int    $name   Optional name for the element
     * @param string        $markup Page type: text, html, markdown
     */
    public function __construct($name = '', $markup = '', $ratings = array())
    {
        $name = $name ?: 'comment-post';
        $this->markup = $markup ?: $this->markup;
        $this->ratings = $ratings;
        parent::__construct($name);
        $this->setAttribute('action', Pi::service('comment')->getUrl('submit'));
    }

    /**
     * Load filter
     *
     * @return PostFilter
     */
    public function getInputFilter()
    {
        if (!$this->filter) {
            $options = array(
                'ratings' => $ratings,
                'review'  => count($ratings) ? 1 : 0
            );
            
            $this->filter = new PostFilter($options);
        }

        return $this->filter;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $set = '';
        switch ($this->markup) {
            case 'html':
                $editor         = 'html';
                break;
            case 'markdown':
                $editor         = 'markitup';
                $set            = 'markdown';
                break;
            case 'text':
            default:
                $editor         = 'textarea';
                $this->markup   = 'text';
                break;
        }

        $userId = Pi::user()->getId();
        $guestApprove = Pi::service('config')->get('guest_approve', 'comment');

        if ($guestApprove === 1 && $userId === 0) {

            $this->add(array(
                'name' => 'identity',
                'options' => array(
                    'label' => __('Identity'),
                ),
                'attributes' => array(
                    'type' => 'text',
                    'required' => true,
                )
            ));

            $this->add(array(
                'name' => 'email',
                'options' => array(
                    'label' => __('Email'),
                ),
                'attributes' => array(
                    'type' => 'text',
                    'required' => true,
                )
            ));

        }
        
        $this->add(array(
            'name'          => 'content',
            'type'          => 'editor',
            'options'       => array(
                'label'     => __('Comment'),
                'editor'    => $editor,
                'set'       => $set,
            ),
            'attributes'    => array(
                'placeholder'   => __('Type your content'),
                'class'         => 'form-control',
                'rows'          => 5,
                'data-autoresize' => true,
                'required' => true
            ),
        ));
        
        if (method_exists(Pi::api('comment', Pi::service('module')->current()), 'canonize')) {
            $this->add(array(
                    'name' => 'subscribe',
                    'type' => 'checkbox',
                    'options' => array(
                        'label' => __('Subscribe to the thread'),
                    ),
                    'attributes'    => array(
                        'checked' => 'checked'
                    )
            ));
        }
        
        $this->add(array(
            'name'          => 'submit',
            //'type'          => 'button',
            'attributes'    => array(
                'type'  => 'submit',
                'value' => __('Publish'),
                'class' => 'btn btn-primary',
            ),
        ));

        $this->add(array(
            'name'          => 'markup',
            'attributes'    => array(
                'type'  => 'hidden',
                'value' => $this->markup,
            ),
        ));
        
        if (!empty($this->ratings)) {
$html =<<<'EOT'
    <div class="col-md-8 no-padding">
    <label><i class="text-danger" style="margin-right: 5px;" title="Requis">*</i>%s</label>
    </div>
    <div class="col-md-4 no-padding">
    
    <div class="rating" for="%s">
        <a href="#" data-value="5" class="fa fa-star"></a>
        <a href="#" data-value="4" class="fa fa-star"></a>
        <a href="#" data-value="3" class="fa fa-star"></a>
        <a href="#" data-value="2" class="fa fa-star"></a>
        <a href="#" data-value="1" class="fa fa-star"></a>
    </div>
    </div>
EOT;
        
            foreach ($this->ratings as $key => $rating) {
               $htmlStar = sprintf($html, $rating, 'rating-' .  $key);
               $this->add(array(
                    'name' => 'star-' . $key,
                    'type' => 'Html',
                    'options' => array(
                        'label' => $rating,
                    ),
                    'attributes' => array(
                        'value' => $htmlStar,
                        'label' => $rating,
                    )
                ));
                $this->add(array(
                    'name'          => 'rating-' . $key,
                    'attributes'    => array(
                        'type'  => 'hidden',
                        'required' => true
                    ),
                ));
            }
            
            
              $this->add(array(
            'name' => 'main_image',
            'type' => 'Module\Media\Form\Element\Media',
            'options' => array(
                'label' => __('Main image'),
                'media_season' => false,
                'media_season_recommended' => true,
                'is_freemium' => false,
                'can_connect_lists' => true,
                'module' => 'comment',
            ),
        ));

        $this->add(array(
            'name' => 'additional_images',
            'type' => 'Module\Media\Form\Element\Media',
            'options' => array(
                'label' => __('Additional images'),
                'media_gallery' => true,
                'can_connect_lists' => true,
                'is_freemium' => false,
                'module' => 'comment',
            ),
        ));
        
            $this->add(array(
                'name'          => 'review',
                'attributes'    => array(
                    'type'  => 'hidden',
                    'value' => 1 
                ),
            ));
            
            $this->add(array(
                'name'          => 'time_experience',
                'type' => 'datepicker',
                'options' => array(
                    'label' => __('Time start'),
                    'datepicker' => array(
                        'format' => 'yyyy-mm-dd',
                        'autoclose' => true,
                        'todayBtn' => true,
                        'todayHighlight' => true,
                        'weekStart' => 1,
                    ),
                ),
                'attributes' => array(
                    'required' => true,
                    'class' => "form-control"
                    
                )
            ));
            
        } else {
            $this->add(array(
                'name'          => 'review',
                'attributes'    => array(
                    'type'  => 'hidden',
                    'value' => 0
                ),
            ));
        }
        
        foreach (array(
                     'id',
                     'root',
                     'reply',
                     'module',
                     'type',
                     'item',
                     'redirect'
                 ) as $hiddenElement
        ) {
            $this->add(array(
                'name'  => $hiddenElement,
                'type'  => 'hidden',
            ));
        }
    }
}
