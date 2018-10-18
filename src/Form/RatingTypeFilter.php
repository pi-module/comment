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
