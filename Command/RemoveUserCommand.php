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

use Flosch\Bundle\ProxyBundle\Exception\UnexistingUserException;

/**
 * A console command for removing an existing user access.
 *
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class RemoveUserCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('flosch-proxy:users:remove')
            ->setDescription('Remove an existing user')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                'What is the user\'s name?'
            )
            ->addOption('all', null, InputOption::VALUE_NONE, 'Remove all existing users')
        ;
    }

    /**
     * Executes the command
     *
     * @throws UnexistingUserException
     * @throws ParameterNotFoundException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);
        $container = $this->getContainer();
        $input->isInteractive() ? $output->title('FloschProxyBundle users helper') : $output->newLine();

        // Save user in users file
        $filePath = $container->getParameter('users_provider_file_path');

        $fileSystem = new FileSystem();

        if ($fileSystem->exists($filePath)) {
            $usersFileInfo = new SplFileInfo($filePath);

            if ($input->getOption('all')) {
                $usersFile = $usersFileInfo->openFile('w+');
                $usersFile->fwrite('');

                $output->success('All users have been removed.');
            } else {
                $usersFile = $usersFileInfo->openFile('r+');

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

                $content = '';
                while (!$usersFile->eof()) {
                    $content .= $usersFile->fgets();
                }

                $parser = new YamlParser();
                $users = $parser->parse($content);

                if (array_key_exists($username, $users)) {
                    unset($users[$username]);
                } else {
                    throw new UnexistingUserException($username);
                }

                $dumper = new YamlDumper();

                $usersFile = $usersFileInfo->openFile('w+');

                $usersFile->rewind();
                $usersFile->fwrite($dumper->dump($users, 2));

                $output->success('User successfully removed');
            }
        } else {
            $output->success('Users file does not exists... Nothing to remove.');
        }
    }

    /**
     * Create the password question to ask the user for the password to be encoded.
     *
     * @return Question
     */
    private function createUsernameQuestion()
    {
        $passwordQuestion = new Question('What is the user\'s name?');

        return $passwordQuestion
            ->setValidator(function ($value) {
                if ('' === trim($value)) {
                    throw new Exception('The username must not be empty.');
                }

                return $value;
            })
            ->setHidden(true)
            ->setMaxAttempts(20)
        ;
    }
}
