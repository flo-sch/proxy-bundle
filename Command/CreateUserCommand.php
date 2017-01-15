<?php

namespace Flosch\Bundle\ProxyBundle\Command;

use SplFileInfo;
use Exception;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Dumper as YamlDumper;

use Flosch\Bundle\ProxyBundle\Model\User;
use Flosch\Bundle\ProxyBundle\Exception\ExistingUserException;

/**
 * A console command for adding a new user access.
 *
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class CreateUserCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('flosch-proxy:users:create')
            ->setDescription('Create a new user')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                'What is the new user\'s name?'
            )
            ->addArgument(
                'password',
                InputArgument::OPTIONAL,
                'What is the new user\'s password?'
            )
            ->addOption('override', 'o', InputOption::VALUE_NONE, 'Override an existing user')
        ;
    }

    /**
     * Executes the command
     *
     * @throws ExistingUserException
     * @throws ParameterNotFoundException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);
        $container = $this->getContainer();
        $input->isInteractive() ? $output->title('FloschProxyBundle users helper') : $output->newLine();

        // Retrieve username or ask for it
        $username = $input->getArgument('username');

        if (!$username) {
            if (!$input->isInteractive()) {
                $output->error('The username must not be empty.');

                return 1;
            }

            $usernameQuestion = $this->createUsernameQuestion($input, $output);
            $username = $output->askQuestion($usernameQuestion);
        }

        // Retrieve password or ask for it
        $password = $input->getArgument('password');

        if (!$password) {
            if (!$input->isInteractive()) {
                $output->error('The password must not be empty.');

                return 1;
            }
            $passwordQuestion = $this->createPasswordQuestion($input, $output);
            $password = $output->askQuestion($passwordQuestion);
        }

        // Encrypt user password
        $user = new User($username, null, $this->generateSalt(), ['ROLE_USER']);

        $encoder = $container->get('security.password_encoder');
        $user->setPassword($encoder->encodePassword($user, $password));

        // Save user in users file
        $filePath = $container->getParameter('users_provider_file_path');

        $fileSystem = new FileSystem();

        if (!$fileSystem->exists($filePath)) {
            $fileSystem->touch($filePath);
            $fileSystem->chmod($filePath, 0755);
        }

        $usersFileInfo = new SplFileInfo($filePath);
        $usersFile = $usersFileInfo->openFile('r+');

        $content = '';
        while (!$usersFile->eof()) {
            $content .= $usersFile->fgets();
        }

        $parser = new YamlParser();
        $users = $parser->parse($content);

        if (!$users) {
            $users = [];
        }

        $userExists = array_key_exists($user->getUsername(), $users);

        if ($userExists && !($input->getOption('override'))) {
            throw new ExistingUserException($user->getUsername());
        }

        $users[$user->getUsername()] = [
            'salt' => $user->getSalt(),
            'password' => $user->getPassword()
        ];

        $dumper = new YamlDumper();

        $usersFile->rewind();
        $usersFile->fwrite($dumper->dump($users, 2));

        // Output result
        $output->table([
            'Info',
            'Value'
        ], [
            ['Username', $user->getUsername()],
            ['Encoder used', get_class($encoder)],
            ['Salt', $user->getSalt()],
            ['Encoded password', $user->getPassword()],
        ]);

        $output->success(sprintf('User successfully %s', $userExists ? 'replaced' : 'created'));
    }

    /**
     * Create the username question to ask the user for the username.
     *
     * @return Question
     */
    private function createUsernameQuestion()
    {
        $usernameQuestion = new Question('What is the new user\'s name?');

        return $usernameQuestion
            ->setValidator(function ($value) {
                if ('' === trim($value)) {
                    throw new Exception('The username must not be empty.');
                }

                return $value;
            })
            ->setHidden(false)
            ->setMaxAttempts(20)
        ;
    }

    /**
     * Create the password question to ask the user for the password to be encoded.
     *
     * @return Question
     */
    private function createPasswordQuestion()
    {
        $passwordQuestion = new Question('What is the new user\'s password?');

        return $passwordQuestion
            ->setValidator(function ($value) {
                if ('' === trim($value)) {
                    throw new Exception('The password must not be empty.');
                }

                return $value;
            })
            ->setHidden(true)
            ->setMaxAttempts(20)
        ;
    }

    private function generateSalt($bytes = 30)
    {
        return bin2hex(random_bytes($bytes));
    }
}
