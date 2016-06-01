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
use Module\System\Validator\UserEmail as UserEmailValidator;

class PostFilter extends InputFilter
{
    public function __construct()
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

        $userId = Pi::user()->getId();
        $guestApprove = Pi::service('config')->get('guest_approve', 'comment');

        if ($guestApprove === 1 && $userId === 0) {

            $this->add(array(
                'name' => 'identity',
                'required' => true,
                'filters' => array(
                    array(
                        'name' => 'StringTrim',
                    ),
                ),
            ));

            $this->add(array(
                'name' => 'email',
                'required' => true,
                'filters' => array(
                    array(
                        'name' => 'StringTrim',
                    ),
                ),
                'validators' => array(
                    array(
                        'name' => 'EmailAddress',
                        'options' => array(
                            'useMxCheck' => false,
                            'useDeepMxCheck' => false,
                            'useDomainCheck' => false,
                        ),
                    ),
                    new UserEmailValidator(array(
                        'blacklist' => false,
                        'check_duplication' => false,
                    )),
                ),
            ));

        }

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
