<?php

namespace EdpGithub;

use Buzz\Client\Curl;
use Buzz\Client\ClientInterface;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;
use Zend\Filter\Word\UnderscoreToCamelCase;

use EdpGithub\HttpClient\HttpClient;
use EdpGithub\HttpClient\HttpClientInterface;
use EdpGithub\HttpClient\Listener\AuthListener;

class Client implements ServiceManagerAwareInterface, EventManagerAwareInterface
{
    /*
     * EventManager
     */
    protected $events;

    /**
     * @var array
     */
    private $options = array(
        'base_url'    => 'https://api.github.com/',
        'timeout'     => 10,
        'api_version' => 'beta',
    );

    /**
     * The Buzz instance used to communicate with GitHub
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    public function api($resource)
    {
        $filter = new UnderscoreToCamelCase();
        $resource = $filter->filter($resource);

        $em = $this->getEventManager();
        $em->trigger('api', $this);

        $service = $this->getServiceManager()->get('EdpGithub\Api\\' . $resource);
        $service->setClient($this);
        return $service;
    }

    /**
     * Authenticate a user for all next requests
     *
     * @param string      $tokenOrLogin  GitHub private token/username/client ID
     * @param null|string $password      GitHub password/secret
     * @param string $authMethod
     */
    public function authenticate($authMethod, $tokenOrLogin, $password = null)
    {
        $filter = new UnderscoreToCamelCase();
        $authMethod = $filter->filter($authMethod);

        $sm = $this->getServiceManager();
        $authListener = $sm->get('EdpGithub\Listener\Auth\\' . $authMethod);
        $authListener->setOptions(
            array(
                'tokenOrLogin' => $tokenOrLogin,
                'password'     => $password
            )
        );
        $this->getEventManager()->attach($authListener);
    }

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if(null === $this->httpClient) {
            $em = $this->getEventManager();
            $sm = $this->getServiceManager();

            $em->trigger('init', $this);
            $httpClient = new Curl();
            $httpClient->setTimeout($this->options['timeout']);
            $httpClient->setVerifyPeer(false);

            $this->httpClient = $sm->get('EdpGithub\HttpClient');
            $this->httpClient->setOptions($this->options);
            $this->httpClient->setClient($httpClient);
        }
        return $this->httpClient;
    }

    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array(
            __CLASS__,
            get_called_class(),
        ));
        $this->events = $events;
        return $this;
    }

    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }
}