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
use Zend\InputFilter\InputFilter;
use Module\System\Validator\UserEmail as UserEmailValidator;

class ReplyFilter extends InputFilter
{
    public function __construct($options)
    {
        $this->add(array(
            'name'          => 'content',
            'allow_empty'   => false,
            'filters'       => array(
                array(
                    'name'  => 'StringTrim',
                ),
            ),
        ));
        
        
        $this->add(array(
            'name'          => 'review',
            'required' => true,
        ));
        
        $this->add(array(
            'name'          => 'subscribe',
            'required' => false,
        ));
        
        foreach (array(
                     'id',
                     'root',
                     'reply'
                 ) as $intElement
        ) {
            $this->add(array(
                'name'          => $intElement,
                'allow_empty'   => true,
                'filters'       => array(
                    array(
                        'name'  => 'Int',
                    ),
                ),
            ));
        }

        foreach (array(
                     'module',
                     'type',
                     'item',
                     'markup',
                     'redirect'
                 ) as $stringElement
        ) {
            $this->add(array(
                'name'          => $stringElement,
                'allow_empty'   => true,
                'filters'       => array(
                    array(
                        'name'  => 'StringTrim',
                    ),
                ),
            ));
        }
    }
}
