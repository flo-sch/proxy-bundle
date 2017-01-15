<?php

namespace Flosch\Bundle\ProxyBundle\Exception;

use Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ExistingUserException extends HttpException
{
    public function __construct($username, Exception $previous = null, $code = 0)
    {
        parent::__construct(500, sprintf('Allready existing user: %s. Use option --override to update.', $username), $previous, [], $code);
    }
}
