<?php

namespace Flosch\Bundle\ProxyBundle\Exception;

use Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnexistingUserException extends HttpException
{
    public function __construct($username, Exception $previous = null, $code = 0)
    {
        parent::__construct(500, sprintf('Unexisting user: %s.', $username), $previous, [], $code);
    }
}
