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
class ReplyForm extends BaseForm
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
    public function __construct($name = '', $markup = '', $options)
    {
        $this->markup = $markup ?: $this->markup;
        $this->caller = isset($options['caller']) ? $options['caller'] : null;
        $this->reply = $options['reply'];
        $this->review = $options['review'];
        parent::__construct();
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
            $this->filter = new ReplyFilter();
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

        
        
        $this->add(array(
            'name'          => 'content',
            'type'          => 'editor',
            'options'       => array(
                'label'     => '',
                'editor'    => $editor,
                'set'       => $set,
            ),
            'attributes'    => array(
                'placeholder'   => __('Type your content'),
                'class'         => 'form-control',
                'rows'          => 3,
                'data-autoresize' => true,
                'required' => true
            ),
        ));
        
        if (method_exists(Pi::api('comment', $this->caller), 'canonize')) {
            $this->add(array(
                    'name' => 'subscribe',
                    'type' => 'checkbox',
                    'options' => array(
                        'label' => __('Subscribe to the thread'),
                    ),
                    'attributes'    => array(
                        'checked' => 'checked',
                        
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
        
        $this->add(array(
            'name'          => 'review',
            'attributes'    => array(
                'type'  => 'hidden',
                'value' => $this->review
            ),
        ));
        
        $this->add(array(
            'name'          => 'reply',
            'attributes'    => array(
                'type'  => 'hidden',
                'value' => $this->reply
            ),
        ));
        
        $this->add(array(
            'name'          => 'type',
            'attributes'    => array(
                'type'  => 'hidden',
                'value' => $this->review ? 'REVIEW' : 'SIMPLE'
            ),
        ));
      
        foreach (array(
                     'id',
                     'root',
                     'module',
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
