<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Module\Comment\Form;

use Pi;
use Pi\Form\Form as BaseForm;
use Zend\Form\Form;
use Zend\Form\Element;

/**
 * Form of category
 *
 * @author Mickaël STAMM
 */
class RatingTypeForm extends BaseForm
{
    protected $_id; 
     
    /**
     * Constructor
     *
     * @param string $type name for the element
     */
    public function __construct($id)
    {
        $this->_id = $id;
        parent::__construct();
    }

    public function getInputFilter()
    {
        if (!$this->filter) {
            $this->filter = new RatingTypeFilter;
        }
        return $this->filter;
    }
    
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->add(array(
            'name' => 'type',
            'options' => array(
                'label' => __('Title'),
            ),
            'attributes' => array(
                'type' => 'text',
                'description' => '',
                'required' => true,
            )
        ));
        
        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'class' => 'btn btn-primary',
                'value' => $this->_id ? __('Edit') : __('Add'),
            )
        ));
    }
}
