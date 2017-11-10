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
use Pi\Paginator\Paginator;
use Zend\Db\Sql\Expression;

/**
 * Comment list controller
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class IndexController extends ActionController
{
    /**
     * All comment posts
     */
    public function indexAction()
    {
        $page   = _get('page', 'int') ?: 1;
        $where = array('active' => 1);
               
        $result = Pi::api('api', 'comment')->getComments($page, null);

        $this->view()->assign('comment', array(
            'title'     => $this->config('head_title'),
            'count'     => $result['count'],
            'posts'     => $result['posts'],
            'paginator' => $result['paginator'],
        ));
        
        $this->view()->setTemplate('comment-list');
        $this->view()->headTitle($this->config('head_title'));
        $this->view()->headMeta($this->config('head_title'), 'twitter:title', 'name');
        $this->view()->headMeta($this->config('head_title'), 'og:title', 'property');
        $this->view()->headDescription($this->config('description'), 'set');
        $this->view()->headMeta($this->config('description'), 'twitter:description');
        $this->view()->headMeta($this->config('description'), 'og:description', 'property');
        $this->view()->headKeywords($this->config('keywords'), 'set');
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
        $type  = $this->params('type');

        $content  = Pi::service('comment')->loadComments(
            array(
                'uri' => $uri, 
                'page' => $page,
                'review' => $type == 'review'  
            )
        );
        
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

        Pi::api('api', 'comment')->subscription($root, $subscription);
        
        $result = array(
            'status'    => 1,
        );  
        return $result;
    } 
}
