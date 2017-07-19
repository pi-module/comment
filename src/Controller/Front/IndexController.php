<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\Comment\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Module\Comment\Form\PostForm;
use Module\Comment\Form\PostFilter;
use Module\Media\Form\View\Helper\FormMedia;
class IndexController extends ActionController
{
    /**
     * Demo for article with comments
     */
    public function indexAction()
    {
        //$this->redirect('', array('controller' => 'demo'));
        $title = sprintf(__('Comment portal for %s'), Pi::config('sitename'));
        $links = array(
            'all-active'   => array(
                'title' => __('All active comment posts'),
                'url'   => Pi::api('api', 'comment')->getUrl('list', array()),
            ),
            'article'   => array(
                'title' => __('Commented articles'),
                'url'   => $this->url('', array(
                    'controller'    => 'list',
                    'action'        => 'article',
                )),
            ),
            'module'   => array(
                'title' => __('Comment posts for module "Comment"'),
                'url'   => Pi::api('api', 'comment')->getUrl('module', array(
                    'name'  => 'comment',
                )),
            ),
            'type'   => array(
                'title' => __('Comment posts for module "Comment" with type "Article"'),
                'url'   => Pi::api('api', 'comment')->getUrl('module', array(
                    'name'      => 'comment',
                    'type'  => 'article',
                )),
            ),
            'user'   => array(
                'title' => sprintf(
                    __('Comment posts by %s'),
                    Pi::service('user')->get(1, 'name')
                ),
                'url'   => Pi::api('api', 'comment')->getUrl('user', array(
                    'uid'   => 1,
                )),
            ),
        );
        if ($uid = Pi::service('user')->getId()) {
            $links['my-post'] = array(
                'title' => __('Comment posts by me'),
                'url'   => Pi::api('api', 'comment')->getUrl('user', array(
                    'uid'   => $uid,
                )),
            );
            $links['my-article'] = array(
                'title' => __('Commented articles by me'),
                'url'   => $this->url('', array(
                    'controller'    => 'list',
                    'action'        => 'article',
                    'uid'           => $uid,
                )),
            );
        }
        $this->view()->assign(array(
            'title' => $title,
            'links' => $links,
        ));
        $this->view()->setTemplate('comment-index');
    }

    /**
     * Action for comment JavaScript loading
     */
    public function loadAction()
    {
        $options['review'] = true;
        
        $uri = $this->params('uri');
        $review = $this->params('review');
        $content = Pi::service('comment')->loadContent(array('uri' => $uri, 'review' => $review));
        $result = array(
            'status'    => 1,
            'content'   => $content,
        );
        return $result;
    }
    
    public function pageAction()
    {
        $uri = $this->params('uri');
        $page  = $this->params('page', 1);
        $content  = Pi::service('comment')->loadComments(array('uri' => $uri, 'page' => $page));
        $result = array(
            'status'    => 1,
            'content'   => $content,
        );  
        return $result;
    }
    
    public function subscriptionAction()
    {
        $uri = $this->params('uri');
        $subscription = $this->params('subscription');
        $routeMatch = Pi::service('url')->match($uri);
        $params = $routeMatch->getParams();
        $data = Pi::api('api', 'comment')->findRoot($params);
        $root = $data['root'];
        if (!$root) {
            return false;
        }

        // Load translations
        Pi::service('i18n')->load('module/comment:default');

        $rootData = Pi::api('api', 'comment')->getRoot($root);
        
        $rowData = array(
                'uid' => Pi::user()->getId(),
                'root' => $rootData['id']                 
        );
        Pi::model('subscription', 'comment')->delete($rowData);
        
        if ($subscription) {
            $row = Pi::model('subscription', 'comment')->createRow();
            $row->assign($rowData);
            $row->save();
        }
        
        $result = array(
            'status'    => 1,
        );  
        return $result;
    }   
}
