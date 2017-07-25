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
use Zend\InputFilter\InputFilter;

/**
 * Filter of Rating Type
 *
 * @author Mickaël STAMM
 **/
class RatingTypeFilter extends InputFilter
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->add(array(
            'name' => 'type',
            'required' => true,
        ));
        
     
    }
}
