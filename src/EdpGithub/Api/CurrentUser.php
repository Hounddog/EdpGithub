<?php

namespace EdpGithub\Api;

class CurrentUser extends AbstractApi
{
    /**
     * Get authenticatec User
     *
     * @link http://developer.github.com/v3/users/
     * /user
     *
     * @return array
     */
    public function show()
    {
        return $this->get('user');
    }

    /**
     * {@inheritDoc}
     */
    protected function get($path, array $parameters = array(), $requestHeaders = array())
    {
        $sm = $this->getServiceManager();
        $auth = $sm->get('EdpGithub\Listener\AuthListener');

        if(null === $auth->getMethod()) {
            throw new Exception\InvalidArgumentException('Needs Authentication');
        }
        $response = $this->getClient()->getHttpClient()->get($path, $parameters, $requestHeaders);

        return $response->getContent();
    }
}