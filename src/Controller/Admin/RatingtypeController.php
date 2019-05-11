<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt New BSD License
 */

/**
 * @author Mickaël STAMM  <contact@sta2m.com>
 */
namespace Module\Comment\Controller\Admin;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Module\Comment\Form\RatingTypeForm;
use Module\Comment\Form\RatingTypeFilter;

class RatingtypeController extends ActionController
{
    
    public function indexAction() 
    {
        $id = $this->params('id');
        
        $list = array();
        $select = $this->getModel('rating_type')->select();
        $rowset = $this->getModel('rating_type')->selectWith($select);
        foreach ($rowset as $row) {
            $types[$row->id] = $row->toArray();
        }

        $form = new RatingTypeForm($id);
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $form->setInputFilter(new RatingTypeFilter($option));
            $form->setData($data);
            if ($form->isValid()) {
                // Save values
                $values = $form->getData();
                if ($id) {
                    $row = $this->getModel('rating_type')->find($id);
                } else {
                    $row = $this->getModel('rating_type')->createRow();    
                }
                
                $row->assign($values);
                $row->save();
                
                $url = Pi::url($this->url('', array(
                    'module' => 'comment',
                    'controller' => 'ratingtype',
                )));
                $message = __('Your rating type was added');
                $this->jump($url, $message);
            }
        } else {
            if ($id) {
                $row = $this->getModel('rating_type')->find($id);
                if ($row) {
                    $form->setData($row->toArray());
                }
            }
        }
        $this->view()->setTemplate('rating-type-index');
        $this->view()->assign('form', $form);
        $this->view()->assign('types', $types);
    }
    
    public function deleteAction()
    {
        $id = $this->params('id');
        
        $this->getModel('rating_type')->delete(array('id' => $id));
        $url = Pi::url($this->url('', array(
            'module' => 'comment',
            'controller' => 'ratingtype',
        )));
        $message = __('Your rating type was deleted');
        $this->jump($url, $message);
    }
}