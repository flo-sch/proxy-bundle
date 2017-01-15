<?php

namespace Flosch\Bundle\ProxyBundle\Provider;

use SplFileInfo;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser as YamlParser;

use Flosch\Bundle\ProxyBundle\Model\User;

class YamlUserProvider implements UserProviderInterface
{
    /**
     * @var YamlParser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $filePath;

    public function __construct($filePath)
    {
        $fileSystem = new FileSystem();

        if (!$fileSystem->exists($filePath)) {
            $fileSystem->touch($filePath);
            $fileSystem->chmod($filePath, 0755);
        }

        $this->parser = new YamlParser();
        $this->filePath = $filePath;

        return $this;
    }

    public function loadUserByUsername($username)
    {
        $usersFileInfo = new SplFileInfo($this->filePath);

        $this->usersFile = $usersFileInfo->openFile('r');

        $content = '';
        while (!$this->usersFile->eof()) {
            $content .= $this->usersFile->fgets();
        }

        $users = $this->parser->parse($content);

        if ($users && array_key_exists($username, $users)) {
            return new User($username, $users[$username]['password'], $users[$username]['salt'], ['ROLE_USER']);
        }

        throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
    }

    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);

        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return (($class === User::class) || (is_subclass_of($class, User::class)));
    }
}
