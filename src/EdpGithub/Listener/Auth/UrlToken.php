<?php

namespace EdpGithub\Listener\Auth;

use EdpGithub\Client;

use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Buzz\Util\Url;

use Zend\EventManager\EventCollection;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\Event;

class UrlToken implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    private $options;

    protected $listeners = array();

    public function attach(EventManagerInterface $events)
    {
        $em = $events->getSharedManager();
        $this->listeners[] = $em->attach('EdpGithub\HttpClient\HttpClient','pre.send', array($this, 'preSend'));
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Set Options
     * @param array
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\InvalidArgumentException
     */
    public function preSend(Event $e)
    {
        $request = $e->getTarget();

        if (!isset($this->options['tokenOrLogin'])) {
            throw new Exception\InvalidArgumentException('You need to set OAuth token!');
        }

        $url  = $request->getUrl();
        $url .= (false === strpos($url, '?') ? '?' : '&').utf8_encode(http_build_query(array('access_token' => $this->options['tokenOrLogin']), '', '&'));

        $request->fromUrl(new Url($url));
    }
}